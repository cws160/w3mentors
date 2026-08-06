<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function __construct(private readonly BlogService $blog) {}

    public function index(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $query = DB::table('tbl_blog_post as p')
            ->join('tbl_blog_post_lang as pl', function ($join) use ($langId) {
                $join->on('p.post_id', '=', 'pl.postlang_post_id')
                    ->where('pl.postlang_lang_id', '=', $langId);
            })
            ->where('p.post_published', 1)
            ->where('p.post_deleted', 0)
            ->select([
                'p.post_id as id',
                'pl.post_title as title',
                'pl.post_short_description as excerpt',
                'p.post_published_on as published_at',
            ])
            ->orderByDesc('p.post_published_on');

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('pl.post_title', 'like', "%{$search}%")
                    ->orWhere('pl.post_short_description', 'like', "%{$search}%")
                    ->orWhere('pl.post_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $catIds = $this->blog->categoryIdsWithDescendants($request->integer('category'), $langId);
            $query->whereExists(function ($q) use ($catIds) {
                $q->select(DB::raw(1))
                    ->from('tbl_blog_post_to_category as ptc')
                    ->whereColumn('ptc.ptc_post_id', 'p.post_id')
                    ->whereIn('ptc.ptc_bpcategory_id', $catIds);
            });
        }

        $posts = $query->paginate($request->integer('per_page', 12));
        $categoryId = $request->integer('category');

        return response()->json([
            'categories' => $this->blog->categoryTree($langId),
            'category_name' => $categoryId > 0 ? $this->blog->categoryName($langId, $categoryId) : '',
            'data' => $this->blog->attachCategoriesToPosts($posts->items(), $langId),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(Request $request, int $post): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $row = DB::table('tbl_blog_post as p')
            ->join('tbl_blog_post_lang as pl', function ($join) use ($langId) {
                $join->on('p.post_id', '=', 'pl.postlang_post_id')
                    ->where('pl.postlang_lang_id', '=', $langId);
            })
            ->where('p.post_id', $post)
            ->where('p.post_published', 1)
            ->where('p.post_deleted', 0)
            ->first([
                'p.post_id as id',
                'pl.post_title as title',
                'pl.post_short_description as excerpt',
                'pl.post_description as description',
                'pl.post_author_name as author',
                'p.post_published_on as published_at',
                'p.post_comment_opened as comment_opened',
            ]);

        if (!$row) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $description = $row->description ?? '';
        if ($description !== '') {
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $posts = $this->blog->attachCategoriesToPosts([$row], $langId);
        $data = $posts[0] ?? [];
        $comments = $this->blog->approvedComments($post, $request->integer('comments_page', 1));

        return response()->json([
            'data' => array_merge($data, [
                'description' => $description,
                'author' => $row->author ?? '',
                'comment_opened' => (int) ($row->comment_opened ?? 0) === 1,
                'comments_count' => $this->blog->commentsCount($post),
                'images' => $this->blog->postImages($post),
            ]),
            'categories' => $this->blog->categoryTree($langId),
            'comments' => $comments['data'],
            'comments_meta' => $comments['meta'],
        ]);
    }

    public function storeComment(Request $request, int $post): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $row = DB::table('tbl_blog_post')
            ->where('post_id', $post)
            ->where('post_published', 1)
            ->where('post_deleted', 0)
            ->first(['post_comment_opened']);

        if (!$row || (int) $row->post_comment_opened !== 1) {
            return response()->json(['message' => 'Comments are closed'], 422);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $name = trim(($user->user_first_name ?? '').' '.($user->user_last_name ?? ''));
        if ($name === '') {
            $name = $user->user_email ?? 'User';
        }

        DB::table('tbl_blog_post_comments')->insert([
            'bpcomment_post_id' => $post,
            'bpcomment_user_id' => $user->user_id,
            'bpcomment_author_name' => $name,
            'bpcomment_author_email' => $user->user_email,
            'bpcomment_content' => $validated['content'],
            'bpcomment_approved' => 0,
            'bpcomment_deleted' => 0,
            'bpcomment_added_on' => now(),
            'bpcomment_user_ip' => $request->ip() ?? '',
            'bpcomment_user_agent' => (string) $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Comment submitted and awaiting approval.',
        ]);
    }
}
