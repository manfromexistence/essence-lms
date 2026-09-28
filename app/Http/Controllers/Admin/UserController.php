<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with('roles');

        if (request()->filled('search')) {
            $search = request('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->paginate(15);

        return view('dashboard.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();

        return view('dashboard.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($request->role);

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('roles');

        return view('dashboard.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $user->load('roles');

        return view('dashboard.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|exists:roles,id',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => ['confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        $targetRole = Role::findOrFail($request->role);
        if ($user->isSuperAdmin() && $targetRole->slug !== 'super-admin' && $this->superAdminCount() <= 1) {
            return back()->with('error', 'The last super administrator cannot be demoted.');
        }
        $user->roles()->sync([$targetRole->id]);

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->with('error', 'The last super administrator cannot be deleted.');
        }

        // Some tables reference users with a RESTRICTING foreign key
        // (inventory_transactions.created_by, certificates.issued_by), so
        // deleting a user who has that history raised a raw QueryException and
        // surfaced as a 500. Report it as a normal validation-style error and
        // point the operator at deactivation instead.
        try {
            DB::transaction(function () use ($user) {
                $user->roles()->detach();
                $user->delete();
            });
        } catch (QueryException $e) {
            return back()->with(
                'error',
                'This user cannot be deleted because other records still reference them '
                .'(for example inventory transactions or issued certificates). '
                .'Deactivate the account instead of deleting it.'
            );
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function superAdminCount(): int
    {
        return User::whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))->count();
    }
}
