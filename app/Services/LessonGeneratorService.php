<?php

namespace App\Services;

use App\Exceptions\LessonGenerationException;
use OpenAI\Laravel\Facades\OpenAI;

class LessonGeneratorService
{
    private const MODEL = 'gpt-5.4-mini';

    private const SCHEMA = [
        'type' => 'object',
        'required' => ['title', 'sections'],
        'additionalProperties' => false,
        'properties' => [
            'title' => ['type' => 'string'],
            'sections' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['title', 'components'],
                    'additionalProperties' => false,
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'components' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'required' => ['type', 'data'],
                                'additionalProperties' => false,
                                'properties' => [
                                    'type' => [
                                        'type' => 'string',
                                        'enum' => ['simple_text', 'listenable_text', 'word_list', 'sentences_ll', 'sentences_ul'],
                                    ],
                                    'data' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function generate(string $promptTopic, ?string $promptWords, string $ul = 'Russian', string $ll = 'Hebrew'): array
    {
        $systemPrompt = $this->buildSystemPrompt($promptTopic, $promptWords, $ul, $ll);

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'lesson',
                        'strict' => false,
                        'schema' => self::SCHEMA,
                    ],
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => 'Generate the lesson now.'],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new LessonGenerationException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }

        return $this->parseResponse($response->choices[0]->message->content);
    }

    public function applyEdit(array $existingContent, string $editPrompt, string $ul = 'Russian', string $ll = 'Hebrew'): array
    {
        $systemPrompt = <<<PROMPT
You are an experienced {$ll} teacher. You will receive an existing language lesson in JSON format and an edit instruction.
Apply the edit instruction to the lesson and return the complete updated lesson in the same JSON structure.
Keep all unchanged parts intact. Apply the moral principle of "do no harm".
The lesson is for {$ll} learners who speak {$ul} natively.
PROMPT;

        $userMessage = "Edit instruction: {$editPrompt}\n\nExisting lesson:\n" . json_encode($existingContent, JSON_UNESCAPED_UNICODE);

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'lesson',
                        'strict' => false,
                        'schema' => self::SCHEMA,
                    ],
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new LessonGenerationException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }

        return $this->parseResponse($response->choices[0]->message->content);
    }

    private function buildSystemPrompt(string $promptTopic, ?string $promptWords, string $ul, string $ll): string
    {
        $wordsLine = $promptWords
            ? "The user wants to practice the following words: {$promptWords}. If the words are not in {$ll}, translate them to {$ll}. If some words from the list are meaningless or you are not sure how to translate them, remove them from the list."
            : "No specific words were provided. Add words related to the topic. No more than 15 in total.";

        return <<<PROMPT
You are an experienced {$ll} teacher, an expert in teaching methodologies.
Create content for a language lesson in JSON format. Use the following components and generate the content for them.
The subject of the lesson is: {$promptTopic}
If it looks like a conversation topic, create a text to read. If it looks like a grammar topic, create a grammar lesson.
If there are 2 subjects, create 2 sections. If more than 2, ignore the extra subjects.
If it's a  grammar lesson it should cover the topic in a simple way, with examples and exercises. If it's a discussion topic, create a text to read and exercises to discuss it.
{$wordsLine}
Apply the moral principle of "do no harm" and avoid any content that might be offensive or inappropriate.

A lesson consists of sections. Each section contains one or more components.
The titles of the sections should be short and concise.

If the topic is related to grammar, use this structure:
1. Introduction: simple_text in {$ul} — suggest participants introduce themselves, give non-trivial example sentences, encourage not worrying about mistakes.
2. Grammatical topic: simple_text — explain the rules.
3. word_list — first the words from the user's list, then related words. No more than 15 total.
4. sentences_ll — 12 sentences in {$ll} for the user to translate to {$ul}. Participants take turns.
5. sentences_ul — 12 sentences in {$ul} to translate to {$ll}. Participants take turns.
6. simple_text — discussion: suggest related questions, give examples.
7. simple_text — Retro: discuss what they learned, what they can teach each other, what they liked.

If the topic is a discussion subject, use this structure:
1. Introduction: simple_text in {$ul} — suggest participants introduce themselves, give non-trivial example sentences, encourage not worrying about mistakes.
2. word_list — first the words from the user's list, then related words. No more than 15 total.
3. sentences_ll — 12 example sentences related to the topic in {$ll} to translate to {$ul}.
4. sentences_ul — 12 example sentences related to the topic in {$ul} to translate to {$ll}.
5. listenable_text — a generated text on the topic in {$ll}, about 20 sentences. Add a hint for the user to select text and listen to it.
6. simple_text — discussion: suggest related questions, give examples.
7. simple_text — Retro: discuss what they learned, what they can teach each other, what they liked.

Component data shapes:
- simple_text: { "html": "<p>...</p>" } — nicely formatted HTML in {$ul}
- listenable_text: { "text": "...", "hint": "..." } — plain text in {$ll}, hint in {$ul}
- word_list: { "items": [ { "word": "...", "translation": "...", "transcription": "...", "is_verb": false, "forms": null, "sentences": ["...", "..."] } ] }
  For verbs set is_verb=true and fill forms as an object with pronoun keys and conjugated forms as values.
- sentences_ll: { "items": [ { "text": "...", "transcription": "...", "translation": "..." } ] } — exactly 12 items
- sentences_ul: { "items": [ { "text": "...", "answer": "..." } ] } — exactly 12 items

PROMPT;
    }

    private function parseResponse(string $json): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LessonGenerationException('Invalid JSON returned by OpenAI: ' . json_last_error_msg());
        }

        if (empty($data['title']) || empty($data['sections'])) {
            throw new LessonGenerationException('Lesson response is missing required fields.');
        }

        return $data;
    }
}
