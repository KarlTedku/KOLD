<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.callback');

Route::post('/auth/demo', [SocialAuthController::class, 'demoLogin'])->name('auth.demo');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SocialAuthController::class, 'logout'])->name('logout');

    Route::get('/onboarding/role', [OnboardingController::class, 'role'])->name('onboarding.role');
    Route::post('/onboarding/role', [OnboardingController::class, 'storeRole'])->name('onboarding.role.store');

    Route::middleware(EnsureUserHasRole::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/ai-draft', [ProfileController::class, 'aiDraft'])->name('profile.ai');

        Route::get('/social', [SocialAccountController::class, 'index'])->name('social.index');
        Route::post('/social/manual', [SocialAccountController::class, 'storeManual'])->name('social.manual');
        Route::get('/social/connect/{platform}', [SocialAccountController::class, 'redirectConnect'])->name('social.connect');
        Route::delete('/social/{platform}', [SocialAccountController::class, 'destroy'])->name('social.destroy');

        Route::get('/discover', [DiscoverController::class, 'index'])->name('discover.index');
        Route::get('/u/{user}', [DiscoverController::class, 'show'])->name('discover.show');

        Route::get('/inbox', [ContactController::class, 'inbox'])->name('contact.inbox');
        Route::post('/u/{user}/contact', [ContactController::class, 'store'])->name('contact.store');
        Route::post('/contact/{contactRequest}/accept', [ContactController::class, 'accept'])->name('contact.accept');
        Route::post('/contact/{contactRequest}/decline', [ContactController::class, 'decline'])->name('contact.decline');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
    });
});
