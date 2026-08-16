<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Content\StoreCommunityPostRequest;
use App\Http\Requests\Api\V1\Content\StoreCommunityThreadRequest;
use App\Http\Requests\Api\V1\Content\StoreExpertQuestionRequest;
use App\Http\Requests\Api\V1\Content\StoreSuccessStoryRequest;
use App\Models\Blog;
use App\Models\CommunityForum;
use App\Models\CommunityPost;
use App\Models\CommunityThread;
use App\Models\ExpertQuestion;
use App\Models\HappyStory;
use App\Models\MarriageTip;
use App\Models\RegionalUpdate;
use App\Models\Webinar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends ApiController
{
    public function articles(Request $request): JsonResponse
    {
        $query = Blog::with('category')->active()->latest();

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->string('q') . '%');
        }

        return $this->success($query->paginate((int) $request->integer('per_page', 15)), 'Articles fetched successfully.');
    }

    public function article(string $slug): JsonResponse
    {
        return $this->success(Blog::with('category')->active()->where('slug', $slug)->firstOrFail(), 'Article fetched successfully.');
    }

    public function successStories(Request $request): JsonResponse
    {
        $stories = HappyStory::with('user:id,first_name,last_name')
            ->where('approved', 1)
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return $this->success($stories, 'Success stories fetched successfully.');
    }

    public function storeSuccessStory(StoreSuccessStoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $story = HappyStory::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'details' => $data['story'],
            'partner_name' => ($data['is_anonymous'] ?? false) ? 'Anonymous' : ($data['partner_name'] ?? 'Partner'),
            'photos' => '[]',
            'approved' => 0,
        ]);

        return $this->success($story, 'Success story submitted for moderation.', 201);
    }

    public function advice(Request $request): JsonResponse
    {
        $query = Blog::with('category')->active()->latest();
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($scope) => $scope->where('slug', $request->string('category')));
        }

        return $this->success($query->paginate((int) $request->integer('per_page', 15)), 'Relationship advice fetched successfully.');
    }

    public function expertQuestions(Request $request): JsonResponse
    {
        return $this->success(
            ExpertQuestion::where('status', 'answered')->latest()->paginate((int) $request->integer('per_page', 15)),
            'Expert questions fetched successfully.'
        );
    }

    public function storeExpertQuestion(StoreExpertQuestionRequest $request): JsonResponse
    {
        $question = ExpertQuestion::create([
            'user_id' => $request->user()->id,
            'category' => $request->validated('category', 'relationship'),
            'question' => $request->validated('question'),
            'details' => $request->validated('details'),
            'is_anonymous' => (bool) $request->validated('is_anonymous', true),
            'status' => 'pending',
        ]);

        return $this->success($question, 'Question submitted for expert review.', 201);
    }

    public function forums(Request $request): JsonResponse
    {
        return $this->success(
            CommunityForum::where('is_active', true)->latest()->paginate((int) $request->integer('per_page', 15)),
            'Community forums fetched successfully.'
        );
    }

    public function threads(Request $request, int $forum): JsonResponse
    {
        return $this->success(
            CommunityThread::with('user:id,first_name,last_name')
                ->where('community_forum_id', $forum)
                ->where('moderation_status', 'approved')
                ->latest()
                ->paginate((int) $request->integer('per_page', 15)),
            'Forum threads fetched successfully.'
        );
    }

    public function storeThread(StoreCommunityThreadRequest $request, int $forum): JsonResponse
    {
        CommunityForum::where('is_active', true)->findOrFail($forum);

        $thread = CommunityThread::create([
            'community_forum_id' => $forum,
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'moderation_status' => 'pending',
        ]);

        return $this->success($thread, 'Thread submitted for moderation.', 201);
    }

    public function posts(Request $request, int $thread): JsonResponse
    {
        return $this->success(
            CommunityPost::with('user:id,first_name,last_name')
                ->where('community_thread_id', $thread)
                ->where('moderation_status', 'approved')
                ->latest()
                ->paginate((int) $request->integer('per_page', 20)),
            'Thread posts fetched successfully.'
        );
    }

    public function storePost(StoreCommunityPostRequest $request, int $thread): JsonResponse
    {
        $threadModel = CommunityThread::where('moderation_status', 'approved')->where('is_locked', false)->findOrFail($thread);
        $post = CommunityPost::create([
            'community_thread_id' => $threadModel->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'moderation_status' => 'pending',
        ]);

        return $this->success($post, 'Reply submitted for moderation.', 201);
    }

    public function webinars(Request $request): JsonResponse
    {
        return $this->success(
            Webinar::whereIn('status', ['scheduled', 'live'])
                ->orderBy('starts_at')
                ->paginate((int) $request->integer('per_page', 15)),
            'Webinars fetched successfully.'
        );
    }

    public function registerWebinar(Request $request, int $webinar): JsonResponse
    {
        Webinar::whereIn('status', ['scheduled', 'live'])->findOrFail($webinar);

        DB::table('webinar_registrations')->updateOrInsert([
            'webinar_id' => $webinar,
            'user_id' => $request->user()->id,
        ], [
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return $this->success(message: 'Webinar registration saved.');
    }

    public function marriageTips(Request $request): JsonResponse
    {
        return $this->success(
            MarriageTip::where('is_active', true)
                ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
                ->latest('publish_at')
                ->paginate((int) $request->integer('per_page', 15)),
            'Marriage tips fetched successfully.'
        );
    }

    public function regionalUpdates(Request $request): JsonResponse
    {
        $query = RegionalUpdate::where('is_active', true)
            ->where(fn ($scope) => $scope->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->latest('publish_at');

        if ($request->filled('region')) {
            $query->where('region', $request->string('region'));
        }

        return $this->success($query->paginate((int) $request->integer('per_page', 15)), 'Regional updates fetched successfully.');
    }
}
