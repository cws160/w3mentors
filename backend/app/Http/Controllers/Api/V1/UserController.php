<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:50'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'lang_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('first_name')) {
            $user->user_first_name = $request->string('first_name');
        }
        if ($request->has('last_name')) {
            $user->user_last_name = $request->input('last_name');
        }
        if ($request->has('timezone')) {
            $user->user_timezone = $request->string('timezone');
        }
        if ($request->has('lang_id')) {
            $user->user_lang_id = $request->integer('lang_id');
        }

        $user->save();

        return response()->json(['data' => new UserResource($user)]);
    }
}
