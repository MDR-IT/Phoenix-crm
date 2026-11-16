<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadKanbanController;
use App\Http\Controllers\LeadMergeController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.create')
        ->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')
        ->name('customers.show');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.update')
        ->name('customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.update')
        ->name('customers.update');

    Route::get('leads', [LeadController::class, 'index'])
        ->middleware('role_or_permission:leads.view|leads.manage')
        ->name('leads.index');
    Route::get('leads/kanban', LeadKanbanController::class)
        ->middleware('role_or_permission:leads.view|leads.manage')
        ->name('leads.kanban');
    Route::get('leads/create', [LeadController::class, 'create'])
        ->middleware('role_or_permission:leads.create|leads.manage')
        ->name('leads.create');
    Route::post('leads', [LeadController::class, 'store'])
        ->middleware('role_or_permission:leads.create|leads.manage')
        ->name('leads.store');
    Route::get('leads-export', [LeadController::class, 'export'])
        ->middleware('role_or_permission:leads.export|leads.manage')
        ->name('leads.export');

    Route::get('leads/{lead}', [LeadController::class, 'show'])
        ->middleware('role_or_permission:leads.view|leads.manage')
        ->name('leads.show');
    Route::get('leads/{lead}/edit', [LeadController::class, 'edit'])
        ->middleware('role_or_permission:leads.update|leads.manage')
        ->name('leads.edit');
    Route::put('leads/{lead}', [LeadController::class, 'update'])
        ->middleware('role_or_permission:leads.update|leads.manage')
        ->name('leads.update');
    Route::patch('leads/{lead}/status', [LeadStatusController::class, 'update'])
        ->middleware('role_or_permission:leads.update|leads.manage')
        ->name('leads.status');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])
        ->middleware('role_or_permission:leads.archive|leads.manage')
        ->name('leads.destroy');
    Route::post('leads/{lead}/restore', [LeadController::class, 'restore'])
        ->middleware('role_or_permission:leads.archive|leads.manage')
        ->name('leads.restore');
    Route::post('leads/{lead}/assign', [LeadAssignmentController::class, 'store'])
        ->middleware('role_or_permission:leads.assign|leads.manage')
        ->name('leads.assign');
    Route::get('leads/{lead}/merge', [LeadMergeController::class, 'create'])
        ->middleware('permission:leads.merge')
        ->name('leads.merge');
    Route::post('leads/{lead}/merge', [LeadMergeController::class, 'store'])
        ->middleware('permission:leads.merge')
        ->name('leads.merge.store');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:users.view')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
        });

        Route::middleware('permission:users.create')->group(function () {
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
        });

        Route::middleware('permission:users.update')->group(function () {
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        });

        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        Route::middleware('permission:roles.view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        });

        Route::middleware('permission:roles.update')->group(function () {
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
    });
});

require __DIR__.'/auth.php';
