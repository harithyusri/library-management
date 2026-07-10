<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Member;
// Shared Controllers (Base or Aliases)
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home')->middleware([\App\Http\Middleware\RoleRedirect::class]);

Route::middleware(['auth', 'verified'])->group(function () {

    // ==========================================
    // ADMIN ROUTES
    // ==========================================
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // --- Book Resource Routes ---
        Route::prefix('books')->name('books.')->group(function () {
            Route::get('/', [Admin\BookController::class, 'index'])->name('index');
            Route::get('/create', [Admin\BookController::class, 'create'])->name('create')->middleware('permission:create books');
            Route::post('/', [Admin\BookController::class, 'store'])->name('store')->middleware('permission:create books');
            Route::get('/{book}', [Admin\BookController::class, 'show'])->name('show');
            Route::get('/{book}/edit', [Admin\BookController::class, 'edit'])->name('edit')->middleware('permission:edit books');
            Route::match(['put', 'patch'], '/{book}', [Admin\BookController::class, 'update'])->name('update')->middleware('permission:edit books');
            Route::delete('/{book}', [Admin\BookController::class, 'destroy'])->name('destroy')->middleware('permission:delete books');

            Route::prefix('{book}/copies')->name('copies.')->group(function () {
                Route::post('/', [Admin\BookController::class, 'storeCopy'])->name('store')->middleware('permission:create book copies');
                Route::put('/{copy}', [Admin\BookController::class, 'updateCopy'])->name('update')->middleware('permission:edit book copies');
                Route::post('/{copy}/generate-qr', [Admin\BookController::class, 'generateCopyQRCode'])->name('generate-qr')->middleware('permission:edit book copies');
                Route::delete('/{copy}', [Admin\BookController::class, 'destroyCopy'])->name('destroy')->middleware('permission:delete book copies');
            });
        });

        Route::get('scan/{barcode}', [Admin\BookController::class, 'scanBarcode'])->name('books.scan');

        // Catalog Management (Admin)
        Route::middleware(['permission:view categories'])->group(function () {
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [Admin\CategoryController::class, 'index'])->name('index');
                Route::post('/store', [Admin\CategoryController::class, 'store'])->name('store');
                Route::put('/update/{category}', [Admin\CategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [Admin\CategoryController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('publishers')->name('publishers.')->group(function () {
                Route::get('/', [Admin\PublisherController::class, 'index'])->name('index');
                Route::post('/store', [Admin\PublisherController::class, 'store'])->name('store');
                Route::put('/update/{publisher}', [Admin\PublisherController::class, 'update'])->name('update');
                Route::delete('/{publisher}', [Admin\PublisherController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('genres')->name('genres.')->group(function () {
                Route::get('/', [Admin\GenreController::class, 'index'])->name('index');
                Route::post('/store', [Admin\GenreController::class, 'store'])->name('store');
                Route::put('/update/{genre}', [Admin\GenreController::class, 'update'])->name('update');
                Route::delete('/{genre}', [Admin\GenreController::class, 'destroy'])->name('destroy');
            });
        });

        // --- Loan/Borrow Routes ---
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', [Admin\LoanController::class, 'index'])->name('index');
            Route::get('/create', [Admin\LoanController::class, 'create'])->name('create');
            Route::get('/{loan}', [Admin\LoanController::class, 'show'])->name('show');
            Route::post('/', [Admin\LoanController::class, 'store'])->name('store');
            Route::post('/{loan}/return', [Admin\LoanController::class, 'return'])->name('return');
        });

        // Staff Management
        Route::prefix('staffs')->middleware(['permission:view users'])->name('staffs.')->group(function () {
            Route::get('/', [Admin\StaffController::class, 'index'])->name('index');
            Route::get('/create', [Admin\StaffController::class, 'create'])->name('create');
            Route::post('/', [Admin\StaffController::class, 'store'])->name('store');
            Route::get('/{user}', [Admin\StaffController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [Admin\StaffController::class, 'edit'])->name('edit');
            Route::put('/{user}', [Admin\StaffController::class, 'update'])->name('update');
            Route::delete('/{user}', [Admin\StaffController::class, 'destroy'])->name('destroy');
        });

        // All Users (Super Admin overview)
        Route::prefix('users')->middleware(['permission:manage roles'])->name('users.')->group(function () {
            Route::get('/', [Admin\UserController::class, 'index'])->name('index');
            Route::get('/{user}', [Admin\UserController::class, 'show'])->name('show');
            Route::patch('/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('/{id}/restore', [Admin\UserController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [Admin\UserController::class, 'forceDelete'])->name('force-delete');
        });

        // Role Management
        Route::prefix('roles')->middleware(['permission:manage roles'])->name('roles.')->group(function () {
            Route::get('/', [Admin\RoleController::class, 'index'])->name('index');
            Route::get('/create', [Admin\RoleController::class, 'create'])->name('create');
            Route::post('/', [Admin\RoleController::class, 'store'])->name('store');
            Route::get('/{role}', [Admin\RoleController::class, 'show'])->name('show');
            Route::get('/{role}/edit', [Admin\RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [Admin\RoleController::class, 'update'])->name('update');
        });

        // Department Management
        Route::prefix('departments')->middleware(['permission:manage roles'])->name('departments.')->group(function () {
            Route::get('/', [Admin\DepartmentController::class, 'index'])->name('index');
            Route::post('/', [Admin\DepartmentController::class, 'store'])->name('store');
            Route::put('/{department}', [Admin\DepartmentController::class, 'update'])->name('update');
            Route::delete('/{department}', [Admin\DepartmentController::class, 'destroy'])->name('destroy');
        });

        // Library Management
        Route::prefix('libraries')->name('libraries.')->group(function () {
            Route::get('/', [Admin\LibraryController::class, 'index'])->name('index');
            Route::get('/create', [Admin\LibraryController::class, 'create'])->name('create');
            Route::post('/', [Admin\LibraryController::class, 'store'])->name('store');
            Route::post('/resolve-map-link', [Admin\LibraryController::class, 'resolveMapLink'])->name('resolve-map-link');
            Route::get('/{library}/edit', [Admin\LibraryController::class, 'edit'])->name('edit');
            Route::put('/{library}', [Admin\LibraryController::class, 'update'])->name('update');
            Route::delete('/{library}', [Admin\LibraryController::class, 'destroy'])->name('destroy');
        });

        // Member Management (Admin side)
        Route::prefix('members')->middleware(['permission:view users'])->name('members.')->group(function () {
            Route::get('/', [Admin\MemberController::class, 'index'])->name('index');
            Route::get('/create', [Admin\MemberController::class, 'create'])->name('create');
            Route::post('/', [Admin\MemberController::class, 'store'])->name('store');
            Route::get('/{user}', [Admin\MemberController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [Admin\MemberController::class, 'edit'])->name('edit');
            Route::put('/{user}', [Admin\MemberController::class, 'update'])->name('update');
            Route::delete('/{user}', [Admin\MemberController::class, 'destroy'])->name('destroy');
        });

        // Room Management
        Route::prefix('rooms')->name('rooms.')->group(function () {
            Route::get('/', [Admin\RoomController::class, 'index'])->name('index');
            Route::get('/create', [Admin\RoomController::class, 'create'])->name('create')->middleware(['permission:create rooms']);
            Route::post('/', [Admin\RoomController::class, 'store'])->name('store')->middleware(['permission:create rooms']);
            Route::get('/{room}', [Admin\RoomController::class, 'show'])->name('show');
            Route::get('/{room}/edit', [Admin\RoomController::class, 'edit'])->name('edit')->middleware(['permission:edit rooms']);
            Route::put('/{room}', [Admin\RoomController::class, 'update'])->name('update')->middleware(['permission:edit rooms']);
            Route::delete('/{room}', [Admin\RoomController::class, 'destroy'])->name('destroy')->middleware(['permission:delete rooms']);
        });

        // Room Bookings (Admin side)
        Route::prefix('room-bookings')->name('room-bookings.')->group(function () {
            Route::get('/', [Admin\RoomBookingController::class, 'index'])->name('index');
            Route::get('/create', [Admin\RoomBookingController::class, 'create'])->name('create');
            Route::post('/', [Admin\RoomBookingController::class, 'store'])->name('store');
            Route::get('/{room_booking}', [Admin\RoomBookingController::class, 'show'])->name('show');
            Route::get('/{room_booking}/edit', [Admin\RoomBookingController::class, 'edit'])->name('edit');
            Route::put('/{room_booking}', [Admin\RoomBookingController::class, 'update'])->name('update');
            Route::patch('/{room_booking}/status', [Admin\RoomBookingController::class, 'updateStatus'])->name('status');
            Route::delete('/{room_booking}', [Admin\RoomBookingController::class, 'destroy'])->name('destroy');
        });

        // Fines (Admin side)
        Route::prefix('fines')->name('fines.')->group(function () {
            Route::get('/', [Admin\FineController::class, 'index'])->name('index');
            Route::patch('/{loan}/pay', [Admin\FineController::class, 'markAsPaid'])->name('pay');
            Route::get('/{loan}/receipt', [Admin\FineController::class, 'downloadReceipt'])->name('receipt');
        });

        // Reports
        Route::get('loan-reports', [Admin\ReportController::class, 'index'])->name('loan-reports.index');
        Route::get('room-reservation-reports', [Admin\ReportController::class, 'index'])->name('room-reservation-reports.index');
        Route::post('reports', [Admin\ReportController::class, 'store'])->name('reports.store');
        Route::get('reports/{report}/status', [Admin\ReportController::class, 'status'])->name('reports.status');
        Route::get('reports/{report}/download', [Admin\ReportController::class, 'download'])->name('reports.download');

        // Audits
        Route::middleware(['permission:view audits'])->prefix('audits')->name('audits.')->group(function () {
            Route::get('/', [Admin\AuditController::class, 'index'])->name('index');
            Route::get('/{audit}', [Admin\AuditController::class, 'show'])->name('show');
        });

        // Announcements (Admin side)
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [Admin\AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [Admin\AnnouncementController::class, 'create'])->name('create')->middleware('permission:create announcements');
            Route::post('/', [Admin\AnnouncementController::class, 'store'])->name('store')->middleware('permission:create announcements');
            Route::get('/{announcement}', [Admin\AnnouncementController::class, 'show'])->name('show');
            Route::get('/{announcement}/edit', [Admin\AnnouncementController::class, 'edit'])->name('edit')->middleware('permission:edit announcements');
            Route::match(['put', 'patch'], '/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('update')->middleware('permission:edit announcements');
            Route::delete('/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('destroy')->middleware('permission:delete announcements');
            Route::post('/upload-image', [Admin\AnnouncementController::class, 'uploadImage'])->name('upload-image')->middleware('permission:create announcements');
        });

        // Maintenance Reports (Admin side)
        Route::prefix('maintenance-reports')->name('maintenance.')->group(function () {
            Route::get('/', [Admin\MaintenanceReportController::class, 'index'])->name('index');
            Route::put('/{maintenance_report}', [Admin\MaintenanceReportController::class, 'update'])->name('update');
            Route::delete('/{maintenance_report}', [Admin\MaintenanceReportController::class, 'destroy'])->name('destroy');
        });
    });

    // ==========================================
    // MEMBER ROUTES
    // ==========================================
    Route::prefix('member')->name('member.')->group(function () {

        Route::get('dashboard', [Member\DashboardController::class, 'index'])->name('dashboard');

        // Catalog (Discovery & Member Borrowing)
        Route::prefix('catalog')->name('catalog.')->group(function () {
            Route::get('/', [Member\CatalogController::class, 'index'])->name('index');
            Route::get('/{book}', [Member\CatalogController::class, 'show'])->name('show');
            Route::post('/{book}/borrow', [Member\CatalogController::class, 'borrow'])->name('borrow');
        });

        // Member Fines
        Route::prefix('fines')->name('fines.')->group(function () {
            Route::get('/', [Member\FineController::class, 'index'])->name('index');
            Route::get('/{loan}', [Member\FineController::class, 'show'])->name('show');
            Route::post('/{loan}/pay', [Member\FineController::class, 'pay'])->name('pay');
            Route::get('/{loan}/success', [Member\FineController::class, 'success'])->name('success');
            Route::get('/{loan}/receipt', [Member\FineController::class, 'downloadReceipt'])->name('receipt');
        });

        // Member Announcements
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [Member\AnnouncementController::class, 'index'])->name('index');
            Route::get('/{announcement}', [Member\AnnouncementController::class, 'show'])->name('show');
        });

        // Member Room Bookings
        Route::prefix('room-bookings')->name('room-bookings.')->group(function () {
            Route::get('/', [Member\RoomBookingController::class, 'index'])->name('index');
            Route::get('/create', [Member\RoomBookingController::class, 'create'])->name('create');
            Route::post('/', [Member\RoomBookingController::class, 'store'])->name('store');
        });

        // Member Maintenance Reports
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [Member\MaintenanceReportController::class, 'index'])->name('index');
            Route::get('/create', [Member\MaintenanceReportController::class, 'create'])->name('create');
            Route::post('/', [Member\MaintenanceReportController::class, 'store'])->name('store');
        });

        // Member Loans
        Route::get('loans', [Member\LoanController::class, 'index'])->name('loans.index');
    });

    // Legacy/Old Dashboard Redirect (Optional, helps if people have old bookmarks)
    Route::get('dashboard', function () {
        return auth()->user()->isMember()
            ? redirect()->route('member.dashboard')
            : redirect()->route('admin.dashboard');
    })->name('dashboard');

});

require __DIR__.'/settings.php';
