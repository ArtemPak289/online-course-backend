<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserManagementService $userService
    ) {}

    /**
     * Отобразить список пользователей.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $filters = array_filter([
            'role' => $request->input('role'),
            'is_blocked' => $request->input('is_blocked'),
        ], fn ($val) => ! is_null($val) && $val !== '');

        $users = $this->userService->getPaginatedUsers(
            $filters,
            $request->integer('per_page', 15)
        );

        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * Обновить роль пользователя.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateRole(
            $user,
            $request->validated('role')
        );

        return response()->json([
            'message' => 'User role updated successfully.',
            'user' => $updatedUser,
        ], Response::HTTP_OK);
    }

    /**
     * Обновить статус блокировки пользователя.
     */
    public function updateBlockStatus(BlockUserRequest $request, User $user): JsonResponse
    {
        $isBlocked = $request->boolean('is_blocked');

        $updatedUser = $this->userService->updateBlockStatus($user, $isBlocked);

        $message = $isBlocked ? 'User blocked successfully.' : 'User unblocked successfully.';

        return response()->json([
            'message' => $message,
            'user' => $updatedUser,
        ], Response::HTTP_OK);
    }
}
