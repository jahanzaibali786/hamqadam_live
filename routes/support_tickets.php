<?php

use App\Http\Controllers\SupportCategoryController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

// All support routes previously lacked authentication. Restrict administrative
// access and validate ticket ownership in the controller.
Route::middleware(['auth', 'admin'])->prefix('support')->group(function (): void {
    Route::resource('support-tickets', SupportTicketController::class);
    Route::get('/my-ticket', [SupportTicketController::class, 'my_ticket'])->name('support-tickets.my_ticket');
    Route::get('/solved-ticket', [SupportTicketController::class, 'solved_ticket'])->name('support-tickets.solved_ticket');
    Route::get('/active-ticket', [SupportTicketController::class, 'active_ticket'])->name('support-tickets.active_ticket');
    Route::post('/support-ticket/agent/reply', [SupportTicketController::class, 'ticket_reply'])->name('support-ticket.admin_reply');
    Route::get('/support-ticket/destroy/{id}', [SupportTicketController::class, 'destroy'])->name('support-tickets.destroy');
    Route::get('/default-ticket-assigned-agent', [SupportTicketController::class, 'default_ticket_assigned_agent'])->name('default_ticket_assigned_agent');
    Route::resource('support-categories', SupportCategoryController::class);
    Route::get('/support-categories/destroy/{id}', [SupportCategoryController::class, 'destroy'])->name('support_categories.destroy');
});

Route::middleware('auth')->group(function (): void {
    Route::get('support-ticket/create', [SupportTicketController::class, 'user_ticket_create'])->name('support-tickets.user_ticket_create');
    Route::post('support-ticket/store', [SupportTicketController::class, 'store'])->name('support-ticket.store');
    Route::post('support-ticket/user-reply', [SupportTicketController::class, 'ticket_reply'])->name('support-ticket.user_reply');
    Route::get('support-ticket/history', [SupportTicketController::class, 'user_index'])->name('support-tickets.user_index');
    Route::get('support-ticket/view-details/{id}', [SupportTicketController::class, 'user_view_details'])->name('support-tickets.user_view_details');
    Route::post('support-ticket/{id}/rate', [SupportTicketController::class, 'rate'])->name('support-tickets.rate');
});

// Loaded by routes/web.php after auth.php and admin.php.
require __DIR__.'/hamqadam_client_scope.php';
