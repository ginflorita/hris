<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DefaultRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->string('q')->trim()->value(), fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $request->string('q')->value()]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::orderBy('name')->get(), 'employees' => $this->linkableEmployees()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_id' => ['nullable', Rule::exists('employees', 'id'), Rule::unique('users', 'employee_id')],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_id' => ($validated['employee_id'] ?? null) ?: null,
            // Nobody chooses this password — it's immediately replaced by
            // the reset link sent below, so the creator never learns it.
            'password' => Hash::make(Str::random(40)),
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.index')
            ->with('status', "User created — a password setup link was emailed to {$user->email}.");
    }

    public function edit(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.edit', [
            'targetUser' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
            'employees' => $this->linkableEmployees($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'employee_id' => ['nullable', Rule::exists('employees', 'id'), Rule::unique('users', 'employee_id')->ignore($user->id)],
        ]);

        $validated['employee_id'] = ($validated['employee_id'] ?? null) ?: null;

        $user->update($validated);

        return back()->with('status', 'Profile updated.');
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $this->authorize('assignRoles', $user);

        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);
        $newRoles = $validated['roles'] ?? [];

        $losingSuperadmin = $user->hasRole(DefaultRole::Superadmin->value)
            && ! in_array(DefaultRole::Superadmin->value, $newRoles, true);

        // A rejected checkbox submission is an expected UI state, not an
        // access-control bypass attempt — Gate::denies() + a form error
        // instead of authorize()'s 403 page.
        if ($losingSuperadmin && Gate::denies('removeSuperadminRole', $user)) {
            return back()->withErrors(['roles' => 'The Superadmin role cannot be removed from this account.']);
        }

        $user->syncRoles($newRoles);

        return back()->with('status', 'Roles updated.');
    }

    public function disable(User $user): RedirectResponse
    {
        $this->authorize('disable', $user);

        $user->update(['disabled_at' => now()]);
        $this->destroyAllSessions($user);

        return back()->with('status', 'User disabled.');
    }

    public function enable(User $user): RedirectResponse
    {
        $this->authorize('enable', $user);

        $user->update(['disabled_at' => null]);

        return back()->with('status', 'User enabled.');
    }

    public function forceLogout(User $user): RedirectResponse
    {
        $this->authorize('forceLogout', $user);

        $this->destroyAllSessions($user);

        return back()->with('status', 'User logged out of all sessions.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', "Password reset link emailed to {$user->email}.");
    }

    private function destroyAllSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * Employees not already linked to a different login account -- plus
     * $user's own current link, if any, so it still shows as selected.
     *
     * @return Collection<int, Employee>
     */
    private function linkableEmployees(?User $user = null): Collection
    {
        return Employee::query()
            ->whereDoesntHave('user', fn ($query) => $query->when($user, fn ($q) => $q->whereKeyNot($user->id)))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
