<?php

use App\Http\Controllers\Admin\QuestionTemplateController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\PracticeController;
use App\Livewire\Admin;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Billing\Checkout;
use App\Livewire\Billing\OrderShow;
use App\Livewire\Billing\OrdersIndex;
use App\Livewire\Billing\PlansIndex;
use App\Livewire\Dashboard;
use App\Livewire\Forum\ForumIndex;
use App\Livewire\Forum\ThreadCreate;
use App\Livewire\Forum\ThreadShow;
use App\Livewire\Learn\LearnCategory;
use App\Livewire\Learn\LearnIndex;
use App\Livewire\Learn\MaterialShow;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Profile\ProfileEdit;
use App\Livewire\Ranking\RankingIndex;
use App\Livewire\Results\ResultReview;
use App\Livewire\Results\ResultShow;
use App\Livewire\Tryout\TryoutIndex;
use App\Livewire\Tryout\TryoutShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::post('/midtrans/webhook', MidtransWebhookController::class)->name('midtrans.webhook');

Route::middleware('guest')->group(function () {
    Route::livewire('/masuk', Login::class)->name('login');
    Route::livewire('/daftar', Register::class)->name('register');
    Route::livewire('/lupa-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// Old English paths used on the landing page.
Route::redirect('/login', '/masuk');
Route::redirect('/register', '/daftar');

Route::middleware(['auth', 'not-blocked'])->group(function () {
    Route::post('/keluar', LogoutController::class)->name('logout');

    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');

    Route::livewire('/belajar', LearnIndex::class)->name('learn.index');
    Route::livewire('/belajar/{category}', LearnCategory::class)->name('learn.category');
    Route::livewire('/materi/{material}', MaterialShow::class)->name('learn.material');

    Route::livewire('/tryout', TryoutIndex::class)->name('tryout.index');
    Route::livewire('/tryout/{package}', TryoutShow::class)->name('tryout.show');
    Route::post('/tryout/{package}/mulai', [ExamController::class, 'start'])->name('exam.start');

    Route::middleware('inertia')->prefix('ujian/{attempt}')->name('exam.')->group(function () {
        Route::get('/', [ExamController::class, 'show'])->name('show');
        Route::post('/jawaban', [ExamController::class, 'answer'])->middleware('throttle:120,1')->name('answer');
        Route::post('/tandai', [ExamController::class, 'flag'])->middleware('throttle:120,1')->name('flag');
        Route::post('/aktivitas', [ExamController::class, 'activity'])->middleware('throttle:30,1')->name('activity');
        Route::post('/selesai', [ExamController::class, 'submit'])->name('submit');
    });

    Route::livewire('/hasil/{attempt}', ResultShow::class)->name('results.show');
    Route::livewire('/hasil/{attempt}/pembahasan', ResultReview::class)->name('results.review');

    Route::livewire('/langganan', PlansIndex::class)->name('plans.index');
    Route::livewire('/langganan/{plan}/checkout', Checkout::class)->name('plans.checkout');
    Route::livewire('/pesanan', OrdersIndex::class)->name('orders.index');
    Route::livewire('/pesanan/{order}', OrderShow::class)->name('orders.show');
    Route::get('/pesanan/{order}/bukti', PaymentProofController::class)->name('orders.proof');

    Route::livewire('/profil', ProfileEdit::class)->name('profile.edit');

    Route::livewire('/forum', ForumIndex::class)->name('forum.index');
    Route::livewire('/forum/buat', ThreadCreate::class)->name('forum.create');
    Route::livewire('/forum/{thread}', ThreadShow::class)->name('forum.thread');

    Route::livewire('/ranking', RankingIndex::class)->name('ranking.index');
    Route::livewire('/notifikasi', NotificationIndex::class)->name('notifications.index');

    Route::post('/latihan/topik/{topic}', PracticeController::class)->name('practice.start');
});

Route::middleware(['auth', 'not-blocked', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', Admin\AdminDashboard::class)->name('dashboard');
    Route::livewire('/hasil', Admin\Attempts\AttemptIndex::class)->name('attempts.index');
    Route::livewire('/hasil/{attempt}', Admin\Attempts\AttemptShow::class)->name('attempts.show');
    Route::livewire('/kategori', Admin\Catalog::class)->name('categories');

    Route::livewire('/materi', Admin\Materials\MaterialIndex::class)->name('materials.index');
    Route::livewire('/materi/buat', Admin\Materials\MaterialForm::class)->name('materials.create');
    Route::livewire('/materi/{material}/ubah', Admin\Materials\MaterialForm::class)->name('materials.edit');

    Route::livewire('/soal', Admin\Questions\QuestionIndex::class)->name('questions.index');
    Route::livewire('/soal/buat', Admin\Questions\QuestionForm::class)->name('questions.create');
    Route::livewire('/soal/import', Admin\Questions\QuestionImport::class)->name('questions.import');
    Route::get('/soal/template', QuestionTemplateController::class)->name('questions.template');
    Route::livewire('/soal/{question}/ubah', Admin\Questions\QuestionForm::class)->name('questions.edit');

    Route::livewire('/paket', Admin\Packages\PackageIndex::class)->name('packages.index');
    Route::livewire('/paket/buat', Admin\Packages\PackageForm::class)->name('packages.create');
    Route::livewire('/paket/{package}/ubah', Admin\Packages\PackageForm::class)->name('packages.edit');

    Route::livewire('/moderasi', Admin\Moderation::class)->name('moderation');
    Route::livewire('/ranking', Admin\RankingManagement::class)->name('ranking');

    Route::middleware('admin:super')->group(function () {
        Route::livewire('/pesanan', Admin\Orders\OrderIndex::class)->name('orders.index');
        Route::livewire('/pesanan/{order}', Admin\Orders\OrderShow::class)->name('orders.show');
        Route::livewire('/langganan', Admin\Plans::class)->name('plans');
        Route::livewire('/voucher', Admin\Vouchers::class)->name('vouchers');
        Route::livewire('/pengguna', Admin\Users\UserIndex::class)->name('users.index');
        Route::livewire('/pengguna/{user}', Admin\Users\UserShow::class)->name('users.show');
    });
});
