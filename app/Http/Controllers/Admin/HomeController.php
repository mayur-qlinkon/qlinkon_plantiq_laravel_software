<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * The universal landing screen.
 *
 * Every user lands here regardless of which modules their plan includes —
 * the tile list simply shrinks. This replaces the old arrangement where the
 * main dashboard had to guess a module-specific dashboard to forward to.
 */
class HomeController extends Controller
{
    /**
     * Conditions that are neither a module nor a permission.
     *
     * Self-service pages hang off the HR profile rather than a licensed seat,
     * so they need their own gate. Unknown values fail closed: a typo in the
     * config should hide a tile, never expose one.
     */
    private function passesRequirement(array $action): bool
    {
        return match ($action['requires'] ?? null) {
            null       => true,
            'employee' => has_employee_profile(),
            default    => false,
        };
    }

    public function index(): View
    {
        $config = config('navigation');

        $actions = collect($config['actions'])
            // A route can disappear when a module's route file is not loaded,
            // and route() would then throw. Drop those rows silently.
            ->filter(fn (array $action) => Route::has($action['route']))
            ->filter(fn (array $action) => empty($action['module']) || has_module($action['module']))
            ->filter(fn (array $action) => empty($action['permission']) || has_permission($action['permission']))
            ->filter(fn (array $action) => $this->passesRequirement($action))
            ->map(function (array $action) {
                // Production tenants run their own task screen; the sidebar
                // makes the same swap, and pointing both at one route would
                // send workers to the wrong list.
                if ($action['route'] === 'admin.hrm.my-tasks.index'
                    && has_module('production')
                    && Route::has('admin.production.my-tasks.index')) {
                    $action['route'] = 'admin.production.my-tasks.index';
                }

                return $action;
            })
            ->map(fn (array $action) => [
                'label'    => $action['label'],
                'url'      => route($action['route']),
                'icon'     => $action['icon'],
                'category' => $action['category'],
                'quick'    => (bool) ($action['quick'] ?? false),
                // Pre-lowercased so the client-side filter does no work per keystroke.
                'search'   => mb_strtolower($action['label'].' '.($action['keywords'] ?? '').' '.$action['category']),
            ])
            ->values();

        // Only show tabs that actually have something behind them.
        $used = $actions->pluck('category')->unique();

        $categories = collect($config['categories'])
            ->filter(fn ($label, $key) => $key === 'quick'
                ? $actions->contains('quick', true)
                : $used->contains($key))
            ->all();

        return view('admin.home', [
            'actions'    => $actions,
            'categories' => $categories,
            'user'       => Auth::user(),
        ]);
    }
}