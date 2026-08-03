<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class CreateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt_topic' => ['required', 'string', 'max:500'],
            'prompt_words' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
