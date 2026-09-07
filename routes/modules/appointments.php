<?php
use Illuminate\Support\Facades\Route;
// Appointment Controllers
use App\Http\Controllers\Admin\Appointment\AppointmentController;
use App\Http\Controllers\Admin\Appointment\AppointmentServiceController;
use App\Http\Controllers\Admin\Appointment\AppointmentSlotController;

Route::middleware('module:appointments')->group(function () {
    // ===================== Appointments =====================
    Route::prefix('appointments')
        ->name('appointments.')
        ->controller(AppointmentController::class)
        ->group(function () {

            Route::get('/', 'index')->middleware('permission:appointments.view')->name('index');
            Route::get('/{appointment}', 'show')->middleware('permission:appointments.view')->name('show');

            Route::patch('/{appointment}/confirm', 'confirm')->middleware('permission:appointments.update')->name('confirm');
            Route::patch('/{appointment}/complete', 'complete')->middleware('permission:appointments.update')->name('complete');
            Route::patch('/{appointment}/cancel', 'cancel')->middleware('permission:appointments.update')->name('cancel');
    });


    // ===================== Appointment Services =====================
    Route::prefix('appointment-services')
        ->name('appointments.services.')
        ->controller(AppointmentServiceController::class)
        ->group(function () {

            Route::get('/', 'index') ->middleware('permission:appointment_services.view')->name('index');
            Route::post('/', 'store')->middleware('permission:appointment_services.create')->name('store');
            Route::put('/{appointmentService}', 'update')->middleware('permission:appointment_services.update')->name('update');
            Route::delete('/{appointmentService}', 'destroy')->middleware('permission:appointment_services.delete')->name('destroy');
    });


    // ===================== Appointment Slots =====================
    Route::prefix('appointment-slots')
        ->name('appointments.slots.')
        ->controller(AppointmentSlotController::class)
        ->group(function () {

            Route::get('/', 'index')->middleware('permission:appointment_slots.view')->name('index');
            Route::post('/', 'store')->middleware('permission:appointment_slots.create')->name('store');
            Route::put('/{appointmentSlot}', 'update')->middleware('permission:appointment_slots.update')->name('update');
            Route::delete('/{appointmentSlot}', 'destroy')->middleware('permission:appointment_slots.delete')->name('destroy');
    });
});