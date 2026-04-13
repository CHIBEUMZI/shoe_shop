<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserUpdateRoleRequest;
use App\Http\Requests\UserUpdateStatusRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $search  = trim((string) $request->query('search', ''));
        $role    = $request->query('role', null);      
        $active  = $request->query('is_active', null); 

        $query = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== null && $role !== '', fn ($q) => $q->where('role', $role))
            ->when($active !== null && $active !== '', fn ($q) => $q->where('is_active', (bool) ((int)$active)))
            ->latest('id');

        return UserResource::collection($query->paginate($perPage));
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'birth_date' => $data['birth_date'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return new UserResource($user);
    }
}