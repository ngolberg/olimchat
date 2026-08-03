<?php

namespace App\Actions\Lesson;

use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonGeneratorService;

class EditLessonAction
{
    public function __construct(private LessonGeneratorService $generator) {}

    public function execute(User $user, Lesson $lesson, string $editPrompt): Lesson
    {
        abort_if($lesson->user_id !== $user->id, 403);

        $ul = match ($user->lang) {
            'en'    => 'English',
            default => 'Russian',
        };

        $updated = $this->generator->applyEdit($lesson->content, $editPrompt, $ul);

        $lesson->update([
            'title'   => mb_substr($updated['title'], 0, 255),
            'content' => $updated,
        ]);

        return $lesson->fresh();
    }
}
