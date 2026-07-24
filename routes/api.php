<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookCopyApiController;
use App\Http\Controllers\Api\Member\AnnouncementApiController;
use App\Http\Controllers\Api\Member\CatalogApiController;
use App\Http\Controllers\Api\Member\DashboardApiController;
use App\Http\Controllers\Api\Member\FineApiController;
use App\Http\Controllers\Api\Member\LoanApiController;
use App\Http\Controllers\Api\Member\MaintenanceReportApiController;
use App\Http\Controllers\Api\Member\RoomBookingApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('book-copies/search', [BookCopyApiController::class, 'search'])->name('book-copies.search')->middleware('auth');
Route::get('users/search', [UserApiController::class, 'search'])->name('users.search')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('member')->middleware('member')->group(function () {
        Route::get('/dashboard', [DashboardApiController::class, 'index']);

        Route::get('/catalog', [CatalogApiController::class, 'index']);
        Route::get('/catalog/{book}', [CatalogApiController::class, 'show']);
        Route::post('/catalog/{book}/borrow', [CatalogApiController::class, 'borrow']);

        Route::get('/loans', [LoanApiController::class, 'index']);

        Route::get('/fines', [FineApiController::class, 'index']);
        Route::get('/fines/{loan}', [FineApiController::class, 'show']);
        Route::post('/fines/{loan}/pay', [FineApiController::class, 'pay']);

        Route::get('/room-bookings', [RoomBookingApiController::class, 'index']);
        Route::get('/room-bookings/create', [RoomBookingApiController::class, 'createData']);
        Route::post('/room-bookings', [RoomBookingApiController::class, 'store']);

        Route::get('/announcements', [AnnouncementApiController::class, 'index']);
        Route::get('/announcements/{announcement}', [AnnouncementApiController::class, 'show']);

        Route::get('/maintenance-reports', [MaintenanceReportApiController::class, 'index']);
        Route::get('/maintenance-reports/create', [MaintenanceReportApiController::class, 'createData']);
        Route::post('/maintenance-reports', [MaintenanceReportApiController::class, 'store']);
    });
});
