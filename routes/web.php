<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\IntakeController;
use App\Http\Controllers\InvitationController;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Middleware\HandleTenant;
use App\Http\Controllers\SlackController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::post('/slack/actions', [SlackController::class, 'handleAction'])->name('slack.actions')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Route::get('/certificates/{certificateId}/download', [IntakeController::class, 'downloadCertificate'])->name('certificates.download');
//     // 1) Show the certificate HTML
//     Route::get('/certificates/{certificateId}', [IntakeController::class, 'showCertificate'])
//         ->name('certificates.show');

Route::middleware('web')->group(function () {
    Route::get('/slack/oauth', [SlackController::class, 'redirectToSlack'])->name('slack.oauth');
    Route::get('/slack/oauth/callback', [SlackController::class, 'handleCallback'])->name('slack.oauth.callback');
    
    // 1. Accept lead in the web UI (local user)
    Route::post('/responses/{id}/accept-lead', [IntakeController::class, 'acceptLead'])
        ->name('responses.acceptLead');

    Route::get('/slack/channels', [SlackController::class, 'listChannels'])
        ->name('slack.channels');
    Route::get('/slack/channel-members', [SlackController::class, 'listChannelMembers'])->name('slack.channel-members');

    // If you also need `/slack/users`:
    Route::get('/slack/users', [SlackController::class, 'listUsers'])
        ->name('slack.users');
});

Route::group([
    'prefix' => '/{tenant}',
    'middleware' => [InitializeTenancyByPath::class, PreventAccessFromCentralDomains::class, HandleTenant::class],
], function () {
    Route::get('/starthere', function () {
        return Inertia::render('IntakeForm'); 
    })->name('starthere');
    
    Route::get('/certificates/{certificateId}/download', [IntakeController::class, 'downloadCertificate'])->name('certificates.download');
    // 1) Show the certificate HTML
    Route::get('/certificates/{certificateId}', [IntakeController::class, 'showCertificate'])
        ->name('certificates.show');

    Route::post('/intake/store', [IntakeController::class, 'store'])->name('intake.store');

    Route::middleware(['auth'])->group(function () {
        Route::get('/responses/kanban', [IntakeController::class, 'kanban'])->name('responses.kanban');
        Route::get('/dashboard', [IntakeController::class, 'dashboard'])->name('dashboard');
        Route::post('/responses/update-stage', [IntakeController::class, 'updateStage'])->name('responses.updateStage');
        Route::get('/responses/changes', [IntakeController::class, 'changes'])->name('responses.changes');
        Route::post('/responses/appraise', [IntakeController::class, 'appraise'])->name('responses.appraise');
        
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('/slack/reset-token', [SlackController::class, 'resetToken'])
            ->name('slack.resetToken');
        Route::post('/responses/{id}/slack-channel', [SlackController::class, 'broadcastToSlackChannel'])->name('responses.broadcastSlackChannel');
        Route::post('/responses/{id}/dm-user', [SlackController::class, 'dmUser'])->name('responses.dmUser');
        
        // Added route for fetching updated FormResponse data
        Route::get('/responses/{id}', [IntakeController::class, 'show'])->name('responses.show');
        Route::post('/responses/broadcast-appraisal', [IntakeController::class, 'broadcastAppraisal'])->name('responses.broadcastAppraisal');
    });  

    Route::get('/invite/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/invite/{token}', [InvitationController::class, 'complete'])->name('invitations.complete');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';