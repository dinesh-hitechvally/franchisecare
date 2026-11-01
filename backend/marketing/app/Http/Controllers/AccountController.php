<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The signed-in person's own account (name, sign-in email, password). Other people are managed under Users. */
class AccountController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:40',
            'job_title' => 'nullable|string|max:120',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'current_password' => 'nullable|string',
        ]);

        // changing how you sign in needs proof that it is really you
        if (($data['password'] ?? null) || strcasecmp($data['email'], $user->email) !== 0) {
            if (! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Enter your current password to change your email or password.']);
            }
        }

        $user->name = $data['name'];
        $user->phone = ($data['phone'] ?? '') ?: null;
        $user->job_title = ($data['job_title'] ?? '') ?: null;
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id ?? 0)->delete(); // signs out every other device
        }
        $user->save();

        return ['user' => $user->only('id', 'name', 'email', 'phone', 'job_title'), 'company' => $user->company->only('id', 'name')];
    }
}
