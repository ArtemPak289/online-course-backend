<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialsException extends Exception
{
    /**
     * Преобразовать исключение в HTTP-ответ.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid login credentials.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
