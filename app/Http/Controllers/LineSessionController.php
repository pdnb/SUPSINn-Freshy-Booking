<?php

namespace App\Http\Controllers;

use App\Services\Line\LineIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LineSessionController extends Controller
{
    public function __invoke(Request $request, LineIdentityService $line): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $userId = $line->rememberFromIdToken($validated['id_token']);

        return response()->json([
            'ok' => true,
            'user_id' => $userId,
        ]);
    }
}
