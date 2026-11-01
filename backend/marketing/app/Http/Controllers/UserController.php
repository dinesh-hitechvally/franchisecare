<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The people of the signed-in user's company. Everyone is a full admin; there are no roles.
 * A user created here always joins the creator's company — the company is never taken from the request.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        return User::where('company_id', $request->user()->company_id)->orderBy('id')->get(['id', 'name', 'email', 'created_at']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($data + ['company_id' => $request->user()->company_id]);

        return response($user->only('id', 'name', 'email'), 201);
    }

    public function update(Request $request, User $user)
    {
        $this->sameCompany($request, $user);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
        ]);

        $user->fill(collect($data)->except('password')->all());
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->tokens()->delete(); // a changed password signs that person out everywhere
        }
        $user->save();

        return $user->only('id', 'name', 'email');
    }

    public function destroy(Request $request, User $user)
    {
        $this->sameCompany($request, $user);
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }

    /** Someone from another company is simply "not found". */
    protected function sameCompany(Request $request, User $user): void
    {
        abort_unless($user->company_id === $request->user()->company_id, 404);
    }
}
