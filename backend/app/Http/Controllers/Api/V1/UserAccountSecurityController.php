<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserAccountSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserAccountSecurityController extends Controller
{
    public function __construct(private UserAccountSecurityService $security)
    {
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'max:15'],
            'conf_new_password' => ['required', 'string', 'same:new_password'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->security->changePassword(
                $request->user(),
                $request->string('current_password')->toString(),
                $request->string('new_password')->toString(),
                $request->string('conf_new_password')->toString()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function changeEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_email' => ['required', 'email', 'max:255'],
            'current_password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $message = $this->security->requestEmailChange(
                $request->user(),
                $request->string('new_email')->toString(),
                $request->string('current_password')->toString()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message]);
    }
}
