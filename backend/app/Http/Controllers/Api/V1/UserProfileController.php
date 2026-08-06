<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserProfileMediaService;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profiles,
        private UserProfileMediaService $media
    ) {
    }

    public function showGeneral(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->profiles->getGeneralForm(
                $user->user_id,
                $this->langId($request, $user),
                (bool) $user->user_is_teacher
            ),
        ]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $user = $request->user();
        $isTeacher = (bool) $user->user_is_teacher;

        $rules = [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'integer', 'in:1,2,3,4'],
            'country_id' => ['required', 'integer', 'min:1'],
            'phone_code' => ['required', 'integer', 'min:1'],
            'phone_number' => ['required', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'max:64'],
            'lang_id' => ['required', 'integer', 'min:1'],
        ];

        if ($isTeacher) {
            $rules['username'] = ['required', 'string', 'min:6', 'max:60'];
            $rules['book_before'] = ['required', 'integer', 'in:0,12,24'];
            $rules['offline_sessions'] = ['sometimes', 'boolean'];
            $rules['trial_enabled'] = ['sometimes', 'boolean'];
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->profiles->updateGeneral($user->user_id, $validator->validated(), $isTeacher);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $this->profiles->getGeneralForm(
                $user->user_id,
                $this->langId($request, $user),
                $isTeacher
            ),
        ]);
    }

    public function showPhotos(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->media->getPhotosForm($user->user_id, (bool) $user->user_is_teacher),
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'user_profile_image' => ['required', 'file'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->media->saveProfileImage(
                $user->user_id,
                $request->file('user_profile_image'),
                (bool) $user->user_is_teacher
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Photo saved successfully.',
            'data' => $this->media->getPhotosForm($user->user_id, (bool) $user->user_is_teacher),
        ]);
    }

    public function removePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->media->removeProfileImage($user->user_id);

        return response()->json([
            'message' => 'Profile image removed.',
            'data' => $this->media->getPhotosForm($user->user_id, (bool) $user->user_is_teacher),
        ]);
    }

    public function showLanguage(Request $request, int $langId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        try {
            $data = $this->profiles->getLanguageForm(
                $user->user_id,
                $langId,
                (bool) $user->user_is_teacher
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function updateLanguage(Request $request, int $langId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validator = Validator::make($request->all(), [
            'biography' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->profiles->updateLanguageBio(
                $user->user_id,
                $langId,
                $validator->validated()['biography']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Profile language saved successfully.',
            'data' => $this->profiles->getLanguageForm(
                $user->user_id,
                $langId,
                (bool) $user->user_is_teacher
            ),
        ]);
    }

    public function updatePhotos(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'video_link' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->media->updateVideoLink(
                $user->user_id,
                $request->input('video_link'),
                (bool) $user->user_is_teacher
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully.',
            'data' => $this->media->getPhotosForm($user->user_id, (bool) $user->user_is_teacher),
        ]);
    }

    private function langId(Request $request, $user): int
    {
        $langId = $request->integer('lang_id', 0);

        return $langId > 0 ? $langId : (int) ($user->user_lang_id ?: 1);
    }
}
