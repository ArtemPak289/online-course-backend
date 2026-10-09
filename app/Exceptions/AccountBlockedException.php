<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountBlockedException extends Exception
{
    /**
     * Преобразовать исключение в HTTP-ответ.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Your account has been blocked.',
        ], Response::HTTP_FORBIDDEN);
    }
}
