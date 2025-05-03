<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $admins = User::where('is_admin', true)->latest()->get();
        return response()->json($admins);
    }

    public function indexCustomers(Request $request): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customers = User::where('is_admin', false)->latest()->get();
        return response()->json($customers);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_admin'] = true;
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->markEmailAsVerified();
        return response()->json($user, 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }
}
