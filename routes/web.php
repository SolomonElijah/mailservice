<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\SchedulerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProviderController;

/*
|--------------------------------------------------------------------------
| Fully Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/track/open/{token}',  [TrackingController::class, 'open'])->name('track.open');
Route::get('/track/click/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe',         [ContactController::class, 'unsubscribe'])->name('unsubscribe');

Route::get('/contacts/csv-template', function () {
    $callback = function () {
        $h = fopen('php://output', 'w');
        fputcsv($h, ['email', 'name', 'company']);
        fputcsv($h, ['alice@example.com', 'Alice Johnson', 'Acme Corp']);
        fputcsv($h, ['bob@example.com', 'Bob Smith', '']);
        fclose($h);
    };
    return response()->stream($callback, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="contacts-template.csv"',
    ]);
})->name('contacts.template.download');

/*
|--------------------------------------------------------------------------
| Resend Webhook (no CSRF)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/resend', [WebhookController::class, 'resend'])
    ->name('webhooks.resend')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/register',  [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
    Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',        [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    // Dashboard & Logs
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/logs',      [DashboardController::class, 'logs'])->name('logs.index');

    // Analytics
    Route::get('/analytics',       [TrackingController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/setup', fn() => view('analytics.webhook-setup'))->name('analytics.webhook-setup');

    // Providers
    Route::get('/providers',          [ProviderController::class, 'index'])->name('providers.index');
    Route::post('/providers/test',    [ProviderController::class, 'test'])->name('providers.test');
    Route::post('/providers/send-test', [ProviderController::class, 'sendTest'])->name('providers.send-test');

    // Email Sender
    Route::get('/mailer',         [EmailController::class, 'index'])->name('email.index');
    Route::post('/send/single',   [EmailController::class, 'sendSingle'])->name('email.send.single');
    Route::post('/send/multiple', [EmailController::class, 'sendMultiple'])->name('email.send.multiple');
    Route::post('/send/advanced', [EmailController::class, 'sendAdvanced'])->name('email.send.advanced');

    // Scheduler
    Route::get('/scheduler',                  [SchedulerController::class, 'index'])->name('scheduler.index');
    Route::post('/scheduler',                 [SchedulerController::class, 'store'])->name('scheduler.store');
    Route::patch('/scheduler/{email}/cancel', [SchedulerController::class, 'cancel'])->name('scheduler.cancel');
    Route::patch('/scheduler/{email}/retry',  [SchedulerController::class, 'retry'])->name('scheduler.retry');
    Route::delete('/scheduler/{email}',       [SchedulerController::class, 'destroy'])->name('scheduler.destroy');

    // Templates
    Route::get('/templates',                    [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create',             [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates',                   [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit',    [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}',         [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}',      [TemplateController::class, 'destroy'])->name('templates.destroy');
    Route::get('/templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
    Route::post('/templates/seed', function () {
        \App\Http\Controllers\TemplateController::seedDefaults(auth()->id());
        return back()->with('success', '5 built-in templates loaded!');
    })->name('templates.seed');

    // Campaigns
    Route::get('/campaigns',                       [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create',                [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns',                      [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}',            [CampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/campaigns/{campaign}/edit',       [CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}',            [CampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}',         [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('/campaigns/{campaign}/send',      [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');

    // Contacts
    Route::get('/contacts',                  [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts/lists',           [ContactController::class, 'storeList'])->name('contacts.list.store');
    Route::patch('/contacts/lists/{list}',   [ContactController::class, 'updateList'])->name('contacts.list.update');
    Route::delete('/contacts/lists/{list}',  [ContactController::class, 'destroyList'])->name('contacts.list.destroy');
    Route::get('/contacts/{list}',                       [ContactController::class, 'show'])->name('contacts.show');
    Route::post('/contacts/{list}/contacts',             [ContactController::class, 'storeContact'])->name('contacts.store');
    Route::patch('/contacts/{list}/contacts/{contact}',  [ContactController::class, 'updateContact'])->name('contacts.update');
    Route::delete('/contacts/{list}/contacts/{contact}', [ContactController::class, 'destroyContact'])->name('contacts.destroy');
    Route::post('/contacts/{list}/bulk',                 [ContactController::class, 'bulkAction'])->name('contacts.bulk');
    Route::get('/contacts/{list}/import',                [ContactController::class, 'importForm'])->name('contacts.import.form');
    Route::post('/contacts/{list}/import',               [ContactController::class, 'import'])->name('contacts.import');
    Route::get('/contacts/{list}/export',                [ContactController::class, 'export'])->name('contacts.export');

    // Profile
    Route::get('/profile',            [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/admin/stop-impersonating', [AdminController::class, 'stopImpersonating'])->name('admin.stop-impersonating');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',        [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users',   [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}',            [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}',           [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('impersonate');
    Route::get('/queues',        [AdminController::class, 'queues'])->name('queues');
    Route::post('/queues/retry', [AdminController::class, 'retryJob'])->name('queues.retry');
    Route::post('/queues/flush', [AdminController::class, 'flushFailed'])->name('queues.flush');
    Route::get('/settings',              [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings/clear-cache', [AdminController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/clear-logs',  [AdminController::class, 'clearLogs'])->name('settings.clear-logs');
});
