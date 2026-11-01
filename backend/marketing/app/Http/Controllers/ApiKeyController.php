<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Long-lived keys for external systems. They carry the "integration" ability and only work on /api/v1. */
class ApiKeyController extends Controller
{
    protected function tokens(Request $request)
    {
        $userIds = User::where('company_id', $request->user()->company_id)->pluck('id');

        return PersonalAccessToken::where('tokenable_type', User::class)->whereIn('tokenable_id', $userIds)->where('name', 'like', 'api:%');
    }

    public function index(Request $request)
    {
        return $this->tokens($request)->latest('id')->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => substr($t->name, 4), 'last_used_at' => $t->last_used_at, 'created_at' => $t->created_at]);
    }

    public function store(Request $request)
    {
        $name = $request->validate(['name' => 'required|string|max:80'])['name'];
        $token = $request->user()->createToken('api:'.$name, ['integration']);

        // plain-text key is returned once and never stored
        return response(['id' => $token->accessToken->id, 'name' => $name, 'key' => $token->plainTextToken], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $this->tokens($request)->whereKey($id)->firstOrFail()->delete();

        return response()->noContent();
    }
}
