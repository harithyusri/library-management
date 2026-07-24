<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * JSON search for users (used by room booking form).
     */
    public function search(Request $request)
    {
        $users = User::where(function ($q) use ($request) {
                $search = $request->q ?? $request->search ?? '';
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json(['data' => $users]);
    }

    /**
     * Display a paginated list of all users (all roles).
     */
    public function index(Request $request): Response
    {
        $query = User::withTrashed()
            ->with('roles')
            ->withCount(['loans', 'roomBookings']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->whereNull('deleted_at')->where('status', $request->status);
            }
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admins/Users/Index', [
            'users' => $users->through(fn ($user) => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'status'         => $user->status,
                'deleted_at'     => $user->deleted_at?->toIso8601String(),
                'created_at'     => $user->created_at->toDateString(),
                'roles'          => $user->roles->pluck('name'),
                'loans_count'    => $user->loans_count,
                'bookings_count' => $user->room_bookings_count,
            ]),
            'roles'   => Role::orderBy('name')->pluck('name'),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Show a single user's full profile.
     */
    public function show(User $user): Response
    {
        $user->load(['roles', 'member', 'staff', 'loans.bookCopy.book', 'roomBookings.room']);

        return Inertia::render('admins/Users/Show', [
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'status'     => $user->status,
                'created_at' => $user->created_at->toDateString(),
                'roles'      => $user->roles->pluck('name'),
                'member'     => $user->member,
                'staff'      => $user->staff,
                'loans'      => $user->loans->map(fn ($l) => [
                    'id'            => $l->id,
                    'book_title'    => $l->bookCopy?->book?->title ?? 'Unknown',
                    'borrowed_date' => $l->borrowed_date?->toDateString(),
                    'due_date'      => $l->due_date?->toDateString(),
                    'status'        => $l->status,
                ]),
                'room_bookings' => $user->roomBookings->map(fn ($b) => [
                    'id'           => $b->id,
                    'room_name'    => $b->room?->name ?? 'Unknown',
                    'booking_date' => $b->booking_date?->toDateString(),
                    'status'       => $b->status,
                ]),
            ],
        ]);
    }

    /**
     * Toggle a user's active / inactive status.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "User {$user->name} has been " . ($user->fresh()->status === 'active' ? 'activated' : 'deactivated') . '.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        $user->update(['status' => 'active']);

        return back()->with('success', "User {$user->name} has been restored.");
    }

    /**
     * Permanently delete a user (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $name = $user->name;
        $user->forceDelete();

        return back()->with('success', "User {$name} has been permanently deleted.");
    }
}
