<?php

use App\Http\Controllers\DisputeController;
use App\Http\Controllers\EvidenceAttachmentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketResponseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Feedback & Support Routes
|--------------------------------------------------------------------------
|
| Here is where you can register feedback and support related routes for your application.
| These routes handle feedback submissions, support tickets, disputes, and attachments.
|
*/

// Feedback routes
Route::middleware(['auth'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/feedback/create', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');
    Route::put('/feedback/{feedback}', [FeedbackController::class, 'update'])->name('feedback.update');
    Route::post('/feedback/{feedback}/attachments', [FeedbackController::class, 'uploadAttachment'])->name('feedback.attachments.upload');
    Route::get('/feedback/attachments/{attachment}/download', [FeedbackController::class, 'downloadAttachment'])->name('feedback.attachments.download');
    Route::delete('/feedback/attachments/{attachment}', [FeedbackController::class, 'deleteAttachment'])->name('feedback.attachments.delete');
});

// Support ticket routes
Route::middleware(['auth'])->group(function () {
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::put('/support/{ticket}', [SupportTicketController::class, 'update'])->name('support.update');
    Route::post('/support/{ticket}/attachments', [SupportTicketController::class, 'uploadAttachment'])->name('support.attachments.upload');
    Route::get('/support/attachments/{attachment}/download', [SupportTicketController::class, 'downloadAttachment'])->name('support.attachments.download');
    Route::delete('/support/attachments/{attachment}', [SupportTicketController::class, 'deleteAttachment'])->name('support.attachments.delete');
});

// Ticket response routes
Route::middleware(['auth'])->group(function () {
    Route::post('/support/{ticket}/responses', [TicketResponseController::class, 'store'])->name('responses.store');
    Route::put('/responses/{response}', [TicketResponseController::class, 'update'])->name('responses.update');
    Route::delete('/responses/{response}', [TicketResponseController::class, 'destroy'])->name('responses.destroy');
    Route::post('/responses/{response}/send', [TicketResponseController::class, 'sendNow'])->name('responses.send');
    Route::post('/responses/{response}/attachments', [TicketResponseController::class, 'uploadAttachment'])->name('responses.attachments.upload');
    Route::get('/responses/attachments/{attachment}/download', [TicketResponseController::class, 'downloadAttachment'])->name('responses.attachments.download');
    Route::delete('/responses/attachments/{attachment}', [TicketResponseController::class, 'deleteAttachment'])->name('responses.attachments.delete');
});

// Dispute routes
Route::middleware(['auth'])->group(function () {
    Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/create', [DisputeController::class, 'create'])->name('disputes.create');
    Route::post('/disputes', [DisputeController::class, 'store'])->name('disputes.store');
    Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::put('/disputes/{dispute}', [DisputeController::class, 'update'])->name('disputes.update');
    Route::post('/disputes/{dispute}/contact', [DisputeController::class, 'contactParties'])->name('disputes.contact');
    Route::post('/disputes/{dispute}/attachments', [DisputeController::class, 'uploadAttachment'])->name('disputes.attachments.upload');
    Route::get('/disputes/attachments/{attachment}/download', [DisputeController::class, 'downloadAttachment'])->name('disputes.attachments.download');
    Route::delete('/disputes/attachments/{attachment}', [DisputeController::class, 'deleteAttachment'])->name('disputes.attachments.delete');
});

// General attachment routes
Route::middleware(['auth'])->group(function () {
    Route::post('/attachments', [EvidenceAttachmentController::class, 'upload'])->name('attachments.upload');
    Route::get('/attachments/{attachment}/download', [EvidenceAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [EvidenceAttachmentController::class, 'delete'])->name('attachments.delete');
});
