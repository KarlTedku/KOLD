<?php

use App\Http\Controllers\AiTagController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BrandMatchController;
use App\Http\Controllers\BrandProjectController;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataDeletionController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\KolCardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectApplicationController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/beta', 'beta.index')->name('beta.index');

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::get('/data-deletion', [DataDeletionController::class, 'instructions'])->name('data-deletion.instructions');
Route::post('/data-deletion/meta', [DataDeletionController::class, 'meta'])->name('data-deletion.meta');
Route::get('/data-deletion/status/{code}', [DataDeletionController::class, 'status'])->name('data-deletion.status');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.callback');

Route::post('/auth/demo', [SocialAuthController::class, 'demoLogin'])->name('auth.demo');

Route::get('/k/{slug}', [KolCardController::class, 'show'])->name('kol-card.show');
Route::get('/projects', [BrandProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [BrandProjectController::class, 'show'])->name('projects.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SocialAuthController::class, 'logout'])->name('logout');
    Route::get('/account/delete', [DataDeletionController::class, 'account'])->name('account.delete');
    Route::delete('/account', [DataDeletionController::class, 'destroy'])->name('account.destroy');

    Route::get('/onboarding/role', [OnboardingController::class, 'role'])->name('onboarding.role');
    Route::post('/onboarding/role', [OnboardingController::class, 'storeRole'])->name('onboarding.role.store');

    Route::middleware(EnsureUserHasRole::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        Route::post('/profile/avatar/meta', [ProfileController::class, 'useMetaAvatar'])->name('profile.avatar.meta');
        Route::post('/profile/ai-draft', [ProfileController::class, 'aiDraft'])->name('profile.ai');
        Route::put('/profile/card', [KolCardController::class, 'update'])->name('kol-card.update');
        Route::get('/profile/card/preview', [KolCardController::class, 'preview'])->name('kol-card.preview');
        Route::post('/profile/card/publish', [KolCardController::class, 'publish'])->name('kol-card.publish');
        Route::post('/profile/card/unpublish', [KolCardController::class, 'unpublish'])->name('kol-card.unpublish');
        Route::post('/profile/card/links', [KolCardController::class, 'storeLink'])->name('kol-card.links.store');
        Route::post('/profile/card/social-links', [KolCardController::class, 'storeSocialLinks'])->name('kol-card.links.social');
        Route::put('/profile/card/links/{kolCardLink}', [KolCardController::class, 'updateLink'])->name('kol-card.links.update');
        Route::delete('/profile/card/links/{kolCardLink}', [KolCardController::class, 'destroyLink'])->name('kol-card.links.destroy');
        Route::post('/profile/ai-tags/generate', [AiTagController::class, 'generate'])->name('ai-tags.generate');
        Route::post('/profile/ai-tags/approve-all', [AiTagController::class, 'approveAll'])->name('ai-tags.approve-all');
        Route::post('/profile/ai-tags/{kolAiTag}/approve', [AiTagController::class, 'approve'])->name('ai-tags.approve');
        Route::delete('/profile/ai-tags/{kolAiTag}', [AiTagController::class, 'reject'])->name('ai-tags.reject');

        Route::get('/social', [SocialAccountController::class, 'index'])->name('social.index');
        Route::post('/social/manual', [SocialAccountController::class, 'storeManual'])->name('social.manual');
        Route::get('/social/connect/{platform}', [SocialAccountController::class, 'redirectConnect'])->name('social.connect');
        Route::get('/social/meta/select', [SocialAccountController::class, 'selectMetaAccounts'])->name('social.meta.select');
        Route::post('/social/meta/select', [SocialAccountController::class, 'storeMetaAccounts'])->name('social.meta.store');
        Route::delete('/social/accounts/{socialAccount}', [SocialAccountController::class, 'destroy'])->name('social.destroy');

        Route::get('/discover', [DiscoverController::class, 'index'])->name('discover.index');
        Route::get('/match', BrandMatchController::class)->name('match.index');
        Route::get('/u/{user}', [DiscoverController::class, 'show'])->name('discover.show');

        Route::get('/brand/projects', [BrandProjectController::class, 'manage'])->name('projects.manage');
        Route::get('/brand/projects/create', [BrandProjectController::class, 'create'])->name('projects.create');
        Route::post('/brand/projects', [BrandProjectController::class, 'store'])->name('projects.store');
        Route::get('/brand/projects/{project}/edit', [BrandProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/brand/projects/{project}', [BrandProjectController::class, 'update'])->name('projects.update');
        Route::post('/brand/projects/{project}/publish', [BrandProjectController::class, 'publish'])->name('projects.publish');
        Route::post('/brand/projects/{project}/close', [BrandProjectController::class, 'close'])->name('projects.close');
        Route::get('/brand/projects/{project}/applications', [ProjectApplicationController::class, 'index'])->name('projects.applications');
        Route::post('/projects/{project}/apply', [ProjectApplicationController::class, 'store'])->name('projects.apply');
        Route::delete('/applications/{application}', [ProjectApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::post('/applications/{application}/accept', [ProjectApplicationController::class, 'accept'])->name('applications.accept');
        Route::post('/applications/{application}/decline', [ProjectApplicationController::class, 'decline'])->name('applications.decline');

        Route::get('/inbox', [ContactController::class, 'inbox'])->name('contact.inbox');
        Route::post('/u/{user}/contact', [ContactController::class, 'store'])->name('contact.store');
        Route::post('/contact/{contactRequest}/accept', [ContactController::class, 'accept'])->name('contact.accept');
        Route::post('/contact/{contactRequest}/decline', [ContactController::class, 'decline'])->name('contact.decline');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
        Route::put('/collaborations/{collaboration}/status', [CollaborationController::class, 'update'])->name('collaborations.update');
    });
});
