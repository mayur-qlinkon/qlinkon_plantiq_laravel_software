<?php

namespace App\Services;

use App\Models\Banner;
use App\Services\Platform\ImageUploadService;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BannerService
{
    public function __construct(protected ImageUploadService $imageService) {}

    // ════════════════════════════════════════════════════
    //  STORE
    // ════════════════════════════════════════════════════
    public function store(array $data): Banner
    {
        return DB::transaction(function () use ($data) {

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // ── Process desktop image (required) ──
            if (isset($data['image_file'])) {
                $data['image'] = $this->uploadImage(
                    file: $data['image_file'],
                    path: 'banners',
                    width: 1920,
                    old: null
                );
                unset($data['image_file']);
            }

            // ── Process mobile image (optional) ──
            if (isset($data['mobile_image_file'])) {
                $data['mobile_image'] = $this->uploadImage(
                    file: $data['mobile_image_file'],
                    path: 'banners/mobile',
                    width: 800,
                    old: null
                );
                unset($data['mobile_image_file']);
            }

            // ── Defaults ──
            $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder($data['type'] ?? 'hero');
            $data['click_count'] = 0;
            $data['view_count'] = 0;

            $banner = Banner::create($data);

            Log::info('[Banner] Created', [
                'banner_id' => $banner->id,
                'company_id' => $banner->company_id,
                'type' => $banner->type,
                'position' => $banner->position,
                'created_by' => $banner->created_by,
            ]);

            return $banner;
        });
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════
    public function update(Banner $banner, array $data): Banner
    {
        return DB::transaction(function () use ($banner, $data) {

            $data['updated_by'] = Auth::id();

            // ── Update desktop image if new one uploaded ──
            if (isset($data['image_file'])) {
                $data['image'] = $this->uploadImage(
                    file: $data['image_file'],
                    path: 'banners',
                    width: 1920,
                    old: $banner->image
                );
                unset($data['image_file']);
            }

            // ── Update mobile image if new one uploaded ──
            if (isset($data['mobile_image_file'])) {
                $data['mobile_image'] = $this->uploadImage(
                    file: $data['mobile_image_file'],
                    path: 'banners/mobile',
                    width: 800,
                    old: $banner->mobile_image
                );
                unset($data['mobile_image_file']);
            }

            // ── Remove mobile image if user explicitly cleared it ──
            if (isset($data['remove_mobile_image']) && $data['remove_mobile_image']) {
                $this->deleteFile($banner->mobile_image);
                $data['mobile_image'] = null;
                unset($data['remove_mobile_image']);
            }

            $banner->update($data);

            Log::info('[Banner] Updated', [
                'banner_id' => $banner->id,
                'company_id' => $banner->company_id,
                'changed' => array_keys($data),
                'updated_by' => Auth::id(),
            ]);

            return $banner->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  DELETE (soft by default, permanent on force)
    // ════════════════════════════════════════════════════
    public function delete(Banner $banner, bool $permanent = false): bool
    {
        try {
            $id = $banner->id;
            $companyId = $banner->company_id;

            if ($permanent) {
                // Delete physical files only on permanent delete
                // (soft delete preserves files for potential restore)
                $this->deleteFile($banner->image);
                $this->deleteFile($banner->mobile_image);
                $banner->forceDelete();

                Log::warning('[Banner] Permanently Deleted', [
                    'banner_id' => $id,
                    'company_id' => $companyId,
                    'deleted_by' => Auth::id(),
                ]);
            } else {
                $banner->delete(); // soft delete — files preserved

                Log::info('[Banner] Soft Deleted', [
                    'banner_id' => $id,
                    'company_id' => $companyId,
                    'deleted_by' => Auth::id(),
                ]);
            }

            return true;

        } catch (Throwable $e) {
            Log::error('[Banner] Delete Failed', [
                'banner_id' => $banner->id ?? null,
                'permanent' => $permanent,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  RESTORE (from soft delete)
    // ════════════════════════════════════════════════════
    public function restore(int $bannerId): bool
    {
        try {
            $banner = Banner::withTrashed()->findOrFail($bannerId);
            $banner->restore();

            Log::info('[Banner] Restored', [
                'banner_id' => $bannerId,
                'restored_by' => Auth::id(),
            ]);

            return true;

        } catch (Throwable $e) {
            Log::error('[Banner] Restore Failed', [
                'banner_id' => $bannerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE ACTIVE STATUS
    // ════════════════════════════════════════════════════
    public function toggleActive(Banner $banner): bool
    {
        try {
            $banner->update([
                'is_active' => ! $banner->is_active,
                'updated_by' => Auth::id(),
            ]);

            Log::info('[Banner] Status Toggled', [
                'banner_id' => $banner->id,
                'is_active' => $banner->is_active,
                'updated_by' => Auth::id(),
            ]);

            return true;

        } catch (Throwable $e) {
            Log::error('[Banner] Toggle Failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  REORDER — single query, no N+1
    // ════════════════════════════════════════════════════
    public function reorder(array $orderedIds): bool
    {
        if (empty($orderedIds)) {
            return true;
        }

        try {
            $cases = '';
            $ids = implode(',', array_map('intval', $orderedIds));

            foreach ($orderedIds as $sortOrder => $id) {
                $id = (int) $id;
                $cases .= "WHEN {$id} THEN {$sortOrder} ";
            }

            DB::statement("
                UPDATE banners
                SET sort_order = CASE id {$cases} END,
                    updated_at = NOW()
                WHERE id IN ({$ids})
                  AND deleted_at IS NULL
            ");

            Log::info('[Banner] Reordered', [
                'count' => count($orderedIds),
                'updated_by' => Auth::id(),
            ]);

            return true;

        } catch (Throwable $e) {
            Log::error('[Banner] Reorder Failed', [
                'ids' => $orderedIds,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  TRACK CLICK
    // ════════════════════════════════════════════════════
    public function trackClick(Banner $banner): void
    {
        try {
            // Atomic increment — race condition safe
            Banner::where('id', $banner->id)->increment('click_count');
        } catch (Throwable $e) {
            // Never crash the user experience for analytics
            Log::warning('[Banner] Click Track Failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════
    //  TRACK VIEW (call from storefront, batch-friendly)
    // ════════════════════════════════════════════════════
    public function trackView(Banner $banner): void
    {
        try {
            Banner::where('id', $banner->id)->increment('view_count');
        } catch (Throwable $e) {
            Log::warning('[Banner] View Track Failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════
    //  GET ACTIVE BANNERS (storefront use)
    // ════════════════════════════════════════════════════
    public function getActiveBanners(
        string $position = 'home_top',
        string $type = 'hero'
    ): Collection {
        try {
            return Banner::query()
                ->isLive()
                ->byPosition($position)
                ->byType($type)
                ->get();

        } catch (Throwable $e) {
            Log::warning('[Banner] Active Banners Fetch Failed', [
                'position' => $position,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Always return empty collection — never crash storefront
            return new Collection;
        }
    }

    // ════════════════════════════════════════════════════
    //  GET ADMIN LIST (paginated, with filters)
    // ════════════════════════════════════════════════════
    public function getAdminList(
        ?string $type = null,
        ?string $position = null,
        int $perPage = 20
    ) {
        try {
            $query = Banner::with(['creator', 'category', 'product'])
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->where('type', $type);
            }
            if ($position) {
                $query->where('position', $position);
            }

            return $query->paginate($perPage);

        } catch (Throwable $e) {
            Log::error('[Banner] Admin List Fetch Failed', [
                'error' => $e->getMessage(),
            ]);

            return new LengthAwarePaginator([], 0, $perPage);
        }
    }

    // ════════════════════════════════════════════════════
    //  DUPLICATE A BANNER
    // ════════════════════════════════════════════════════
    public function duplicate(Banner $banner): Banner
    {
        return DB::transaction(function () use ($banner) {
            $newBanner = $banner->replicate(['click_count', 'view_count', 'created_at', 'updated_at']);
            $newBanner->admin_label = $banner->display_admin_label.' (Copy)';
            $newBanner->title = $banner->title.' (Copy)';
            $newBanner->is_active = false; // start inactive so owner reviews first
            $newBanner->sort_order = $this->nextSortOrder($banner->type);
            $newBanner->created_by = Auth::id();
            $newBanner->updated_by = Auth::id();
            $newBanner->click_count = 0;
            $newBanner->view_count = 0;
            $newBanner->save();

            Log::info('[Banner] Duplicated', [
                'original_id' => $banner->id,
                'new_id' => $newBanner->id,
                'created_by' => Auth::id(),
            ]);

            return $newBanner;
        });
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Upload image with consistent config and error handling.
     */
    private function uploadImage(mixed $file, string $path, int $width, ?string $old): string
    {
        return $this->imageService->upload(
            file: $file,
            path: $path,
            options: [
                'old_file' => $old,
                'width' => $width,
                'format' => 'webp',
                'quality' => 85,
            ]
        );
    }

    /**
     * Safely delete a file from public storage.
     * Never throws — logs warning on failure.
     */
    private function deleteFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $e) {
            Log::warning('[Banner] File Delete Failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get next sort order for a given company + type.
     * New banners go to the bottom of their group.
     */
    private function nextSortOrder(string $type): int
    {
        try {
            $max = Banner::where('type', $type)
                ->max('sort_order');

            return ($max ?? -1) + 1;

        } catch (Throwable $e) {
            return 0;
        }
    }
}
