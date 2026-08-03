<?php

namespace App\Actions\Lesson;

use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonGeneratorService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class CreateLessonAction
{
    public function __construct(private LessonGeneratorService $generator) {}

    public function execute(User $user, string $promptTopic, ?string $promptWords): Lesson
    {
        $todayCount = $user->lessons()->whereDate('created_at', today())->count();
        if ($todayCount >= 30) {
            throw new HttpResponseException(
                response()->json(['message' => 'You have reached the daily limit of 30 lessons.'], 429)
            );
        }

        $ul = match ($user->lang) {
            'en'    => 'English',
            default => 'Russian',
        };

        $content = $this->generator->generate($promptTopic, $promptWords, $ul);

        $hashId = $this->generateHashId();

        return $user->lessons()->create([
            'hash_id'      => $hashId,
            'title'        => mb_substr($content['title'], 0, 255),
            'prompt_topic' => $promptTopic,
            'prompt_words' => $promptWords,
            'content'      => $content,
        ]);
    }

    private function generateHashId(): string
    {
        do {
            $hashId = Str::random(12);
        } while (Lesson::where('hash_id', $hashId)->exists());

        return $hashId;
    }
}
