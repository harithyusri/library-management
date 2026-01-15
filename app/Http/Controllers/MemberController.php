<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class MemberController extends Controller
{
    /**
     * Display a listing of members/borrowers.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view users')) {
            abort(403, 'Unauthorized');
        }

        // Only get users with member role
        $query = User::with(['roles', 'activeLoans', 'overdueLoans'])
            ->whereHas('roles', function ($q) {
                $q->where('name', 'member');
            })
            ->withCount(['activeLoans', 'overdueLoans']);

        // Search by name, email, or phone
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by overdue loans
        if ($request->has('has_overdue') && $request->has_overdue === 'yes') {
            $query->has('overdueLoans');
        } elseif ($request->has('has_overdue') && $request->has_overdue === 'no') {
            $query->doesntHave('overdueLoans');
        }

        $query->orderBy('created_at', 'desc');

        $members = $query->paginate(15)->withQueryString();

        // Add total fines to each member
        $members->getCollection()->transform(function ($member) {
            $member->total_fines = $member->getTotalUnpaidFines();
            return $member;
        });

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status', 'has_overdue']),
            'can' => [
                'createUsers' => $request->user()->can('create users'),
                'editUsers' => $request->user()->can('edit users'),
                'deleteUsers' => $request->user()->can('delete users'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new member.
     */
    public function create(Request $request)
    {
        if (!$request->user()->can('create users')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Members/Create');
    }

    /**
     * Store a newly created member.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        // Always assign member role
        $user->assignRole('member');

        return redirect()->route('members.show', $user->id)
            ->with('success', 'Member created successfully!');
    }

    /**
     * Display the specified member.
     */
    public function show(Request $request, User $user)
    {
        if (!$request->user()->can('view users')) {
            abort(403, 'Unauthorized');
        }

        // Ensure user is a member
        if (!$user->hasRole('member')) {
            abort(404);
        }

        $user->load(['roles', 'activeLoans.bookCopy.book', 'overdueLoans.bookCopy.book']);

        return Inertia::render('Members/Show', [
            'member' => $user,
            'stats' => [
                'total_loans' => $user->loans()->count(),
                'active_loans' => $user->activeLoans()->count(),
                'overdue_loans' => $user->overdueLoans()->count(),
                'total_fines' => $user->getTotalUnpaidFines(),
            ],
            'can' => [
                'editUsers' => $request->user()->can('edit users'),
                'deleteUsers' => $request->user()->can('delete users'),
                'createLoans' => $request->user()->can('create loans'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified member.
     */
    public function edit(Request $request, User $user)
    {
        if (!$request->user()->can('edit users')) {
            abort(403, 'Unauthorized');
        }

        // Ensure user is a member
        if (!$user->hasRole('member')) {
            abort(404);
        }

        $user->load('roles');

        return Inertia::render('Members/Edit', [
            'member' => $user,
        ]);
    }

    /**
     * Update the specified member.
     */
    public function update(Request $request, User $user)
    {
        if (!$request->user()->can('edit users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return redirect()->route('members.show', $user->id)
            ->with('success', 'Member updated successfully!');
    }

    /**
     * Remove the specified member.
     */
    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->can('delete users')) {
            abort(403, 'Unauthorized');
        }

        // Ensure user is a member
        if (!$user->hasRole('member')) {
            abort(404);
        }

        // Prevent deleting member with active loans
        if ($user->activeLoans()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete member with active loans.');
        }

        $user->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully!');
    }
}