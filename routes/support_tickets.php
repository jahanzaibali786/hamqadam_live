<?php

use App\Http\Controllers\SupportCategoryController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'support'], function(){

	Route::resource('support-tickets', SupportTicketController::class);
	Route::controller(SupportTicketController::class)->group(function () {
		Route::get('/my-ticket', 'my_ticket')->name('support-tickets.my_ticket');
		Route::get('/solved-ticket', 'solved_ticket')->name('support-tickets.solved_ticket');
		Route::get('/active-ticket', 'active_ticket')->name('support-tickets.active_ticket');
		Route::post('support-ticket/agent/reply', 'ticket_reply')->name('support-ticket.admin_reply');
		Route::get('/support-ticket/destroy/{id}', 'destroy')->name('support-tickets.destroy');

		// deafult staff for assigning ticket
		Route::get('/default-ticket-assigned-agent', 'default_ticket_assigned_agent')->name('default_ticket_assigned_agent');

	});
	Route::resource('support-categories',SupportCategoryController::class);
	Route::controller(SupportCategoryController::class)->group(function () {
		// Support categories
		Route::get('/support-categories/destroy/{id}', 'destroy')->name('support_categories.destroy');
	});
});

Route::controller(SupportTicketController::class)->group(function () {
	Route::get('support-ticket/create', 'user_ticket_create')->name('support-tickets.user_ticket_create');
	Route::post('support-ticket/store', 'store')->name('support-ticket.store');
	Route::post('support-ticket/user-reply', 'ticket_reply')->name('support-ticket.user_reply');
	Route::get('support-ticket/history', 'user_index')->name('support-tickets.user_index');
	Route::get('support-ticket/view-details/{id}', 'user_view_details')->name('support-tickets.user_view_details');
});

?>
