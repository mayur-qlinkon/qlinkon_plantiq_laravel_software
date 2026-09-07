<?php

namespace App\Services\Production;

use App\Models\Production\GrowingSpace;
use App\Models\Production\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bulk Generator — a tool, not a data entity (per architecture doc).
 * Mass-creates Growing Spaces from a prefix + row range + column range,
 * all belonging to one Zone (or directly to a Site, if zone_id is null)
 * and one Growing Space Type.
 *
 * Expected $params keys:
 *   production_site_id   (int, required)
 *   zone_id               (int|null, optional — null = directly under Site)
 *   growing_space_type_id (int, required)
 *   capacity               (float, required — applied to every generated space)
 *   prefix                 (string, default '')
 *   row_start, row_end     (int, required)
 *   col_start, col_end     (int, required)
 *   name_template           (string, default '{prefix}R{row}-C{col}')
 *   sort_order_start        (int, default 0)
 */
class GrowingSpaceBulkGeneratorService
{
    /**
     * Build the list of names that WOULD be created, without touching the
     * database. Flags any name that already exists in the target scope so
     * the caller can show a confirmation UI before committing.
     *
     * @throws \RuntimeException  When the given zone belongs to a different Site
     * @return array<int, array{name: string, row: int, col: int, conflict: bool}>
     */
    public function preview(array $params): array
    {
        $this->assertZoneBelongsToSameSite($params['zone_id'] ?? null, $params['production_site_id']);

        $grid = $this->buildGrid($params);
        $existingNames = $this->existingNamesInScope($params);

        return array_map(function (array $row) use ($existingNames) {
            $row['conflict'] = $existingNames->contains($row['name']);

            return $row;
        }, $grid);
    }

    /**
     * Create the Growing Spaces. Fails fast (nothing is written) if any
     * generated name already exists in the target scope, unless
     * $skipConflicts is explicitly true — in that case conflicting names
     * are left untouched and only the new ones are created.
     *
     * @throws \RuntimeException  When conflicts exist and $skipConflicts is false
     * @return array{created: Collection<int, GrowingSpace>, skipped: array<int, string>}
     */
    public function generate(array $params, bool $skipConflicts = false): array
    {
        $grid = $this->preview($params);
        $conflicts = array_values(array_filter($grid, fn (array $row) => $row['conflict']));

        if ($conflicts && ! $skipConflicts) {
            $names = implode(', ', array_column($conflicts, 'name'));

            throw new \RuntimeException(
                "These names already exist in this zone: {$names}. Use a different prefix/range, or pass \$skipConflicts = true."
            );
        }

        $toCreate = array_values(array_filter($grid, fn (array $row) => ! $row['conflict']));

        $created = DB::transaction(function () use ($toCreate, $params) {
            $sortOrder = $params['sort_order_start'] ?? 0;
            $collection = collect();

            foreach ($toCreate as $row) {
                $collection->push(GrowingSpace::create([
                    'production_site_id' => $params['production_site_id'],
                    'zone_id' => $params['zone_id'] ?? null,
                    'growing_space_type_id' => $params['growing_space_type_id'],
                    'name' => $row['name'],
                    'capacity' => $params['capacity'],
                    'sort_order' => $sortOrder++,
                ]));
            }

            return $collection;
        });

        return [
            'created' => $created,
            'skipped' => array_column($conflicts, 'name'),
        ];
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    protected function assertZoneBelongsToSameSite(?int $zoneId, int $siteId): void
    {
        if ($zoneId === null) {
            return;
        }

        $zoneSiteId = Zone::whereKey($zoneId)->value('production_site_id');

        if ($zoneSiteId !== $siteId) {
            throw new \RuntimeException("Target zone must belong to the same Production Site.");
        }
    }

    // ════════════════════════════════════════════════════
    //  INTERNALS
    // ════════════════════════════════════════════════════

    /**
     * @return array<int, array{name: string, row: int, col: int}>
     */
    protected function buildGrid(array $params): array
    {
        $prefix = $params['prefix'] ?? '';
        $template = $params['name_template'] ?? '{prefix}R{row}-C{col}';

        $grid = [];

        for ($row = $params['row_start']; $row <= $params['row_end']; $row++) {
            for ($col = $params['col_start']; $col <= $params['col_end']; $col++) {
                $grid[] = [
                    'name' => strtr($template, [
                        '{prefix}' => $prefix,
                        '{row}' => $row,
                        '{col}' => $col,
                    ]),
                    'row' => $row,
                    'col' => $col,
                ];
            }
        }

        return $grid;
    }

    protected function existingNamesInScope(array $params): Collection
    {
        $query = GrowingSpace::query()->where('production_site_id', $params['production_site_id']);

        if (! empty($params['zone_id'])) {
            $query->where('zone_id', $params['zone_id']);
        } else {
            $query->whereNull('zone_id');
        }

        return $query->pluck('name');
    }
}