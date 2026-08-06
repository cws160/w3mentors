<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\LegacyPasswordHasher;
use DateTime;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    private const TYPE_ADMIN_PROFILE_IMAGE = 15;

    private const MAX_PROFILE_IMAGE_BYTES = 4194304;

    private const PROFILE_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $admin = Admin::active()
            ->where('admin_username', $request->string('username'))
            ->first();

        if (!$admin || !$admin->validateLegacyPassword($request->string('password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-api', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'admin' => [
                'id' => $admin->admin_id,
                'username' => $admin->admin_username,
                'name' => $admin->admin_name,
                'email' => $admin->admin_email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json([
            'admin' => [
                'id' => $admin->admin_id,
                'username' => $admin->admin_username,
                'name' => $admin->admin_name,
                'email' => $admin->admin_email,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json([
            'data' => [
                'id' => $admin->admin_id,
                'username' => $admin->admin_username,
                'full_name' => $admin->admin_name,
                'email' => $admin->admin_email,
                'timezone' => $admin->admin_timezone ?: $this->defaultTimezone(),
            ],
            'default_timezone' => $this->defaultTimezone(),
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $admin->admin_name = trim((string) $request->input('full_name'));
        $admin->admin_timezone = trim((string) $request->input('timezone'));
        $admin->save();

        return response()->json([
            'message' => 'Setup successful',
            'admin' => [
                'id' => $admin->admin_id,
                'username' => $admin->admin_username,
                'name' => $admin->admin_name,
                'email' => $admin->admin_email,
            ],
        ]);
    }

    public function uploadProfileImage(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $file = $request->file('user_profile_image');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'Please select a file'], 422);
        }

        if ($file->getSize() > self::MAX_PROFILE_IMAGE_BYTES) {
            return response()->json(['message' => 'File size should be less than 4 MB.'], 422);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::PROFILE_IMAGE_EXTENSIONS, true)) {
            return response()->json(['message' => 'Invalid file extension.'], 422);
        }

        $uploadRoot = base_path('../user-uploads');
        $relativeDir = date('Y') . '/' . date('m') . '/';
        $absoluteDir = $uploadRoot . '/' . $relativeDir;
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $original = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName()) ?: 'profile.jpg';
        $fileName = $original;
        while (is_file($absoluteDir . $fileName)) {
            $fileName = time() . '-' . $original;
        }

        $relativePath = $relativeDir . $fileName;
        $file->move($absoluteDir, $fileName);

        $oldFile = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_ADMIN_PROFILE_IMAGE)
            ->where('file_record_id', $admin->admin_id)
            ->orderBy('file_id')
            ->first();

        $fileId = DB::table('tbl_attached_files')->insertGetId([
            'file_type' => self::TYPE_ADMIN_PROFILE_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $admin->admin_id,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_ADMIN_PROFILE_IMAGE)
            ->where('file_record_id', $admin->admin_id)
            ->where('file_id', '!=', $fileId)
            ->delete();

        if ($oldFile && !empty($oldFile->file_path)) {
            $oldPath = $uploadRoot . '/' . $oldFile->file_path;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => "/api/v1/image/show/" . self::TYPE_ADMIN_PROFILE_IMAGE . "/{$admin->admin_id}/LARGE?t=" . time(),
        ]);
    }

    public function removeProfileImage(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $files = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_ADMIN_PROFILE_IMAGE)
            ->where('file_record_id', $admin->admin_id)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_ADMIN_PROFILE_IMAGE)
            ->where('file_record_id', $admin->admin_id)
            ->delete();

        $uploadRoot = base_path('../user-uploads');
        foreach ($files as $file) {
            if (!empty($file->file_path)) {
                $path = $uploadRoot . '/' . $file->file_path;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        return response()->json(['message' => 'File deleted successfully']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $validator = Validator::make(
            $request->all(),
            [
                'current_password' => ['required', 'string'],
                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z]).{8,}$/',
                ],
                'conf_new_password' => ['required', 'same:new_password'],
            ],
            [
                'current_password.required' => 'Current password Is mandatory',
                'new_password.required' => 'Please enter a 8 digit alphanumeric password',
                'new_password.min' => 'Please enter a 8 digit alphanumeric password',
                'new_password.regex' => 'Please enter a 8 digit alphanumeric password',
                'conf_new_password.required' => 'Confirm new password Is mandatory',
                'conf_new_password.same' => 'Confirm password must match',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if (!$admin->validateLegacyPassword((string) $request->input('current_password'))) {
            return response()->json([
                'message' => 'Your current password mis matched',
                'errors' => ['current_password' => ['Your current password mis matched']],
            ], 422);
        }

        $admin->admin_password = LegacyPasswordHasher::hash((string) $request->input('new_password'));
        $admin->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    private function defaultTimezone(): string
    {
        return (string) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_TIMEZONE')
            ->value('conf_val') ?: 'UTC');
    }

    /** @return array<int, array{id: string, label: string}> */
    private function timezoneOptions(): array
    {
        $options = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $offset = (new DateTime('now', new DateTimeZone($tz)))->format('P');
            $options[] = [
                'id' => $tz,
                'label' => "UTC {$offset} {$tz}",
            ];
        }

        return $options;
    }
}
