<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherAccountController extends Controller
{
    public function __construct(private TeacherAccountService $account)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        return response()->json([
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
            'experience_types' => $this->account->getExperienceTypes(),
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        return response()->json([
            'data' => $this->account->getProfileProgress($user->user_id),
        ]);
    }

    public function updateLanguages(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        $validator = Validator::make($request->all(), [
            'teach_lang_ids' => ['required', 'array', 'min:1'],
            'teach_lang_ids.*' => ['integer'],
            'speak_languages' => ['required', 'array', 'min:1'],
            'speak_languages.*.slang_id' => ['required', 'integer'],
            'speak_languages.*.proficiency' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->account->updateLanguages(
                $user->user_id,
                $this->langId($request, $user),
                $request->input('teach_lang_ids', []),
                $request->input('speak_languages', [])
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully',
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
        ]);
    }

    public function updatePrices(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        $validator = Validator::make($request->all(), [
            'prices' => ['nullable', 'array'],
            'prices.*' => ['numeric', 'min:0'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->account->updatePrices(
                $user->user_id,
                $request->input('prices', []),
                $request->input('slots', [])
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully',
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        $validator = Validator::make($request->all(), [
            'preference_ids' => ['present', 'array'],
            'preference_ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->account->updatePreferences($user->user_id, $request->input('preference_ids', []));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully',
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
        ]);
    }

    public function storeQualification(Request $request): JsonResponse
    {
        return $this->saveQualification($request);
    }

    public function updateQualification(Request $request, int $qualification): JsonResponse
    {
        return $this->saveQualification($request, $qualification);
    }

    public function deleteQualification(Request $request, int $qualification): JsonResponse
    {
        $user = $this->requireTeacher($request);

        try {
            $this->account->deleteQualification($user->user_id, $qualification);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'message' => 'Removed successfully',
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
        ]);
    }

    private function saveQualification(Request $request, ?int $id = null): JsonResponse
    {
        $user = $this->requireTeacher($request);

        $validator = Validator::make($request->all(), [
            'experience_type' => ['required', 'integer', 'in:1,2,3'],
            'title' => ['required', 'string', 'max:100'],
            'institute_name' => ['required', 'string', 'max:100'],
            'institute_address' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_year' => ['required', 'integer', 'min:1970', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:1970', 'max:2100', 'gte:start_year'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $qualification = $this->account->saveQualification($user->user_id, $validator->validated(), $id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully',
            'qualification' => $qualification,
            'data' => $this->account->getProfile($user->user_id, $this->langId($request, $user)),
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
