<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeacherAddressController extends Controller
{
    public function __construct(private TeacherAddressService $addresses)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        if (! $this->addresses->moduleEnabled()) {
            return response()->json([
                'message' => 'Offline sessions module is not enabled.',
                'data' => ['module_enabled' => false],
            ], 403);
        }

        return response()->json([
            'data' => $this->addresses->index($user->user_id, $this->langId($request, $user)),
        ]);
    }

    public function show(Request $request, int $address): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $row = $this->addresses->find($user->user_id, $address, $this->langId($request, $user));

        if (! $row) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $saved = $this->addresses->save(
                $user->user_id,
                $this->langId($request, $user),
                $validator->validated()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Address saved successfully',
            'data' => $this->addresses->index($user->user_id, $this->langId($request, $user)),
            'address' => $saved,
        ]);
    }

    public function update(Request $request, int $address): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $saved = $this->addresses->save(
                $user->user_id,
                $this->langId($request, $user),
                $validator->validated(),
                $address
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Address saved successfully',
            'data' => $this->addresses->index($user->user_id, $this->langId($request, $user)),
            'address' => $saved,
        ]);
    }

    public function destroy(Request $request, int $address): JsonResponse
    {
        $user = $this->requireTeacher($request);

        try {
            $this->addresses->remove($user->user_id, $address);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Address removed successfully',
            'data' => $this->addresses->index($user->user_id, $this->langId($request, $user)),
        ]);
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^[0-9()\-+{}  ]{4,16}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_id' => ['required', 'integer'],
            'zipcode' => ['required', 'string', 'max:20'],
            'type' => ['required', 'integer', Rule::in([
                TeacherAddressService::TYPE_HOME,
                TeacherAddressService::TYPE_OFFICE,
                TeacherAddressService::TYPE_OTHER,
            ])],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'place_id' => ['nullable', 'string', 'max:255'],
            'place_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    private function requireTeacher(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->user_is_teacher) {
            abort(403, 'Teacher account required.');
        }

        return $user;
    }

    private function langId(Request $request, $user): int
    {
        $langId = $request->integer('lang_id', 0);

        return $langId > 0 ? $langId : (int) ($user->user_lang_id ?: 1);
    }
}
