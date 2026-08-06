<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ForumSubscribedTagsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardForumSubscribedTagsController extends Controller
{
    public function __construct(private readonly ForumSubscribedTagsService $tags)
    {
    }

    public function subscribed(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($user->user_lang_id ?: 1);

        return response()->json([
            'data' => $this->tags->listSubscribed((int) $user->user_id, $langId),
        ]);
    }

    public function systemTags(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($user->user_lang_id ?: 1);
        $result = $this->tags->listSystemTags(
            $langId,
            $request->integer('page', 1),
            $request->integer('per_page', 50),
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($user->user_lang_id ?: 1);
        $items = $this->tags->suggestTags($request->string('keyword')->toString(), $langId);

        return response()->json(['data' => $items]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $tagId = $request->integer('ftag_id');
        if ($tagId < 1) {
            return response()->json(['message' => 'Invalid request.'], 422);
        }

        $ok = $this->tags->subscribe((int) $request->user()->user_id, $tagId);
        if (! $ok) {
            return response()->json(['message' => 'Could not subscribe. Tag may already be in your list.'], 422);
        }

        return response()->json(['message' => 'You have subscribed successfully.']);
    }

    public function unsubscribe(Request $request, int $tagId): JsonResponse
    {
        if ($tagId < 1) {
            return response()->json(['message' => 'Invalid request.'], 422);
        }

        $ok = $this->tags->unsubscribe((int) $request->user()->user_id, $tagId);
        if (! $ok) {
            return response()->json(['message' => 'Could not unsubscribe.'], 422);
        }

        return response()->json(['message' => 'You have unsubscribed successfully.']);
    }

    public function unsubscribeAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($user->user_lang_id ?: 1);
        $ok = $this->tags->unsubscribeAll((int) $user->user_id, $langId);
        if (! $ok) {
            return response()->json(['message' => 'Could not unsubscribe from all tags.'], 422);
        }

        return response()->json(['message' => 'You have unsubscribed from all tags successfully.']);
    }
}
