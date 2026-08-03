<?php

namespace App\Http\Controllers\Api;

use App\Actions\Lesson\CreateLessonAction;
use App\Actions\Lesson\EditLessonAction;
use App\Actions\Lesson\RateLessonAction;
use App\Actions\Lesson\TrackLessonViewAction;
use App\Exceptions\LessonGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\CreateLessonRequest;
use App\Http\Requests\Lesson\EditLessonRequest;
use App\Http\Requests\Lesson\RateLessonRequest;
use App\Http\Resources\LessonDetailResource;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LessonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $lessons = $request->user()
            ->lessons()
            ->withCount(['views', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->with(['user', 'ratings' => fn ($q) => $q->where('user_id', $userId)])
            ->latest()
            ->get();

        return LessonResource::collection($lessons);
    }

    public function store(CreateLessonRequest $request, CreateLessonAction $action): LessonDetailResource|JsonResponse
    {
        try {
            $lesson = $action->execute(
                $request->user(),
                $request->validated('prompt_topic'),
                $request->validated('prompt_words'),
            );
        } catch (LessonGenerationException $e) {
            return response()->json(['message' => 'Failed to generate lesson. Please try again.', 'details' => $e->getMessage()], 422);
        }

        $userId = $request->user()->id;
        $lesson->loadCount(['views', 'ratings'])->loadAvg('ratings', 'rating')
            ->load(['user', 'ratings' => fn ($q) => $q->where('user_id', $userId)]);

        return new LessonDetailResource($lesson);
    }

    public function show(string $hashId, Request $request, TrackLessonViewAction $track): LessonDetailResource
    {
        $userId = $request->user()?->id;
        $lesson = Lesson::where('hash_id', $hashId)
            ->withCount(['views', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->with(['user', 'ratings' => fn ($q) => $q->when($userId, fn ($q) => $q->where('user_id', $userId))])
            ->firstOrFail();

        $track->execute($lesson, $request->user());

        return new LessonDetailResource($lesson);
    }

    public function update(string $hashId, EditLessonRequest $request, EditLessonAction $action): LessonDetailResource|JsonResponse
    {
        $lesson = Lesson::where('hash_id', $hashId)->firstOrFail();

        abort_if($lesson->user_id !== $request->user()->id, 403);

        try {
            $lesson = $action->execute($request->user(), $lesson, $request->validated('edit_prompt'));
        } catch (LessonGenerationException $e) {
            return response()->json(['message' => 'Failed to apply changes. Please try again.'], 422);
        }

        $userId = $request->user()->id;
        $lesson->loadCount(['views', 'ratings'])->loadAvg('ratings', 'rating')
            ->load(['user', 'ratings' => fn ($q) => $q->where('user_id', $userId)]);

        return new LessonDetailResource($lesson);
    }

    public function destroy(string $hashId, Request $request): JsonResponse
    {
        $lesson = Lesson::where('hash_id', $hashId)->firstOrFail();

        abort_if($lesson->user_id !== $request->user()->id, 403);

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted.']);
    }

    public function rate(string $hashId, RateLessonRequest $request, RateLessonAction $action): JsonResponse
    {
        $lesson = Lesson::where('hash_id', $hashId)->firstOrFail();

        $action->execute($lesson, $request->user(), $request->validated('rating'));

        $lesson->loadCount('ratings')->loadAvg('ratings', 'rating');

        return response()->json([
            'avg_rating'    => round((float) $lesson->ratings_avg_rating, 1),
            'ratings_count' => (int) $lesson->ratings_count,
        ]);
    }

    public function top(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()?->id;
        $lessons = Lesson::withCount(['views', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->with(['user', 'ratings' => fn ($q) => $q->when($userId, fn ($q) => $q->where('user_id', $userId))])
            ->whereHas('ratings')
            ->orderByDesc('ratings_avg_rating')
            ->orderByDesc('views_count')
            ->paginate(20);

        return LessonResource::collection($lessons);
    }
}
