<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FranchiseUser;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FranchiseUserController extends Controller
{
    public function index(Request $request)
    {
        $query = FranchiseUser::with('franchise:id,name,code');

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($franchiseId = $request->get('franchise_id')) {
            $query->where('franchise_id', $franchiseId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $perPage = $request->get('per_page', 15);
        
        return response()->json($query->orderBy('name')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:franchise_users',
            'password' => 'required|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:owner,manager,staff',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $user = FranchiseUser::create($validated);

        return response()->json($user->load('franchise:id,name'), 201);
    }

    public function show(FranchiseUser $franchiseUser)
    {
        return response()->json($franchiseUser->load('franchise'));
    }

    public function update(Request $request, FranchiseUser $franchiseUser)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:franchise_users,email,' . $franchiseUser->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|in:owner,manager,staff',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $franchiseUser->update($validated);

        return response()->json($franchiseUser->load('franchise:id,name'));
    }

    public function destroy(FranchiseUser $franchiseUser)
    {
        $franchiseUser->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function resetPassword(Request $request, FranchiseUser $franchiseUser)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $franchiseUser->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function getByFranchise(Franchise $franchise)
    {
        return response()->json($franchise->users);
    }
}
