<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailLogController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->homeRoute())
        : redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Setup wizard
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->controller(InstallController::class)->group(function () {
    Route::get('/', 'welcome')->name('welcome');
    Route::get('database', 'database')->name('database');
    Route::post('database', 'migrate')->name('migrate');
    Route::get('setup', 'setup')->name('setup');
    Route::post('setup', 'finish')->name('finish');
});

/*
|--------------------------------------------------------------------------
| Public utilities: language, PWA, web cron, Google OAuth
|--------------------------------------------------------------------------
*/
Route::get('locale/{locale}', LocaleController::class)->name('locale');
Route::get('manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('offline', [PwaController::class, 'offline'])->name('pwa.offline');
Route::match(['get', 'post'], 'cron/{token}', CronController::class)->middleware('throttle:10,1')->name('cron.run');

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('preferences', PreferenceController::class)->name('preferences');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Back office: administrators, staff and sellers
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,staff,seller')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('search', SearchController::class)->name('search');

        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

        Route::get('kanban', [KanbanController::class, 'index'])->name('kanban.index');
        Route::post('kanban/move', [KanbanController::class, 'move'])->name('kanban.move');
        Route::post('kanban/cards', [KanbanController::class, 'storeCard'])->name('kanban.cards.store');
        Route::put('kanban/cards/{card}', [KanbanController::class, 'updateCard'])->name('kanban.cards.update');
        Route::delete('kanban/cards/{card}', [KanbanController::class, 'destroyCard'])->name('kanban.cards.destroy');
        Route::post('kanban/columns', [KanbanController::class, 'storeColumn'])->middleware('role:admin,staff')->name('kanban.columns.store');
        Route::put('kanban/columns/{column}', [KanbanController::class, 'updateColumn'])->middleware('role:admin,staff')->name('kanban.columns.update');
        Route::delete('kanban/columns/{column}', [KanbanController::class, 'destroyColumn'])->middleware('role:admin,staff')->name('kanban.columns.destroy');

        Route::resource('customers', CustomerController::class)
            ->middlewareFor(['destroy'], 'role:admin,staff');
        Route::post('customers/{customer}/portal-access', [CustomerController::class, 'portalAccess'])->middleware('role:admin,staff')->name('customers.portal-access');
        Route::post('customers/{customer}/ai-analysis', [CustomerController::class, 'aiAnalysis'])->middleware('role:admin,staff')->name('customers.ai-analysis');

        Route::resource('subscriptions', SubscriptionController::class)
            ->middlewareFor(['destroy'], 'role:admin,staff');
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->middleware('role:admin,staff')->name('subscriptions.renew');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('subscriptions.pause');
        Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
        Route::post('subscriptions/{subscription}/remind', [SubscriptionController::class, 'remind'])->name('subscriptions.remind');

        Route::resource('invoices', InvoiceController::class)
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->middleware('role:admin,staff')->name('invoices.send');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->middleware('role:admin,staff')->name('invoices.duplicate');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('role:admin,staff')->name('invoices.cancel');

        Route::resource('payments', PaymentController::class)
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');
        Route::post('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->middleware('role:admin,staff')->name('payments.receipt');

        Route::get('my-commissions', [SellerController::class, 'me'])->middleware('role:seller')->name('sellers.me');
    });

    /*
    |----------------------------------------------------------------------
    | Staff only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,staff')->group(function () {
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

        Route::resource('categories', ProductCategoryController::class)->except(['show', 'create', 'edit'])
            ->parameters(['categories' => 'category']);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('sellers', SellerController::class);
        Route::resource('currencies', CurrencyController::class)->except(['show', 'create', 'edit']);
        Route::post('currencies/{currency}/default', [CurrencyController::class, 'makeDefault'])->name('currencies.default');

        Route::get('email-logs', [EmailLogController::class, 'index'])->name('email-logs.index');
        Route::get('email-logs/{emailLog}', [EmailLogController::class, 'show'])->name('email-logs.show');

        Route::prefix('ai')->name('ai.')->controller(AiAssistantController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('conversations', 'store')->name('conversations.store');
            Route::get('conversations/{conversation}', 'show')->name('conversations.show');
            Route::delete('conversations/{conversation}', 'destroy')->name('conversations.destroy');
            Route::post('conversations/{conversation}/messages', 'message')->middleware('throttle:30,1')->name('conversations.message');
            Route::get('insights', 'insights')->name('insights');
            Route::post('insights', 'generateInsights')->middleware('throttle:10,1')->name('insights.generate');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Administration
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings/{tab?}', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{tab}', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/mail/test', [SettingController::class, 'testMail'])->name('settings.test-mail');
        Route::post('settings/gemini/test', [SettingController::class, 'testGemini'])->name('settings.test-gemini');
        Route::post('settings/cron/run', [SettingController::class, 'runCron'])->name('settings.run-cron');
        Route::post('settings/cron/token', [SettingController::class, 'regenerateCronToken'])->name('settings.cron-token');

        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('email-templates', EmailTemplateController::class)->only(['index', 'edit', 'update'])
            ->parameters(['email-templates' => 'emailTemplate']);
        Route::post('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
        Route::post('email-templates/{emailTemplate}/ai', [EmailTemplateController::class, 'aiDraft'])->middleware('throttle:10,1')->name('email-templates.ai');
        Route::resource('custom-fields', CustomFieldController::class)->except(['show'])
            ->parameters(['custom-fields' => 'customField']);

        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('backups/upload', [BackupController::class, 'upload'])->name('backups.upload');
        Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });

    /*
    |----------------------------------------------------------------------
    | Customer portal
    |----------------------------------------------------------------------
    */
    Route::middleware('role:customer')->prefix('portal')->name('portal.')->controller(PortalController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('subscriptions', 'subscriptions')->name('subscriptions');
        Route::get('subscriptions/{subscription}', 'subscription')->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/auto-renew', 'toggleAutoRenew')->name('subscriptions.auto-renew');
        Route::post('subscriptions/{subscription}/cancel', 'cancel')->name('subscriptions.cancel');
        Route::get('invoices', 'invoices')->name('invoices');
        Route::get('invoices/{invoice}', 'invoice')->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', 'invoicePdf')->name('invoices.pdf');
        Route::get('payments', 'payments')->name('payments');
    });
});

require __DIR__.'/auth.php';
