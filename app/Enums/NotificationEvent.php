<?php

namespace App\Enums;

/**
 * Broadcast notification events — the ones whose recipients are a business
 * decision the tenant makes.
 *
 * Targeted notifications are deliberately absent. When a lead is assigned or
 * a leave request is approved, the recipient follows from the data itself;
 * letting a tenant redirect "your leave was approved" to someone else would
 * be meaningless. Those keep dispatching directly to the person concerned.
 *
 * Adding an event: one case, plus an arm in each match below.
 */
enum NotificationEvent: string
{
    case OrderPlaced             = 'order.placed';
    case AppointmentBooked       = 'appointment.booked';
    case LeaveRequested          = 'leave.requested';
    case WorkLogSubmitted        = 'worklog.submitted';
    case OcrScanCompleted        = 'ocr.completed';
    case ProductionTaskMissed    = 'production.task_missed';
    case ProductionTaskCompleted = 'production.task_completed';
    case InventoryLowStock       = 'inventory.low_stock';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced             => 'Order Inquiries',
            self::AppointmentBooked       => 'Appointment Bookings',
            self::LeaveRequested          => 'Leave Requests',
            self::WorkLogSubmitted        => 'Work Log Submissions',
            self::OcrScanCompleted        => 'OCR Scans',
            self::ProductionTaskMissed    => 'Missed Production Tasks',
            self::ProductionTaskCompleted => 'Completed Production Tasks',
            self::InventoryLowStock       => 'Low Stock Alerts',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OrderPlaced             => 'A customer submits a new inquiry from the storefront',
            self::AppointmentBooked       => 'A customer books an appointment from the storefront',
            self::LeaveRequested          => 'An employee applies for leave',
            self::WorkLogSubmitted        => 'An employee submits a work log for approval',
            self::OcrScanCompleted        => 'A scanned document finishes processing',
            self::ProductionTaskMissed    => 'A daily task was not completed on its scheduled day',
            self::ProductionTaskCompleted => 'A worker marks a daily task as done',
            self::InventoryLowStock       => 'A product drops to or below its stock alert level',
        };
    }

    /**
     * Module slug this event belongs to; null means it is core.
     *
     * The settings screen hides events whose module the tenant does not have,
     * so a nursery without HRM never sees a Leave Requests card.
     */
    public function module(): ?string
    {
        return match ($this) {
            self::OrderPlaced             => null,
            self::AppointmentBooked       => 'appointments',
            self::LeaveRequested          => 'hrm',
            self::WorkLogSubmitted        => 'hrm',
            self::OcrScanCompleted        => 'ocr_scanner',
            self::ProductionTaskMissed,
            self::ProductionTaskCompleted => 'production',
            self::InventoryLowStock       => 'inventory',
        };
    }

    /**
     * Who is notified when the tenant has configured nothing.
     *
     * A permission rather than a role: role names get edited and renamed,
     * while "whoever can approve leave" stays true as staff changes.
     */
    public function defaultPermission(): string
    {
        return match ($this) {
            self::OrderPlaced             => 'orders.view',
            self::AppointmentBooked       => 'appointments.view',
            self::LeaveRequested          => 'leaves.approve',
            self::WorkLogSubmitted        => 'work_logs.approve',
            self::OcrScanCompleted        => 'ocr_scanner.history',
            self::ProductionTaskMissed,
            self::ProductionTaskCompleted => 'production_plant_batches.view',
            self::InventoryLowStock       => 'inventory_reports.view',
        };
    }

    /** Lucide icon name for the settings card. */
    public function icon(): string
    {
        return match ($this) {
            self::OrderPlaced             => 'shopping-cart',
            self::AppointmentBooked       => 'calendar-clock',
            self::LeaveRequested          => 'calendar-check',
            self::WorkLogSubmitted        => 'clipboard-check',
            self::OcrScanCompleted        => 'scan-text',
            self::ProductionTaskMissed    => 'alert-triangle',
            self::ProductionTaskCompleted => 'check-circle',
            self::InventoryLowStock       => 'package-x',
        };
    }

    /**
     * How the default permission is described to a non-technical user.
     * "orders.view" means nothing to a nursery owner; "can view orders" does.
     */
    public function defaultPermissionLabel(): string
    {
        return match ($this) {
            self::OrderPlaced             => 'Everyone who can view orders',
            self::AppointmentBooked       => 'Everyone who can view appointments',
            self::LeaveRequested          => 'Everyone who can approve leave',
            self::WorkLogSubmitted        => 'Everyone who can approve work logs',
            self::OcrScanCompleted        => 'Everyone who can view scan history',
            self::ProductionTaskMissed,
            self::ProductionTaskCompleted => 'Everyone who can view plant batches',
            self::InventoryLowStock       => 'Everyone who can view inventory reports',
        };
    }

    /**
     * Channels the default permission row starts with.
     *
     * In-app only, always. A permission row expands to an unbounded number of
     * people — a company with thirty staff who can view orders would send
     * thirty emails per inquiry — so email is reserved for named recipients
     * the tenant has deliberately picked. In-app notifications cost nothing
     * per head.
     *
     * @return list<string>
     */
    public function defaultChannels(): array
    {
        return ['database'];
    }

    /**
     * Events the tenant may configure, filtered by the modules they have.
     *
     * @return list<self>
     */
    public static function availableToCompany(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event) => $event->module() === null || has_module($event->module())
        ));
    }
}