<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function roles(): JsonResponse
    {
        return response()->json(['data' => Role::orderBy('name')->get(['id', 'name'])]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']]);
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%');
                });
            })
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->with([
                'role',
                'bidang',
                'subBidang',
                'satker',
            ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['nullable', 'exists:satkers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($user->id === $request->user()->id && ((int) $validated['role_id'] !== $user->role_id || (array_key_exists('is_active', $validated) && ! $validated['is_active']))) {
            return response()->json(['message' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan atau diubah perannya.'], 422);
        }

        $user->update($validated);

        $user->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin yang sedang login tidak dapat dihapus.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }
}
