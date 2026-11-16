<?php

use App\Http\Controllers\Admin\IntegrationHealthController;
use App\Http\Controllers\Admin\IntegrationSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadKanbanController;
use App\Http\Controllers\LeadMergeController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialLeadController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebhookMonitorController;
use App\Http\Controllers\WhatsAppInteractionController;
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
    Route::post('customers/{customer}/notes', [NoteController::class, 'storeForCustomer'])
        ->middleware('permission:notes.create')
        ->name('customers.notes.store');

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
    Route::post('leads/{lead}/notes', [NoteController::class, 'storeForLead'])
        ->middleware('permission:notes.create')
        ->name('leads.notes.store');

    Route::delete('notes/{note}', [NoteController::class, 'destroy'])
        ->middleware('permission:notes.update')
        ->name('notes.destroy');

    Route::get('follow-ups', [FollowUpController::class, 'index'])
        ->middleware('permission:follow_ups.view')
        ->name('follow-ups.index');
    Route::get('follow-ups/create', [FollowUpController::class, 'create'])
        ->middleware('permission:follow_ups.create')
        ->name('follow-ups.create');
    Route::post('follow-ups', [FollowUpController::class, 'store'])
        ->middleware('permission:follow_ups.create')
        ->name('follow-ups.store');
    Route::get('follow-ups/{follow_up}/edit', [FollowUpController::class, 'edit'])
        ->middleware('permission:follow_ups.update')
        ->name('follow-ups.edit');
    Route::put('follow-ups/{follow_up}', [FollowUpController::class, 'update'])
        ->middleware('permission:follow_ups.update')
        ->name('follow-ups.update');
    Route::post('follow-ups/{follow_up}/complete', [FollowUpController::class, 'complete'])
        ->middleware('permission:follow_ups.complete')
        ->name('follow-ups.complete');
    Route::post('follow-ups/{follow_up}/cancel', [FollowUpController::class, 'cancel'])
        ->middleware('permission:follow_ups.update')
        ->name('follow-ups.cancel');

    Route::get('tasks', [TaskController::class, 'index'])
        ->middleware('permission:tasks.view')
        ->name('tasks.index');
    Route::get('tasks/create', [TaskController::class, 'create'])
        ->middleware('permission:tasks.create')
        ->name('tasks.create');
    Route::post('tasks', [TaskController::class, 'store'])
        ->middleware('permission:tasks.create')
        ->name('tasks.store');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])
        ->middleware('permission:tasks.update')
        ->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])
        ->middleware('permission:tasks.update')
        ->name('tasks.update');
    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->middleware('permission:tasks.complete')
        ->name('tasks.complete');

    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/health', IntegrationHealthController::class)
            ->middleware('permission:integrations.view')
            ->name('health');
        Route::get('/settings', [IntegrationSettingsController::class, 'index'])
            ->middleware('permission:integrations.view')
            ->name('settings.index');
        Route::get('/settings/{provider}', [IntegrationSettingsController::class, 'edit'])
            ->middleware('permission:integrations.manage')
            ->name('settings.edit');
        Route::put('/settings/{provider}', [IntegrationSettingsController::class, 'update'])
            ->middleware('permission:integrations.manage')
            ->name('settings.update');

        Route::get('/whatsapp', [WhatsAppInteractionController::class, 'index'])
            ->middleware('permission:integrations.view')
            ->name('whatsapp');
        Route::get('/social-leads', [SocialLeadController::class, 'index'])
            ->middleware('permission:integrations.view')
            ->name('social-leads');
        Route::get('/webhooks', [WebhookMonitorController::class, 'index'])
            ->middleware('permission:integrations.view')
            ->name('webhooks.index');
        Route::get('/webhooks/{webhook}', [WebhookMonitorController::class, 'show'])
            ->middleware('permission:integrations.view')
            ->name('webhooks.show');
        Route::post('/webhooks/{webhook}/retry', [WebhookMonitorController::class, 'retry'])
            ->middleware('permission:integrations.manage')
            ->name('webhooks.retry');
    });

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
