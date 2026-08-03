<?php

namespace App\Actions\Lesson;

use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\User;

class TrackLessonViewAction
{
    public function execute(Lesson $lesson, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        LessonView::updateOrCreate([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
        ]);
    }
}
