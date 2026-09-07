<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Hrm\HrmDashboardController;
use App\Http\Controllers\Admin\Hrm\TodoController;
use App\Http\Controllers\Admin\Hrm\MyLeaveController as HrmMyLeaveController;
use App\Http\Controllers\Admin\Hrm\MyAttendanceController as HrmMyAttendanceController;
use App\Http\Controllers\Admin\Hrm\MyTaskController as HrmMyTaskController;
use App\Http\Controllers\Admin\Hrm\MyWorkLogController as HrmMyWorkLogController;
use App\Http\Controllers\Admin\Hrm\MySalarySlipController as HrmMySalarySlipController;
use App\Http\Controllers\Admin\Hrm\DepartmentController as HrmDepartmentController;
use App\Http\Controllers\Admin\Hrm\DesignationController as HrmDesignationController;
use App\Http\Controllers\Admin\Hrm\ShiftController as HrmShiftController;
use App\Http\Controllers\Admin\Hrm\HolidayController as HrmHolidayController;
use App\Http\Controllers\Admin\Hrm\EmployeeController as HrmEmployeeController;
use App\Http\Controllers\Admin\Hrm\AttendanceController as HrmAttendanceController;
use App\Http\Controllers\Admin\Hrm\AttendanceRuleController as HrmAttendanceRuleController;
use App\Http\Controllers\Admin\Hrm\OfficeLocationController as HrmOfficeLocationController;
use App\Http\Controllers\Admin\Hrm\MobileScanController as HrmMobileScanController;
use App\Http\Controllers\Admin\Hrm\LeaveTypeController as HrmLeaveTypeController;
use App\Http\Controllers\Admin\Hrm\LeaveBalanceController as HrmLeaveBalanceController;
use App\Http\Controllers\Admin\Hrm\LeaveController as HrmLeaveController;
use App\Http\Controllers\Admin\Hrm\SalaryComponentController as HrmSalaryComponentController;
use App\Http\Controllers\Admin\Hrm\SalarySlipController as HrmSalarySlipController;
use App\Http\Controllers\Admin\Hrm\HrmTaskController;
use App\Http\Controllers\Admin\Hrm\AnnouncementController as HrmAnnouncementController;
use App\Http\Controllers\Admin\Hrm\WorkLogController as HrmWorkLogController;

// ════════════════════════════════════════════════
// HR & PAYROLL MANAGEMENT MODULE (module:hrm)
// ════════════════════════════════════════════════
Route::prefix('hrm')->middleware(['module:hrm'])->name('hrm.')->group(function () {
        Route::get('/dashboard', [HrmDashboardController::class, 'index'])
            ->middleware('permission:hrm_dashboard.view')
            ->name('dashboard');   // → admin.hrm.dashboard

        // ── Employee Todos (personal) ─────────────────────────────────────────
        Route::prefix('employee/todos')
            ->name('employee.todos.')                
            ->controller(TodoController::class)
            ->group(function () {
                Route::get('/',                    'index')          ->name('index');
                Route::post('/',                   'store')          ->name('store');
                Route::put('/{todo}',              'update')         ->name('update');
                Route::delete('/{todo}',           'destroy')        ->name('destroy');
                Route::patch('/{todo}/complete',   'toggleComplete') ->name('complete');
                Route::patch('/{todo}/important',  'toggleImportant')->name('important');
                Route::post('/reorder',            'reorder')        ->name('reorder');
            });

        // ── Employee self-service ──
        //
        // Deliberately outside module:hrm. These pages show a person their own
        // records, so an employee reaches them through their HR profile rather
        // than through a paid HRM seat.
        Route::withoutMiddleware(['module:hrm'])->middleware('employee.profile')->group(function () {

            Route::get('my-leaves', [HrmMyLeaveController::class, 'index'])->name('my-leaves.index');
            Route::post('my-leaves', [HrmMyLeaveController::class, 'store'])->name('my-leaves.store');
            // Leave documents live on the private disk, so they are only
            // reachable through this authorised action.
            Route::get('my-leaves/{leave}/document', [HrmMyLeaveController::class, 'downloadDocument'])->name('my-leaves.document');
            // Declared before my-leaves/{leave} so the static segment is not
            // swallowed by the wildcard. Leave documents live on the private
            // disk and are only reachable through this authorised action.
            Route::get('my-leaves/{leave}/document', [HrmMyLeaveController::class, 'downloadDocument'])->name('my-leaves.document');
            Route::get('my-leaves/{leave}', [HrmMyLeaveController::class, 'show'])->name('my-leaves.show');
            Route::put('my-leaves/{leave}', [HrmMyLeaveController::class, 'update'])->name('my-leaves.update');
            Route::delete('my-leaves/{leave}', [HrmMyLeaveController::class, 'destroy'])->name('my-leaves.destroy');

            // ── My Attendance (employee self-service) ──
            Route::get('my-attendance', [HrmMyAttendanceController::class, 'index'])->name('my-attendance.index');

            // ── Marking own attendance (printed QR poster → phone → scan) ──
            //
            // Same reasoning as the rest of this group: an employee marking
            // their own presence is not HRM administration. Gating it on
            // module:hrm meant a company that had not bought HRM seats for its
            // workers could not let them punch in at all — the QR poster on the
            // wall led to a 403.
            Route::get('attend/{store}', [HrmMobileScanController::class, 'show'])->name('attend');
            Route::post('attendance/scan', [HrmAttendanceController::class, 'scan'])
                ->name('attendance.scan')
                ->middleware('throttle:10,1');

            // ── My Tasks (employee self-service) ──
            Route::get('my-tasks', [HrmMyTaskController::class, 'index'])->name('my-tasks.index');
            Route::get('my-tasks/{task}', [HrmMyTaskController::class, 'show'])->name('my-tasks.show');
            Route::patch('my-tasks/{task}/progress', [HrmMyTaskController::class, 'updateProgress'])->name('my-tasks.progress');
            Route::post('my-tasks/{task}/comments', [HrmMyTaskController::class, 'addComment'])->name('my-tasks.comments.store');
            // The comments partial polls this to refresh the thread. Without it
            // the employee side could post but never read back.
            Route::get('my-tasks/{task}/comments', [HrmMyTaskController::class, 'getComments'])->name('my-tasks.comments.get');
            Route::post('my-tasks/{task}/attachments', [HrmMyTaskController::class, 'uploadAttachment'])->name('my-tasks.attachments.store');
            Route::get('my-tasks/attachments/{attachment}/download', [HrmMyTaskController::class, 'downloadAttachment'])->name('my-tasks.attachments.download');

            // ── My Work Logs (employee self-service) ──
            Route::prefix('my-work-logs')->name('my-work-logs.')->controller(HrmMyWorkLogController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{workLog}', 'update')->name('update');
                Route::delete('/{workLog}', 'destroy')->name('destroy');
            });

            // ── My Salary Slips (employee self-service) ──
            Route::get('my-salary-slips', [HrmMySalarySlipController::class, 'index'])->name('my-salary-slips.index');
            Route::get('my-salary-slips/{salarySlip}/pdf', [HrmMySalarySlipController::class, 'downloadPdf'])->name('my-salary-slips.pdf');

        }); // /employee self-service

        // ── Departments (Single Page CRUD) ──
        Route::resource('departments', HrmDepartmentController::class)->except(['create', 'show', 'edit'])->middleware('permission:departments.view');

        // ── Designations (Single Page CRUD) ──
        Route::resource('designations', HrmDesignationController::class)->except(['create', 'show', 'edit'])->middleware('permission:designations.view');

        // ── Shifts (Single Page CRUD) ──
        Route::resource('shifts', HrmShiftController::class)->except(['create', 'show', 'edit'])->middleware('permission:shifts.view');

        // ── Holidays (Single Page CRUD) ──
        Route::resource('holidays', HrmHolidayController::class)->except(['create', 'show', 'edit'])->middleware('permission:holidays.view');

        // ── Employees ──
        // Static segments must be declared before the resource, otherwise
        // employees/{employee} captures them as an ID.
        Route::get('employees/check-code', [HrmEmployeeController::class, 'checkCode'])
            ->name('employees.check-code')
            ->middleware('permission:employees.create');
        Route::get('employees/linkable-users', [HrmEmployeeController::class, 'linkableUsers'])
            ->name('employees.linkable-users')
            ->middleware('permission:employees.create');
        // Identity documents (Aadhaar, PAN) sit on the private disk. This action
        // is the only path to them, and it is declared before the resource so
        // employees/{employee} does not capture the static segment as an ID.
        //
        // {type} is whereIn-constrained: the handler reads $employee->{$type},
        // so an unconstrained value would turn this into an arbitrary column
        // reader over the employees table.
        Route::get('employees/{employee}/document/{type}', [HrmEmployeeController::class, 'downloadDocument'])
            ->whereNumber('employee')
            ->whereIn('type', ['id_proof', 'address_proof'])
            ->middleware('permission:employees.view')
            ->name('employees.document');

        // Declared explicitly rather than as a resource: middleware passed to
        // Route::resource applies to every route in the set, so requiring all
        // four verbs meant a read-only HR role holding employees.view was 403'd
        // on the very page that permission names. Each verb now gates itself.
        Route::controller(HrmEmployeeController::class)->name('employees.')->prefix('employees')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:employees.view');
            Route::get('/create', 'create')->name('create')->middleware('permission:employees.create');
            Route::post('/', 'store')->name('store')->middleware('permission:employees.create');
            Route::get('/{employee}', 'show')->name('show')->whereNumber('employee')->middleware('permission:employees.view');
            Route::get('/{employee}/edit', 'edit')->name('edit')->whereNumber('employee')->middleware('permission:employees.update');
            Route::match(['put', 'patch'], '/{employee}', 'update')->name('update')->whereNumber('employee')->middleware('permission:employees.update');
            Route::delete('/{employee}', 'destroy')->name('destroy')->whereNumber('employee')->middleware('permission:employees.delete');
        });

        // Salary Structure management per employee. This is what payroll reads,
        // so it is gated on updating the employee, not merely viewing them.
        // Declared before the collection route so "preview" is never read as
        // an {employee} segment.
        Route::get('employees/{employee}/salary-structures/preview', [HrmEmployeeController::class, 'previewSalaryStructure'])
            ->name('employees.salary-structures.preview')
            ->middleware('permission:employees.view');

        Route::get('employees/{employee}/salary-structures', [HrmEmployeeController::class, 'salaryStructures'])
            ->name('employees.salary-structures.index')
            ->middleware('permission:employees.view');
        Route::post('employees/{employee}/salary-structures', [HrmEmployeeController::class, 'storeSalaryStructure'])
            ->name('employees.salary-structures.store')
            ->middleware('permission:employees.update');
        Route::put('employees/{employee}/salary-structures/{structure}', [HrmEmployeeController::class, 'updateSalaryStructure'])
            ->name('employees.salary-structures.update')
            ->middleware('permission:employees.update');
        Route::delete('employees/{employee}/salary-structures/{structure}', [HrmEmployeeController::class, 'destroySalaryStructure'])
            ->name('employees.salary-structures.destroy')
            ->middleware('permission:employees.update');

        // ── Attendance ──
        Route::prefix('attendance')->name('attendance.')->controller(HrmAttendanceController::class)->group(function () {
            Route::get('/today', 'today')->name('today')->middleware('permission:attendance.view');
            Route::get('/report', 'report')->name('report')->middleware('permission:attendance.report');
            Route::get('/export/excel', 'exportExcel')->name('export.excel')->middleware('permission:attendance.report');
            Route::get('/export/pdf', 'exportPdf')->name('export.pdf')->middleware('permission:attendance.report');
            Route::post('/{attendance}/override', [HrmAttendanceController::class, 'override'])->name('override')->middleware('permission:attendance.override');
            Route::post('/{attendance}/review-location', [HrmAttendanceController::class, 'reviewLocation'])->name('review-location')->middleware('permission:attendance.override');
        });

        // ── Attendance Rules (Single Page CRUD) ──
        Route::post('attendance-rules/holiday-policy', [HrmAttendanceRuleController::class, 'updateHolidayPolicy'])
            ->name('attendance-rules.holiday-policy')
            ->middleware('permission:attendance_rules.update');
        Route::resource('attendance-rules', HrmAttendanceRuleController::class)->except(['create', 'show', 'edit'])->middleware('permission:attendance_rules.view');

        // ── Attendance Settings ──

        // ── Office Locations (GPS + per-store QR) ──
        Route::prefix('office-locations')->name('office-locations.')->controller(HrmOfficeLocationController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:office_locations.view');
            Route::put('/{store}', 'update')->name('update')->middleware('permission:office_locations.update');
            Route::post('/{store}/generate-qr', 'generateQr')->name('generate-qr')->middleware('permission:office_locations.generate_qr');
            Route::get('/{store}/poster', 'poster')->name('poster')->middleware('permission:office_locations.view');
        });

        

        // ── Leave Types (Single Page CRUD) ──
        Route::resource('leave-types', HrmLeaveTypeController::class)->except(['create', 'show', 'edit'])->middleware('permission:leave_types.view');

        // ── Leave Balances ──
        Route::prefix('leave-balances')->name('leave-balances.')->controller(HrmLeaveBalanceController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:leave_balances.view');
            Route::post('/initialize', 'initialize')->name('initialize')->middleware('permission:leave_balances.initialize');
            Route::post('/carry-forward', 'carryForward')->name('carry-forward')->middleware('permission:leave_balances.carry_forward');
            Route::put('/{leaveBalance}', 'update')->name('update')->middleware('permission:leave_balances.update');
        });

        // ── Leaves ──
        Route::prefix('leaves')->name('leaves.')->controller(HrmLeaveController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:leaves.view');
            Route::get('/create', 'create')->name('create')->middleware('permission:leaves.create');
            Route::post('/', 'store')->name('store')->middleware('permission:leaves.create');
            // Before /{leave} so the static segment wins the match.
            Route::get('/{leave}/document', 'downloadDocument')->name('document')->middleware('permission:leaves.view');
            Route::get('/{leave}', 'show')->name('show')->middleware('permission:leaves.view');
            Route::patch('/{leave}/approve', 'approve')->name('approve')->middleware('permission:leaves.approve');
            Route::patch('/{leave}/reject', 'reject')->name('reject')->middleware('permission:leaves.reject');
            Route::patch('/{leave}/cancel', 'cancel')->name('cancel')->middleware('permission:leaves.cancel');
        });
        Route::get('/leaves/balances/{employee}', [HrmLeaveController::class, 'balances'])
            ->name('leaves.employee-balances')
            ->middleware('permission:leaves.view');

        // ── Salary Components (Single Page CRUD) ──
        Route::resource('salary-components', HrmSalaryComponentController::class)->except(['create', 'show', 'edit'])->middleware('permission:salary_components.view');

        // ── Salary Slips ──
        Route::prefix('salary-slips')->name('salary-slips.')->controller(HrmSalarySlipController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:salary_slips.view');
            Route::get('/{salarySlip}', 'show')->name('show')->middleware('permission:salary_slips.view');
            Route::post('/generate', 'generate')->name('generate')->middleware('permission:salary_slips.generate');
            Route::patch('/{salarySlip}', 'update')->name('update')->middleware('permission:salary_slips.edit');
            Route::patch('/{salarySlip}/approve', 'approve')->name('approve')->middleware('permission:salary_slips.approve');
            Route::patch('/{salarySlip}/pay', 'markPaid')->name('pay')->middleware('permission:salary_slips.mark_paid');
            Route::get('/{salarySlip}/pdf', 'downloadPdf')->name('pdf')->middleware('permission:salary_slips.download_pdf');
            Route::delete('/{salarySlip}', 'destroy')->name('destroy')->middleware('permission:salary_slips.delete');
        });

        // ── Tasks ──
        Route::prefix('tasks')->name('tasks.')->controller(HrmTaskController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:hrm_tasks.view');
            Route::get('/create', 'create')->name('create')->middleware('permission:hrm_tasks.create');
            Route::post('/', 'store')->name('store')->middleware('permission:hrm_tasks.create');
            Route::get('/{task}', 'show')->name('show')->middleware('permission:hrm_tasks.view');
            Route::get('/{task}/edit', 'edit')->name('edit')->middleware('permission:hrm_tasks.update');
            Route::put('/{task}', 'update')->name('update')->middleware('permission:hrm_tasks.update');
            Route::delete('/{task}', 'destroy')->name('destroy')->middleware('permission:hrm_tasks.delete');
            Route::patch('/{task}/status', 'updateStatus')->name('status')->middleware('permission:hrm_tasks.change_status');
            Route::post('/{task}/comments', 'addComment')->name('comments.store')->middleware('permission:hrm_tasks.add_comment');
            // NEW GET route for polling
            Route::get('/{task}/comments', 'getComments')->name('comments.get')->middleware('permission:hrm_tasks.view');
            Route::post('/{task}/attachments', 'addAttachment')->name('attachments.store')->middleware('permission:hrm_tasks.add_attachment');
            Route::get('/attachments/{attachment}/download', 'downloadAttachment')->name('attachments.download')->middleware('permission:hrm_tasks.download_attachment');
            Route::delete('/attachments/{attachment}', 'deleteAttachment')->name('attachments.destroy')->middleware('permission:hrm_tasks.delete_attachment');
        });

        // ── Announcements ──
        // The controller already runs AnnouncementPolicy on every action; this
        // is a coarse outer gate so the module toggle alone is not the door.
        Route::prefix('announcements')->name('announcements.')->middleware('permission:announcements.view')->controller(HrmAnnouncementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            
            Route::get('/{announcement}/download', 'downloadAttachment')->name('download');
            
            Route::get('/{announcement}', 'show')->name('show')->whereNumber('announcement');
            Route::get('/{announcement}/edit', 'edit')->name('edit')->whereNumber('announcement');
            Route::put('/{announcement}', 'update')->name('update')->whereNumber('announcement');
            Route::delete('/{announcement}', 'destroy')->name('destroy')->whereNumber('announcement');

            // Status actions
            Route::patch('/{announcement}/publish', 'publish')->name('publish');
            Route::patch('/{announcement}/unpublish', 'unpublish')->name('unpublish');
            Route::patch('/{announcement}/schedule', 'schedule')->name('schedule');

            // Utilities
            Route::post('/{announcement}/duplicate', 'duplicate')->name('duplicate');
            Route::post('/{id}/restore', 'restore')->name('restore');
        });

        // ── Work Logs ──
        Route::prefix('work-logs')->name('work-logs.')->controller(HrmWorkLogController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:work_logs.view');
            Route::post('/', 'store')->name('store')->middleware('permission:work_logs.view');
            Route::put('/{workLog}', 'update')->name('update')->middleware('permission:work_logs.view');
            Route::delete('/{workLog}', 'destroy')->name('destroy')->middleware('permission:work_logs.view');
            Route::patch('/{workLog}/approve', 'approve')->name('approve')->middleware('permission:work_logs.approve');
        });
    });