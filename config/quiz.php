<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Attempts Per Subtopic Quiz
    |--------------------------------------------------------------------------
    |
    | Defines how many completed attempts a student is allowed for an individual
    | subtopic quiz. This value is referenced throughout the quiz-taking flow
    | to determine whether a learner is still eligible to retry a quiz.
    |
    */

    'max_attempts' => (int) env('QUIZ_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Document-Level Quiz Batch Defaults
    |--------------------------------------------------------------------------
    |
    | Settings that control the behaviour of document-level quiz sessions,
    | where learners can take every eligible subtopic quiz for a learning
    | material in a single, continuous flow.
    |
    */

    'document_batch' => [
        // Reserved for future customisations (e.g., enforce sequential order toggles)
    ],

];