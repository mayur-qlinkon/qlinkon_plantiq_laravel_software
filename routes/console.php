<?php

use App\Services\Hrm\AnnouncementService;
use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:send-followup-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::call(function () {
    $service = app(AnnouncementService::class);
    $service->syncScheduledToPublished();
    $service->syncExpiredStatus();
})->everyMinute()
    ->name('sync-announcement-statuses')
    ->withoutOverlapping();

// ─── Absent marking — every night at 12:30 AM ---
Schedule::command('attendance:mark-absent')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->runInBackground();

// --- Missing checkout marking - every night at 12:30 AM ---
Schedule::command('attendance:mark-missing-checkout')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->runInBackground();

// --- Production daily tasks — every morning at 5:00 AM, before workers check in ---
Schedule::command('production:generate-daily-tasks')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground();

// --- Missed production tasks — every night at 00:05, checks yesterday's still-pending tasks ---
Schedule::command('production:notify-missed-tasks')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();

// --- Project renewals sync — every night at 00:45 ---
// Runs after the attendance jobs so the two are not competing for the same
// shared-hosting CPU slice. This is what keeps the renewals board honest:
// auto-renewals roll forward, everything else gets marked expired, and a
// last-synced timestamp is written so a dead cron becomes visible.
Schedule::command('projects:sync-renewals')
    ->dailyAt('00:45')
    ->withoutOverlapping()
    ->runInBackground();

// --- Expiring service warnings — every morning at 09:00 ---
// Deliberately NOT at night. The alert is a call to action, and an unread
// count that appears as the working day starts gets acted on; one raised at
// 1 AM is stale by the time anyone logs in.
// Runs after the sync so freshly auto-renewed services are already excluded.
Schedule::command('projects:notify-expiring')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();


// --- Low stock digest — every morning at 09:15 ---
// Morning, not night, for the same reason as projects:notify-expiring above:
// restocking is a working-day action, and an alert raised at 1 AM is already
// stale by the time anyone opens the panel.
//
// Staggered off 09:00 so it does not start alongside projects:notify-expiring —
// both use runInBackground(), and shared hosting does not enjoy two tenant-wide
// scans starting in the same second.
Schedule::command('inventory:notify-low-stock')
    ->dailyAt('09:15')
    ->withoutOverlapping()
    ->runInBackground();


// --- Tenant usage rollup — every night at 01:15 ---
// After the other nightly jobs so their writes are counted in the same run.
// The window is rolling, so a missed night self-heals on the next one.
Schedule::command('companies:sync-usage')
    ->dailyAt('01:15')
    ->withoutOverlapping()
    ->runInBackground();