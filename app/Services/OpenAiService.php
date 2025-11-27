<?php

namespace App\Services;

use App\Models\ChatGptApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use RuntimeException;

class OpenAiService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $maxRetries;
    private int $timeout;
    private float $costPer1k;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY', '');
        $this->apiUrl = config('services.openai.api_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('services.openai.model', 'gpt-4.1');
        $this->maxRetries = (int) config('services.openai.max_retries', 3);
        $this->timeout = (int) config('services.openai.timeout', 120);
        $this->costPer1k = (float) config('services.openai.cost_per_1k_tokens', 0.002);

        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key not configured (services.openai.api_key or OPENAI_API_KEY).');
        }
    }

    /* ---------------------------------------------------------
     | Public high-level methods (kept + improved)
     | --------------------------------------------------------- */

    /**
     * Analyze uploaded learning material content
     */
    public function analyzeContent(string $content, ?string $obtlContext = null): array
    {
        $prompt = $this->buildContentAnalysisPrompt($content, $obtlContext);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educational content analyst. Extract high-level instructional topics, core concepts, and recommended MCQ counts strictly from the supplied material. Do not invent facts.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.0,
            'top_p' => 0.0,
            'response_format' => ['type' => 'json_object']
        ], 'content_analysis');

        // response['content'] expected to be JSON text
        return json_decode($response['content'] ?? '[]', true) ?? [];
    }

    /**
     * Generate Table of Specification (ToS)
     */
    public function generateToS(array $learningOutcomes, string $materialSummary, int $totalItems = 20): array
    {
        $prompt = $this->buildToSPrompt($learningOutcomes, $materialSummary, $totalItems);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educational assessment designer specializing in Bloom’s taxonomy and test blueprinting.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.0,
            'top_p' => 0.0,
            'response_format' => ['type' => 'json_object']
        ], 'tos_generation');

        return json_decode($response['content'] ?? '{}', true) ?? [];
    }


    /**
     * Generate quiz questions (STRICT engine)
     *
     * Accepts:
     *  - $topics: array of topic descriptors e.g. [ ['name' => 'X','num_items'=>2], ... ]
     *  - $materialContent: full text to source questions from
     *  - $options: optional keys: model, max_attempts, temperature, top_p, max_tokens
     *
     * Returns: array with keys 'questions' and 'metadata'
     */
    public function generateQuizQuestions(array $topics, string $materialContent, array $options = []): array
    {
        // validate arguments
        if (empty($topics) || empty($materialContent)) {
            throw new Exception('generateQuizQuestions requires topics and materialContent.');
        }

        $model = $options['model'] ?? $this->model;
        $maxAttempts = (int) ($options['max_attempts'] ?? 3);
        $temperature = (float) ($options['temperature'] ?? 0.0);
        $topP = (float) ($options['top_p'] ?? 0.0);
        $maxTokens = (int) ($options['max_tokens'] ?? 3000);

        // Build strict system prompt (includes topics and shortened material if necessary)
        $systemPrompt = $this->buildTopicQuizGenerationPrompt($topics, $materialContent);

        $attempt = 0;
        $lastReport = null;
        $lastModelText = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                ],
                'temperature' => $temperature,
                'top_p' => $topP,
                'max_tokens' => $maxTokens,
                'n' => 1
            ];

            $raw = $this->sendRequest($payload, 'quiz_generation_attempt_' . $attempt);

            $modelText = $raw['content'] ?? '';

            // Validate response
            [$isValid, $report, $decoded] = $this->postResponseValidator($modelText, $materialContent, $topics);

            if ($isValid && is_array($decoded)) {
                // Ensure metadata.total_generated is accurate
                $decoded['metadata']['total_generated'] = count($decoded['questions'] ?? []);
                return $decoded;
            }

            // prepare for regeneration
            $lastReport = $report;
            $lastModelText = $modelText;

            // Build regeneration prompt (keeps same base but adds feedback)
            $systemPrompt = $this->buildRegenerationPrompt($this->buildTopicQuizGenerationPrompt($topics, $materialContent), $modelText, $report);
        }

        // If we get here, all attempts failed
        $errorMsg = 'generateQuizQuestions failed after ' . $maxAttempts . ' attempts.';
        Log::error($errorMsg, ['last_report' => $lastReport, 'last_model_text' => $lastModelText]);
        throw new Exception($errorMsg . ' See logs for validation report.');
    }

    /**
     * Reword/regenerate a quiz question (for regeneration functionality)
     */
    public function rewordQuestion(string $originalQuestion, array $originalOptions, int $regenerationCount): array
    {
        $prompt = $this->buildRewordPrompt($originalQuestion, $originalOptions, $regenerationCount);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in creating alternative versions of educational assessment questions while maintaining the same learning objective and difficulty level.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.4,
            'top_p' => 0.9,
            'response_format' => ['type' => 'json_object']
        ], 'question_reword');

        return json_decode($response['content'] ?? '{}', true) ?? [];
    }

    /**
     * Generate personalized formative feedback
     */
    public function generateFeedback(array $quizAttemptData, array $userMasteryData): array
    {
        $prompt = $this->buildFeedbackPrompt($quizAttemptData, $userMasteryData);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a supportive educational mentor who provides constructive, personalized feedback to help students improve their learning. Your feedback is encouraging, specific, and actionable.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.6,
            'top_p' => 0.9,
            'response_format' => ['type' => 'json_object']
        ], 'feedback_generation');

        return json_decode($response['content'] ?? '{}', true) ?? [];
    }

    /**
     * Extract title from OBTL document
     */
    public function extractObtlTitle(string $obtlContent): array
    {
        $prompt = $this->buildObtlTitlePrompt($obtlContent);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in analyzing educational documents and extracting key information accurately and efficiently.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.0,
            'top_p' => 0.0,
            'response_format' => ['type' => 'json_object']
        ], 'obtl_title_extraction');

        return json_decode($response['content'] ?? '{}', true) ?? [];
    }

    /**
     * Parse OBTL document to extract learning outcomes
     */
    public function parseObtlDocument(string $obtlContent): array
    {
        $prompt = $this->buildObtlParsingPrompt($obtlContent);

        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in Outcome-Based Teaching and Learning (OBTL) who can extract and structure learning outcomes from curriculum documents. Only extract explicit outcomes and infer cognitive levels from verbs.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.0,
            'top_p' => 0.0,
            'response_format' => ['type' => 'json_object']
        ], 'obtl_parsing');

        return json_decode($response['content'] ?? '{}', true) ?? [];
    }

    /* ---------------------------------------------------------
     | Prompt builders (improved)
     | --------------------------------------------------------- */

    /**
     * Build OBTL title extraction prompt (kept original but cleaned)
     */
    private function buildObtlTitlePrompt(string $obtlContent): string
    {
        return <<<PROMPT
Extract the course or document title from the following OBTL (Outcome-Based Teaching and Learning) document.

OBTL Document:
$obtlContent

Return JSON:
{
  "title": "main course or document title or empty string",
  "course_code": "course code if found or empty",
  "subtitle": "subtitle if present or empty",
  "full_title": "combined title and code if applicable",
  "confidence": "high|medium|low",
  "extraction_notes": "notes about how the title was determined"
}
PROMPT;
    }

    /**
     * Build content analysis prompt (improved)
     */
    private function buildContentAnalysisPrompt(string $content, ?string $obtlContext): string
    {
        $contextSection = $obtlContext ? "\n\nOBTL Context:\n$obtlContext" : '';

        return <<<PROMPT
Analyze the following learning material and extract high-level instructional topics suitable for multiple-choice assessments. Use only information explicitly present.

Learning Material:
$content
$contextSection

Return JSON only with this structure:
{
  "content_summary": "2-3 sentence synthesis (explicitly supported)",
  "topics": [
    {
      "topic": "short topic title",
      "description": "1-2 sentence overview referencing the material",
      "key_concepts": ["explicit concept 1", "explicit concept 2"],
      "recommended_question_count": 4,
      "cognitive_emphasis": "remember|understand|apply|analyze|evaluate|create",
      "supporting_notes": "short note referencing the exact spot in the material"
    }
  ],
  "analysis_notes": "optional clarifications"
}

Requirements:
- Include only topics that are clearly supported by the material.
- recommended_question_count must be an integer (4-6).
- Do not invent facts or include outside knowledge.
PROMPT;
    }

    /**
     * Build Table of Specification prompt (improved)
     */
    private function buildToSPrompt(array $learningOutcomes, string $materialSummary, int $totalItems): string
    {
        $outcomesJson = json_encode($learningOutcomes, JSON_PRETTY_PRINT);

        return <<<PROMPT
    Create a full Table of Specification (ToS) using the learning outcomes and material summary below.

    The ToS MUST follow the Revised Bloom's Taxonomy:
    - remember
    - understand
    - apply
    - analyze
    - evaluate
    - create

    Learning Outcomes:
    $outcomesJson

    Material Summary:
    $materialSummary

    Total Number of Quiz Items: $totalItems

    ------------------------------------------------
    STRICT REQUIREMENTS (DO NOT VIOLATE)
    ------------------------------------------------

    1. **You MUST infer the cognitive level from the verb** of each learning outcome  
    Example mapping:
    - remember → define, list, identify  
    - understand → explain, summarize, describe  
    - apply → compute, solve, demonstrate  
    - analyze → compare, differentiate, categorize  
    - evaluate → judge, critique, justify  
    - create → design, construct, formulate  

    2. **YOU MAY NOT assign all learning outcomes to the same cognitive level.**
    Each outcome must use the correct Bloom level based on its verb.

    3. **Final cognitive distribution must include at least FOUR different Bloom levels.**
    You may NEVER produce a distribution that is only 1–2 levels.

    4. **No cognitive level may exceed 40% of the total items.**

    5. **Every cognitive level must have a minimum of 10% allocation.**  
    (Example for 20 items → at least 2 items per level)

    6. The sum of all num_items MUST equal exactly **$totalItems**.

    7. Weight percentage must sum to **100%**.

    8. DO NOT invent content beyond the learning outcomes and material summary.

    ------------------------------------------------
    RETURN JSON EXACTLY IN THIS FORMAT:
    {
    "table_of_specification": [
        {
        "topic": "topic name",
        "learning_outcome": "specific outcome text",
        "cognitive_level": "remember|understand|apply|analyze|evaluate|create",
        "num_items": 2,
        "weight_percentage": 10,
        "sample_indicators": ["short observable competency"]
        }
    ],
    "cognitive_distribution": {
        "remember": 20,
        "understand": 20,
        "apply": 20,
        "analyze": 20,
        "evaluate": 10,
        "create": 10
    },
    "total_items": $totalItems,
    "assessment_focus": "2–3 sentence justification of how the ToS aligns with Bloom's taxonomy and the learning outcomes."
    }
    PROMPT;
    }

    /**
     * Build quiz generation prompt (STRICT) — uses WHAT/WHICH only
     */
    private function buildTopicQuizGenerationPrompt(array $topics, string $materialContent): string
    {
        $topicsJson = json_encode($topics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $totalQuestions = collect($topics)->sum('num_items');

        // shorten material if very long
        $materialSnippet = $this->shortenMaterialForPrompt($materialContent);

        return <<<PROMPT
        You are an expert educational assessment designer. STRICTLY follow these instructions.

        OUTPUT FORMAT:
        Return ONLY a JSON object (no commentary, no markdown) that matches the schema shown at the end.

        GOAL:
        Generate clear, simple, fact-based multiple-choice questions that come DIRECTLY from the material.  
        Questions should resemble classroom factual recall items such as:
        - "How many ...?"
        - "What is the ...?"
        - "Which planet ...?"

        RULES:
        1) Generate EXACTLY {$totalQuestions} questions based ONLY on factual statements found in the Material Content.
        If insufficient material exists, generate only valid questions and set metadata.incomplete = true.

        2) Allowed question_text starters are ONLY:
        - “What”
        - “Which”
        - “How many”
        - “How much”
        NO other interrogatives allowed.

        3) “How many” or “How much” questions are ONLY allowed when the material contains an explicit number or clearly countable quantity.
        If no number exists, do NOT generate them.

        4) Use 4 answer options (A–D). Exactly ONE correct option.

        5) CORRECT OPTION RANDOMIZATION:
        - The correct option MUST be randomly placed in A/B/C/D.
        - The correct option MUST NOT always be A.
        - No predictable patterns or sequences.
        - Distribute correct answers across different letters.

        6) The correct option MUST appear WORD-FOR-WORD in the source_excerpt.
        If the correct option text is not present in the excerpt, you MUST NOT use it.

        7) If a question cannot be supported by a verbatim excerpt, do NOT generate the question.
        Reduce total_generated and set metadata.incomplete=true.

        8) Options must be short (1–6 words only).

        9) The source_excerpt must be copied exactly from the material (<= 40 words).

        10) Explanation must reference the source_excerpt (1–40 words).

        11) Question_text must be concise (< 120 chars).

        12) Do NOT use the word "material" inside the question_text.

        IMPORTANT:
        The following examples are ONLY STYLE EXAMPLES.
        DO NOT copy or reuse any facts, topics, terms, or content from the examples.
        Use ONLY the actual Material Content when generating questions.


        FEW-SHOT EXAMPLES (Follow these patterns exactly):

        Example 1:
        Material excerpt:
        "The solar system has 8 planets. The smallest is Mercury and the largest is Jupiter."

        Valid generated question:
        {
        "question_text": "How many planets are in the solar system?",
        "question_type": "multiple_choice",
        "difficulty": "easy",
        "topic": "Astronomy",
        "cognitive_level": "remember",
        "options": [
            {"option_letter":"A","option_text":"6","is_correct":false},
            {"option_letter":"B","option_text":"8","is_correct":true},
            {"option_letter":"C","option_text":"10","is_correct":false},
            {"option_letter":"D","option_text":"4","is_correct":false}
        ],
        "explanation":"The excerpt states there are 8 planets.",
        "source_excerpt":"The solar system has 8 planets."
        }

        Example 2:
        Material excerpt:
        "The smallest planet is Mercury, and Jupiter is the largest planet."

        Valid generated question:
        {
        "question_text": "Which planet is the largest?",
        "question_type": "multiple_choice",
        "difficulty": "easy",
        "topic": "Astronomy",
        "cognitive_level": "remember",
        "options": [
            {"option_letter":"A","option_text":"Mercury","is_correct":false},
            {"option_letter":"B","option_text":"Earth","is_correct":false},
            {"option_letter":"C","option_text":"Jupiter","is_correct":true},
            {"option_letter":"D","option_text":"Mars","is_correct":false}
        ],
        "explanation":"The excerpt directly states Jupiter is the largest planet.",
        "source_excerpt":"Jupiter is the largest planet."
        }

        Example 3:
        Material excerpt:
        "Photosynthesis uses sunlight to convert carbon dioxide and water into glucose."

        Valid generated question:
        {
        "question_text": "What does photosynthesis produce?",
        "question_type": "multiple_choice",
        "difficulty": "easy",
        "topic": "Biology",
        "cognitive_level": "understand",
        "options": [
            {"option_letter":"A","option_text":"Oxygen","is_correct":false},
            {"option_letter":"B","option_text":"Glucose","is_correct":true},
            {"option_letter":"C","option_text":"Protein","is_correct":false},
            {"option_letter":"D","option_text":"Nitrogen","is_correct":false}
        ],
        "explanation":"The excerpt says it converts materials into glucose.",
        "source_excerpt":"convert carbon dioxide and water into glucose."
        }

        Example 4 (Randomized correct letter is required):
        Material excerpt:
        "Water boils at 100 degrees Celsius at standard pressure."

        Valid generated question:
        {
        "question_text": "What is the boiling point of water at standard pressure?",
        "question_type": "multiple_choice",
        "difficulty": "easy",
        "topic": "Science",
        "cognitive_level": "remember",
        "options": [
            {"option_letter":"A","option_text":"50°C","is_correct":false},
            {"option_letter":"B","option_text":"90°C","is_correct":false},
            {"option_letter":"C","option_text":"100°C","is_correct":true},
            {"option_letter":"D","option_text":"120°C","is_correct":false}
        ],
        "explanation":"The excerpt states water boils at 100°C.",
        "source_excerpt":"Water boils at 100 degrees Celsius."
        }

        These examples demonstrate:
        - Allowed question forms (“How many,” “What,” “Which”)
        - Simple factual recall
        - Random correct-answer placement
        - Short option_text values
        - Correct answers that appear verbatim in the source_excerpt
        - Proper JSON structure
        - No invented content



        SCHEMA:
        {
        "questions": [
            {
            "question_text": "How many ...? / What ...? / Which ...?",
            "question_type": "multiple_choice",
            "difficulty": "easy|medium|hard",
            "topic": "topic name (exact match)",
            "cognitive_level": "remember|understand|apply|analyze|evaluate|create",
            "options": [
                {"option_letter":"A","option_text":"...","is_correct":true|false},
                {"option_letter":"B","option_text":"...","is_correct":true|false},
                {"option_letter":"C","option_text":"...","is_correct":true|false},
                {"option_letter":"D","option_text":"...","is_correct":true|false}
            ],
            "explanation":"1-40 words referencing the source_excerpt",
            "source_excerpt":"exact excerpt <= 40 words from the material"
            }
        ],
        "metadata": {
            "total_requested": {$totalQuestions},
            "total_generated": 0,
            "incomplete": false,
            "notes": ""
        }
        }

        TOPICS:
        {$topicsJson}

        MATERIAL:
        {$materialSnippet}

        Generate the JSON now.
        PROMPT;

    }

    /**
     * Shorten material if very long to keep prompt inside token limits.
     */
    protected function shortenMaterialForPrompt(string $material): string
    {
        $maxChars = 6000;
        if (strlen($material) <= $maxChars) {
            return $material;
        }

        $sentences = preg_split('/(?<=[.?!])\s+/', $material);
        $keep = [];
        $chars = 0;
        foreach ($sentences as $s) {
            $sTrim = trim($s);
            if ($sTrim === '') continue;
            $keep[] = $sTrim;
            $chars += strlen($sTrim);
            if ($chars > $maxChars) break;
        }
        return implode(' ', $keep);
    }

    /**
     * Build question type specific instructions (kept, strict)
     */
    private function buildQuestionTypeInstructions(array $questionTypes): string
    {
        return <<<RULES
### QUESTION TYPE RULES (STRICT MODE)
Only "multiple_choice" with 4 options A-D. Exactly one correct option. Must be WHAT or WHICH only.
RULES;
    }

    /**
     * Build difficulty level instructions (kept, strict)
     */
    private function buildDifficultyInstructions(array $difficultyLevels): string
    {
        return <<<RULES
### DIFFICULTY LEVEL RULES
easy: direct recall from a single statement.
medium: requires combining two short facts in same passage.
hard: identification of a specific component/property explicitly stated.
RULES;
    }

    /**
     * Build validation instructions (kept)
     */
    private function buildValidationInstructions(): string
    {
        return <<<RULES
### VALIDATION RULES
1) question_text must start with 'What' or 'Which'.
2) source_excerpt must be verbatim in material and <= 40 words.
3) correct option must appear verbatim inside source_excerpt.
4) No inference/hypotheticals.
RULES;
    }

    /**
     * Build question reword prompt (kept)
     */
    private function buildRewordPrompt(string $originalQuestion, array $originalOptions, int $regenerationCount): string
    {
        $optionsJson = json_encode($originalOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
    You are an expert in generating alternative versions of multiple-choice questions.

    Your task is to:
    1. **Reword the question** (same meaning, same difficulty, same cognitive level).
    2. **Rewrite all answer choices in new wording**.
    3. **Randomly shuffle the answer choices** (A–D).
    4. **Ensure exactly ONE correct answer**, and it **must remain correct** under the reworded question.
    5. Explanation must reference the same fact as the original question.
    6. Output must ALWAYS follow the JSON schema.

    ---------------------------------------
    Original Question:
    $originalQuestion

    Original Options:
    $optionsJson

    ---------------------------------------
    REWORDING RULES:
    - Keep meaning equivalent but wording different.
    - No reuse of large phrases from the original.
    - Maintain the same knowledge requirement.
    - Do NOT create new facts.
    - Avoid trick questions.
    - Difficulty must remain equivalent.

    ---------------------------------------
    OUTPUT JSON EXACTLY IN THIS FORMAT:

    {
    "reworded_question": {
        "question_text": "Reworded question here (must still start with What or Which)",
        "question_type": "multiple_choice",
        "options": [
        {
            "option_letter": "A",
            "option_text": "Reworded option text",
            "is_correct": true/false
        },
        {
            "option_letter": "B",
            "option_text": "...",
            "is_correct": true/false
        },
        {
            "option_letter": "C",
            "option_text": "...",
            "is_correct": true/false
        },
        {
            "option_letter": "D",
            "option_text": "...",
            "is_correct": true/false
        }
        ],
        "explanation": "1–2 sentence explanation referencing the same concept as the original.",
        "regeneration_notes": "Explain the major changes from the original.",
        "maintains_equivalence": true
    }
    }

    STRICT REQUIREMENTS:
    - The correct answer MUST remain correct after rewording.
    - The answer choices MUST be randomly shuffled.
    - The question MUST be reworded, not minimally edited.
    - Only 'What' or 'Which' may start the question.
    PROMPT;
    }


    /**
     * Build feedback generation prompt (kept, cleaned)
     */
    private function buildFeedbackPrompt(array $quizAttemptData, array $userMasteryData): string
    {
        $attemptJson = json_encode($quizAttemptData, JSON_PRETTY_PRINT);
        $masteryJson = json_encode($userMasteryData, JSON_PRETTY_PRINT);

        return <<<PROMPT
    Generate personalized, constructive feedback for a student based on their quiz performance and mastery data.

    Quiz Attempt Data:
    $attemptJson

    Student Mastery Data:
    $masteryJson

    Return JSON only in this format:
    {
        "overall_feedback": "encouraging overall assessment",
        "score_interpretation": "what the score indicates",
        "strengths": [
            {
                "area": "topic or skill",
                "description": "what the student did well",
                "evidence": "specific questions or patterns"
            }
        ],
        "areas_for_improvement": [
            {
                "area": "topic or skill",
                "current_level": "description",
                "gap_analysis": "what's missing",
                "priority": "high|medium|low"
            }
        ],
        "specific_recommendations": [
            {
                "recommendation": "actionable study suggestion",
                "topic": "related topic",
                "estimated_time": "time needed",
                "resources": ["suggested study materials or approaches"]
            }
        ],
        "next_steps": [
            "immediate action 1",
            "immediate action 2",
            "immediate action 3"
        ],
        "motivational_message": "encouraging closing message",
        "estimated_mastery_timeline": "realistic timeline for improvement"
    }

    Tone: Supportive, constructive, actionable.
    PROMPT;
    }


    /**
     * Build OBTL parsing prompt (improved)
     */
    private function buildObtlParsingPrompt(string $obtlContent): string
    {
        return <<<PROMPT
    From the OBTL document below, extract BOTH:

    1. **Document Title Information**
    - title
    - course_code (if any)
    - subtitle (if any)
    - full_title (course_code + title if applicable)
    - confidence ("high" | "medium" | "low")

    2. **Learning Outcomes**
    For each outcome:
    - outcome_code (if present)
    - outcome_statement
    - cognitive_level (remember|understand|apply|analyze|evaluate|create)
    - bloom_category
    - suggested_assessment_methods

    Return JSON ONLY in this format:

    {
    "title_info": {
        "title": "",
        "course_code": "",
        "subtitle": "",
        "full_title": "",
        "confidence": ""
    },
    "learning_outcomes": [
        {
        "outcome_code": "",
        "outcome_statement": "",
        "cognitive_level": "",
        "bloom_category": "",
        "suggested_assessment_methods": []
        }
    ]
    }

    OBTL Document:
    $obtlContent
    PROMPT;
    }


    /* ---------------------------------------------------------
     | Post-response validation & utilities (strict quiz logic)
     | --------------------------------------------------------- */

    /**
     * Post-response validator for quiz generation and general JSON extraction
     *
     * Returns [bool $isValid, array $report, array|null $decoded]
     */
    protected function postResponseValidator(string $modelText, string $materialContent, array $topics): array
    {
        $report = [
            'json_valid' => false,
            'schema_errors' => [],
            'factual_errors' => [],
            'form_errors' => [],
            'notes' => [],
        ];

        // Attempt to extract JSON blob
        $jsonString = $this->extractJsonFromText($modelText);
        if ($jsonString === null) {
            $report['schema_errors'][] = 'No JSON object found in model output.';
            return [false, $report, null];
        }

        $decoded = json_decode($jsonString, true);
        if ($decoded === null) {
            $report['schema_errors'][] = 'Invalid JSON: ' . json_last_error_msg();
            return [false, $report, null];
        }

        $report['json_valid'] = true;

        // Basic top-level validation
        if (!isset($decoded['questions']) || !is_array($decoded['questions'])) {
            $report['schema_errors'][] = 'Missing or invalid "questions" array.';
            return [false, $report, null];
        }

        if (!isset($decoded['metadata']) || !is_array($decoded['metadata'])) {
            $report['schema_errors'][] = 'Missing or invalid "metadata" object.';
            return [false, $report, null];
        }

        $requested = $decoded['metadata']['total_requested'] ?? null;
        $totalRequested = collect($topics)->sum('num_items');
        if ($requested !== $totalRequested) {
            $report['schema_errors'][] = "metadata.total_requested ($requested) does not match topics total ($totalRequested).";
            // continue validating the returned questions anyway
        }

        $topicsList = collect($topics)->pluck('name')->map(fn($n)=>trim($n))->values()->all();

        $validCount = 0;
        foreach ($decoded['questions'] as $idx => $q) {
            $qIndex = $idx + 1;

            // check required fields
            $required = ['question_text','question_type','difficulty','topic','cognitive_level','options','explanation','source_excerpt'];
            foreach ($required as $f) {
                if (!array_key_exists($f, $q)) {
                    $report['schema_errors'][] = "Question #{$qIndex} missing required field: {$f}.";
                }
            }

            // type check
            if (($q['question_type'] ?? '') !== 'multiple_choice') {
                $report['form_errors'][] = "Question #{$qIndex} question_type must be 'multiple_choice'.";
            }

            // question_text starts with What/Which
            // if (!isset($q['question_text']) || !preg_match('/^(What|Which)\b/i', trim($q['question_text']))) {
            //     $report['form_errors'][] = "Question #{$qIndex} question_text must begin with 'What' or 'Which'.";
            // }

            // banned interrogatives
            if (preg_match('/\b(How|Why|Where|When|Explain|Describe)\b/i', $q['question_text'])) {
                $report['form_errors'][] = "Question #{$qIndex} contains banned interrogative.";
            }

            // topic validity
            if (!in_array($q['topic'], $topicsList, true)) {
                $report['form_errors'][] = "Question #{$qIndex} topic '{$q['topic']}' is not in topics list.";
            }

            // options: 4, letters A-D, exactly one correct
            if (!isset($q['options']) || !is_array($q['options']) || count($q['options']) !== 4) {
                $report['schema_errors'][] = "Question #{$qIndex} must have exactly 4 options.";
            } else {
                $letters = ['A','B','C','D'];
                $correctCount = 0;
                foreach ($q['options'] as $opt) {
                    if (!isset($opt['option_letter']) || !in_array($opt['option_letter'], $letters, true)) {
                        $report['schema_errors'][] = "Question #{$qIndex} has invalid option_letter.";
                    }
                    if (!isset($opt['option_text'])) {
                        $report['schema_errors'][] = "Question #{$qIndex} option missing option_text.";
                    }
                    if (!array_key_exists('is_correct', $opt)) {
                        $report['schema_errors'][] = "Question #{$qIndex} option missing is_correct.";
                    } elseif ($opt['is_correct']) {
                        $correctCount++;
                    }
                }
                if ($correctCount !== 1) {
                    $report['schema_errors'][] = "Question #{$qIndex} must have exactly one correct option; found {$correctCount}.";
                }
            }

            // source_excerpt checks
            $excerpt = $q['source_excerpt'] ?? '';
            if (empty($excerpt)) {
                $report['factual_errors'][] = "Question #{$qIndex} source_excerpt is empty.";
            } else {
                $wordCount = str_word_count(strip_tags($excerpt));
                if ($wordCount > 40) {
                    $report['factual_errors'][] = "Question #{$qIndex} source_excerpt exceeds 40 words ({$wordCount}).";
                }
                if (stripos($materialContent, $excerpt) === false && stripos($materialContent, trim($excerpt, " \n\r\t.,;:")) === false) {
                    $report['factual_errors'][] = "Question #{$qIndex} source_excerpt not found verbatim in material.";
                }
            }

            // correct option appears verbatim in source_excerpt
            if (isset($q['options']) && is_array($q['options'])) {
                $correctOptionText = null;
                foreach ($q['options'] as $opt) {
                    if (!empty($opt['is_correct'])) {
                        $correctOptionText = $opt['option_text'];
                        break;
                    }
                }
                if ($correctOptionText === null) {
                    $report['schema_errors'][] = "Question #{$qIndex} missing correct option.";
                } else {

                    // Normalize for safer comparison
                    $excerptLower = Str::lower($excerpt);
                    $optionLower = Str::lower($correctOptionText);

                    // Basic check — case-insensitive direct match
                    if (!Str::contains($excerptLower, $optionLower)) {

                        // ADVANCED fallback: allow key noun matching (avoid false failures)
                        $keywords = preg_split('/\s+/', $optionLower);
                        $matched = false;

                        foreach ($keywords as $word) {
                            if (strlen($word) > 3 && Str::contains($excerptLower, $word)) {
                                $matched = true;
                                break;
                            }
                        }

                        if (!$matched) {
                            $report['factual_errors'][] =
                                "Question #{$qIndex} correct option text does not appear in source_excerpt (case-insensitive partial match also failed).";
                        }
                    }

                }
            }

            // explanation length & reference check
            $explanation = $q['explanation'] ?? '';
            if (empty($explanation)) {
                $report['schema_errors'][] = "Question #{$qIndex} explanation is empty.";
            } else {
                if (str_word_count($explanation) > 40) {
                    $report['schema_errors'][] = "Question #{$qIndex} explanation exceeds 40 words.";
                }
                // ensure explanation includes some token from excerpt
                $excerptTokens = array_filter(array_unique(array_map('strtolower', preg_split('/\W+/', strip_tags($excerpt)))), fn($t)=>strlen($t) > 3);
                $matchFound = false;
                foreach ($excerptTokens as $tok) {
                    if ($tok && stripos($explanation, $tok) !== false) {
                        $matchFound = true;
                        break;
                    }
                }
                if (!$matchFound) {
                    $report['factual_errors'][] = "Question #{$qIndex} explanation does not reference the source_excerpt tokens.";
                }
            }

            // question-level decision
            $questionHasErrors = false;
            foreach (['schema_errors','form_errors','factual_errors'] as $k) {
                foreach ($report[$k] as $e) {
                    if (str_contains($e, "Question #{$qIndex}")) {
                        $questionHasErrors = true;
                        break 2;
                    }
                }
            }

            if (!$questionHasErrors) {
                $validCount++;
            }
        } // end foreach question

        // final decision
        $allGood = ($validCount === count($decoded['questions'])) && empty($report['schema_errors']) && empty($report['form_errors']) && empty($report['factual_errors']);

        $report['notes'][] = "Valid questions: {$validCount} / " . count($decoded['questions']);

        return [$allGood, $report, $decoded];
    }

    /**
     * Extract JSON blob from possibly noisy assistant response.
     * Returns JSON string or null.
     */
    protected function extractJsonFromText(string $text): ?string
    {
        $text = trim($text);

        // direct JSON
        if (Str::startsWith($text, '{') || Str::startsWith($text, '[')) {
            if (json_decode($text) !== null) {
                return $text;
            }
        }

        // try find JSON object
        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $candidate = substr($text, $first, $last - $first + 1);
            if (json_decode($candidate) !== null) {
                return $candidate;
            }
        }

        // try array root
        $first = strpos($text, '[');
        $last = strrpos($text, ']');
        if ($first !== false && $last !== false && $last > $first) {
            $candidate = substr($text, $first, $last - $first + 1);
            if (json_decode($candidate) !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Build regeneration prompt (appends validation report and last output)
     */
    protected function buildRegenerationPrompt(string $basePrompt, string $lastModelOutput, array $validationReport): string
    {
        $reportSummary = json_encode($validationReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $lastSnippet = substr($lastModelOutput, 0, 2000); // keep it bounded

        return <<<INSTR
{$basePrompt}

-- REGENERATION GUIDANCE --
The previous model output failed validation for these reasons:
{$reportSummary}

Previous model output (truncated):
{$lastSnippet}

Please regenerate the full JSON output EXACTLY following the original schema and fix all reported issues. Return only the corrected JSON object.
INSTR;
    }

    /* ---------------------------------------------------------
     | Core HTTP send and logging
     | --------------------------------------------------------- */

    /**
     * Core method to send requests to OpenAI API
     *
     * Returns array with keys: content (string), usage (array), model (string)
     */
    private function sendRequest(array $payload, string $requestType, int $attemptNumber = 1): array
    {
        $startTime = microtime(true);

        try {
            // Ensure model is present in payload
            if (!isset($payload['model'])) {
                $payload['model'] = $this->model;
            }

            // If messages not provided (some callers pass messages inside payload), keep as is
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                $body = $response->body();
                throw new Exception("OpenAI API error: HTTP {$response->status()} - {$body}");
            }

            $data = $response->json();
            $endTime = microtime(true);

            $usage = $data['usage'] ?? ['total_tokens' => 0, 'prompt_tokens' => 0, 'completion_tokens' => 0];

            // Log API usage record (best-effort)
            $this->logApiUsage(
                $requestType,
                $usage['total_tokens'] ?? 0,
                $usage['prompt_tokens'] ?? 0,
                $usage['completion_tokens'] ?? 0,
                ($endTime - $startTime) * 1000,
                true
            );

            // Extract assistant content robustly
            $content = '';
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
            } elseif (isset($data['choices'][0]['text'])) {
                $content = $data['choices'][0]['text'];
            }

            return [
                'content' => $content,
                'usage' => $usage,
                'model' => $data['model'] ?? $payload['model'] ?? $this->model
            ];
        } catch (Exception $e) {
            Log::error('OpenAI API request failed', [
                'request_type' => $requestType,
                'attempt' => $attemptNumber,
                'error' => $e->getMessage()
            ]);

            if ($attemptNumber < $this->maxRetries) {
                sleep((int) pow(2, $attemptNumber)); // exponential backoff
                return $this->sendRequest($payload, $requestType, $attemptNumber + 1);
            }

            // Log failure to DB as well
            $this->logApiUsage($requestType, 0, 0, 0, 0, false, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Log API usage to database (best-effort)
     */
    private function logApiUsage(
        string $requestType,
        int $totalTokens,
        int $promptTokens,
        int $completionTokens,
        float $responseTimeMs,
        bool $success,
        ?string $errorMessage = null
    ): void {
        try {
            $estimatedCost = ($totalTokens / 1000) * $this->costPer1k;
            ChatGptApiLog::create([
                'user_id' => auth()->id() ?? null,
                'request_type' => $requestType,
                'model' => $this->model,
                'total_tokens' => $totalTokens,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'response_time_ms' => $responseTimeMs,
                'estimated_cost' => $estimatedCost,
                'success' => $success,
                'error_message' => $errorMessage,
                'created_at' => now()
            ]);
        } catch (Exception $e) {
            // Don't let logging fail the flow
            Log::error('Failed to log API usage', ['error' => $e->getMessage()]);
        }
    }

    /* ---------------------------------------------------------
     | Utility methods retained from original service
     | --------------------------------------------------------- */

    /**
     * Get API usage statistics for a user
     */
    public function getUserApiStats(?int $userId = null, ?string $dateFrom = null): array
    {
        $userId = $userId ?? auth()->id();

        $query = ChatGptApiLog::where('user_id', $userId);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        return [
            'total_requests' => $query->count(),
            'successful_requests' => $query->where('success', true)->count(),
            'failed_requests' => $query->where('success', false)->count(),
            'total_tokens' => $query->sum('total_tokens'),
            'total_cost' => $query->sum('estimated_cost'),
            'average_response_time' => $query->avg('response_time_ms'),
            'requests_by_type' => $query->groupBy('request_type')
                ->selectRaw('request_type, COUNT(*) as count, SUM(total_tokens) as tokens')
                ->get()
                ->keyBy('request_type')
        ];
    }
}
