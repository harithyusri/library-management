<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

        // Get users with member role and their member profiles
        $query = User::with(['roles', 'member', 'activeLoans', 'overdueLoans'])
            ->whereHas('roles', function ($q) {
                $q->where('name', 'member');
            })
            ->withCount(['activeLoans', 'overdueLoans']);

        // Search by name, email, phone, or library card number
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhereHas('member', function ($memberQuery) use ($request) {
                      $memberQuery->where('library_card_number', 'like', "%{$request->search}%");
                  });
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
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
            
            // Member profile fields
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['nullable', 'string', 'max:500'],
            'library_card_number' => ['nullable', 'string', 'max:50', 'unique:members'],
            'membership_start_date' => ['nullable', 'date'],
            'membership_expiry_date' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'in:standard,premium,student,senior'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'max_books_allowed' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_days_allowed' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'email_verified_at' => now(),
            ]);

            // Assign member role
            $user->assignRole('member');

            // Create member profile
            $user->member()->create([
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'library_card_number' => $validated['library_card_number'] ?? null,
                'membership_start_date' => $validated['membership_start_date'] ?? now(),
                'membership_expiry_date' => $validated['membership_expiry_date'] ?? now()->addYear(),
                'membership_type' => $validated['membership_type'] ?? 'standard',
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'max_books_allowed' => $validated['max_books_allowed'] ?? 5,
                'max_days_allowed' => $validated['max_days_allowed'] ?? 14,
            ]);

            DB::commit();

            return redirect()->route('members.show', $user->id)
                ->with('success', 'Member created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create member: ' . $e->getMessage());
        }
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

        $user->load(['roles', 'member', 'activeLoans.bookCopy.book', 'overdueLoans.bookCopy.book']);

        return Inertia::render('Members/Show', [
            'member' => $user,
            'memberProfile' => $user->member,
            'stats' => [
                'total_loans' => $user->loans()->count(),
                'active_loans' => $user->activeLoans()->count(),
                'overdue_loans' => $user->overdueLoans()->count(),
                'total_fines' => $user->getTotalUnpaidFines(),
                'remaining_books' => $user->member?->remaining_books ?? 0,
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

        $user->load(['roles', 'member']);

        return Inertia::render('Members/Edit', [
            'member' => $user,
            'memberProfile' => $user->member,
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
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
            
            // Member profile fields
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['nullable', 'string', 'max:500'],
            'library_card_number' => ['nullable', 'string', 'max:50', 'unique:members,library_card_number,' . $user->member->id],
            'membership_start_date' => ['nullable', 'date'],
            'membership_expiry_date' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'in:standard,premium,student,senior'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'max_books_allowed' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_days_allowed' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        DB::beginTransaction();

        try {
            // Update user
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            // Update password if provided
            if (!empty($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            // Update member profile
            $user->member->update([
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'library_card_number' => $validated['library_card_number'],
                'membership_start_date' => $validated['membership_start_date'],
                'membership_expiry_date' => $validated['membership_expiry_date'],
                'membership_type' => $validated['membership_type'] ?? 'standard',
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'max_books_allowed' => $validated['max_books_allowed'] ?? 5,
                'max_days_allowed' => $validated['max_days_allowed'] ?? 14,
            ]);

            DB::commit();

            return redirect()->route('members.show', $user->id)
                ->with('success', 'Member updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update member: ' . $e->getMessage());
        }
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