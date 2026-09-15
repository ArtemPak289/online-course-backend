<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        $users = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * Update the user's role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->update([
            'role' => $request->validated('role'),
        ]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'user' => $user->fresh(),
        ], Response::HTTP_OK);
    }

    /**
     * Update the user's blocked status.
     */
    public function updateBlockStatus(BlockUserRequest $request, User $user): JsonResponse
    {
        $isBlocked = $request->boolean('is_blocked');

        $user->update([
            'is_blocked' => $isBlocked,
        ]);

        if ($isBlocked) {
            // Revoke active tokens when blocking a user
            $user->tokens()->delete();
        }

        $message = $isBlocked ? 'User blocked successfully.' : 'User unblocked successfully.';

        return response()->json([
            'message' => $message,
            'user' => $user->fresh(),
        ], Response::HTTP_OK);
    }
}
