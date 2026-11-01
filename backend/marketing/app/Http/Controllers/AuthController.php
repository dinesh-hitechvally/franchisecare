<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data) {
            $company = Company::create(['name' => $data['company_name'], 'from_name' => $data['company_name']]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'company_id' => $company->id,
            ]);
        });

        return $this->session($user);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            if ($user) {
                $this->audit($user, 'login_failed', 'Failed sign-in', $request);
            }
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        $this->audit($user, 'login', 'Signed in', $request);

        return $this->session($user);
    }

    public function me(Request $request)
    {
        return $this->payload($request->user());
    }

    public function logout(Request $request)
    {
        $this->audit($request->user(), 'logout', 'Signed out', $request);
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    protected function session(User $user)
    {
        return ['token' => $user->createToken('spa')->plainTextToken] + $this->payload($user);
    }

    protected function audit(User $user, string $action, string $text, Request $request): void
    {
        \App\Models\ActivityLog::create(['company_id' => $user->company_id, 'user_id' => $user->id, 'user_name' => $user->name, 'action' => $action, 'description' => $text, 'ip' => $request->ip()]);
    }

    protected function payload(User $user): array
    {
        return ['user' => $user->only('id', 'name', 'email', 'phone', 'job_title'), 'company' => $user->company->only('id', 'name') + ['logo_url' => \App\Models\Media::logoUrl($user->company)]];
    }
}
