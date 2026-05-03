<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Force password change (authenticated but exempt from ForcePasswordChange middleware)
Route::middleware('auth')->group(function () {
    Route::get('/force-password-change', [AuthController::class, 'showForcePasswordChange'])->name('password.force-change');
    Route::post('/force-password-change', [AuthController::class, 'forcePasswordChange']);
});

// Authenticated routes (force password change check on all)
Route::middleware(['auth', 'password.force_change'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect('/dashboard');
    });

    // Kanban Board (dashboard)
    Route::get('/dashboard', [TicketController::class, 'kanban'])->name('dashboard');
    Route::get('/api/board-data', [TicketController::class, 'boardData'])->name('board.data');
    Route::post('/tickets/{ticket}/move-column', [TicketController::class, 'moveColumn'])->name('tickets.move-column');
    Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');

    // Tickets
    Route::post('/tickets/create-internal', [TicketController::class, 'storeInternal'])->name('tickets.store-internal');
    Route::get('/tickets/archived', [TicketController::class, 'archived'])->name('tickets.archived');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');
    Route::post('/tickets/{ticket}/comment/{comment}/edit', [TicketController::class, 'editComment'])->name('tickets.edit-comment');
    Route::post('/tickets/{ticket}/comment/{comment}/delete', [TicketController::class, 'deleteComment'])->name('tickets.delete-comment');
    Route::post('/tickets/{ticket}/upload-file', [TicketController::class, 'uploadFile'])->name('tickets.upload-file');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/dev-fields', [TicketController::class, 'updateDevFields'])->name('tickets.dev-fields');
    Route::post('/tickets/{ticket}/update-content', [TicketController::class, 'updateContent'])->name('tickets.update-content');
    Route::post('/tickets/{ticket}/pr-links', [TicketController::class, 'addPrLink'])->name('tickets.add-pr-link');
    Route::post('/tickets/{ticket}/pr-links/{link}/delete', [TicketController::class, 'removePrLink'])->name('tickets.remove-pr-link');
    Route::post('/tickets/{ticket}/commits', [TicketController::class, 'addCommit'])->name('tickets.add-commit');
    Route::post('/tickets/{ticket}/commits/{commit}/delete', [TicketController::class, 'removeCommit'])->name('tickets.remove-commit');
    Route::post('/tickets/{ticket}/sp-files', [TicketController::class, 'uploadSpFile'])->name('tickets.upload-sp-file');
    Route::post('/tickets/{ticket}/sp-files/{file}/delete', [TicketController::class, 'removeSpFile'])->name('tickets.remove-sp-file');
    Route::post('/tickets/{ticket}/classify-override', [TicketController::class, 'classifyOverride'])->name('tickets.classify-override');
    Route::post('/tickets/{ticket}/archive', [TicketController::class, 'archive'])->name('tickets.archive');
    Route::post('/tickets/{ticket}/delete', [TicketController::class, 'destroy'])->name('tickets.destroy');

    // Labels
    Route::get('/labels', [TicketController::class, 'labels'])->name('labels.index');
    Route::post('/labels', [TicketController::class, 'createLabel'])->name('labels.store');
    Route::post('/labels/{label}/delete', [TicketController::class, 'deleteLabel'])->name('labels.destroy');
    Route::post('/tickets/{ticket}/labels', [TicketController::class, 'syncLabels'])->name('tickets.sync-labels');

    // Clients CRUD
    Route::resource('clients', ClientController::class)->except(['show', 'destroy']);
    Route::post('/clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])->name('clients.toggle-active');
    Route::post('/clients/{client}/regenerate-key', [ClientController::class, 'regenerateKey'])->name('clients.regenerate-key');
    Route::get('/clients/{client}/test-connection', [ClientController::class, 'testConnection'])->name('clients.test-connection');

    // Team CRUD
    Route::resource('team', TeamController::class)->except(['show', 'destroy'])->parameters(['team' => 'member']);
    Route::post('/team/{member}/toggle-active', [TeamController::class, 'toggleActive'])->name('team.toggle-active');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/data', [ReportController::class, 'data'])->name('reports.data');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Notifications (mark-all-read must be before the {id} route)
    Route::get('/notifications/unread-count', [TicketController::class, 'unreadNotificationCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-all-read', [TicketController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/read', [TicketController::class, 'markNotificationRead'])->name('notifications.read');

    // Team members search (for @mentions)
    Route::get('/api/team-members/search', [TeamController::class, 'search'])->name('team.search');
});
