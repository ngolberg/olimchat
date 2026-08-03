<?php

namespace App\Actions\Lesson;

use App\Models\Lesson;
use App\Models\LessonRating;
use App\Models\User;

class RateLessonAction
{
    public function execute(Lesson $lesson, User $user, int $rating): void
    {
        LessonRating::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => $user->id],
            ['rating' => $rating]
        );
    }
}
