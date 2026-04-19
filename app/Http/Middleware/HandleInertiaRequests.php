<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'is_member' => $request->user()?->isMember() ?? false,
                'is_staff'  => $request->user()?->isStaff() ?? false,
                'can' => $request->user() ? [
                    'view_dashboard' => $request->user()->can('view dashboard'),

                    'view_users'   => $request->user()->can('view users'),
                    'create users' => $request->user()->can('create users'),
                    'edit users' => $request->user()->can('edit users'),
                    'delete users' => $request->user()->can('delete users'),

                    'view_books' => $request->user()->can('view books'),
                    'create_books' => $request->user()->can('create books'),
                    'edit_books'   => $request->user()->can('edit books'),
                    'delete_books' => $request->user()->can('delete books'),

                    'view_book_copies' => $request->user()->can('view book copies'),
                    'create_book_copies' => $request->user()->can('create book copies'),
                    'edit_book_copies'   => $request->user()->can('edit book copies'),
                    'delete_book_copies' => $request->user()->can('delete book copies'),

                    'view_loans'   => $request->user()->can('view loans'),
                    'create_loans' => $request->user()->can('create loans'),
                    'return_loans' => $request->user()->can('return loans'),
                    'delete_loans' => $request->user()->can('delete loans'),

                    'view_categories' => $request->user()->can('view categories'),
                    'create_categories' => $request->user()->can('create categories'),
                    'edit_categories' => $request->user()->can('edit categories'),
                    'delete_categories' => $request->user()->can('delete categories'),

                    'view_genres' => $request->user()->can('view genres'),
                    'create_genres' => $request->user()->can('create genres'),
                    'edit_genres' => $request->user()->can('edit genres'),
                    'delete_genres' => $request->user()->can('delete genres'),

                    'view_publishers' => $request->user()->can('view publishers'),
                    'create_publishers' => $request->user()->can('create publishers'),
                    'edit_publishers' => $request->user()->can('edit publishers'),
                    'delete_publishers' => $request->user()->can('delete publishers'),

                    'view_fines'   => $request->user()->can('view fines'),
                    'waive_fines' => $request->user()->can('waive fines'),
                    'collect_fines' => $request->user()->can('collect fines'),

                    'view_reports' => $request->user()->can('view reports'),
                    'export_reports' => $request->user()->can('export reports'),

                    'manage_settings' => $request->user()->can('manage settings'),
                    'manage_roles' => $request->user()->can('manage roles'),
                    'manage_permissions' => $request->user()->can('manage permissions'),

                    'view_rooms' => $request->user()->can('view rooms'),
                    'create_rooms' => $request->user()->can('create rooms'),
                    'edit_rooms' => $request->user()->can('edit rooms'),
                    'delete_rooms' => $request->user()->can('delete rooms'),

                    'view_room_bookings' => $request->user()->can('view room bookings'),
                    'create_room_bookings' => $request->user()->can('create room bookings'),
                    'edit_room_bookings' => $request->user()->can('edit room bookings'),
                    'delete_room_bookings' => $request->user()->can('delete room bookings'),
                    'cancel_room_bookings' => $request->user()->can('cancel room bookings'),

                    'view_reservations' => $request->user()->can('view reservations'),
                    'create_reservations' => $request->user()->can('create reservations'),
                    'cancel_reservations' => $request->user()->can('cancel reservations'),

                    'view_audits' => $request->user()->can('view audits'),

                    'view_announcements' => $request->user()->can('view announcements'),
                    'create_announcements' => $request->user()->can('create announcements'),
                    'edit_announcements' => $request->user()->can('edit announcements'),
                    'delete_announcements' => $request->user()->can('delete announcements'),
                ] : [],
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
