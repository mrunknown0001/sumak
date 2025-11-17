<?php

namespace App\Services;

use App\Models\ChatGptApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAiService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $maxRetries;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = config('services.openai.api_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxRetries = config('services.openai.max_retries', 3);
        $this->timeout = config('services.openai.timeout', 120);
    }

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
                    'content' => 'You are an expert educational content analyst. Your job is to extract 5–8 high-level topics from the provided material. 
                    Each topic must include a concise 1–2 sentence description, highlight core concepts, and recommend how many Bloom’s Focus multiple-choice 
                    questions (4–6) should be written for that topic. Never invent details that are not supported by the source material.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object']
        ], 'content_analysis');

        return json_decode($response['content'], true) ?? [];
    }

    /**
     * Generate Table of Specification (ToS) focused on Bloom's
     */
    public function generateToS(array $learningOutcomes, string $materialSummary, int $totalItems = 20): array
    {
        $prompt = $this->buildToSPrompt($learningOutcomes, $materialSummary, $totalItems);
        
        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in creating Tables of Specification for educational assessments, with deep knowledge of Bloom\'s Taxonomy.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            // 'temperature' => 0.4,
            'response_format' => ['type' => 'json_object']
        ], 'tos_generation');

        return json_decode($response['content'], true);
    }

    /**
     * Generate quiz questions based on ToS
     */
    public function generateQuizQuestions(
        array $topics,
        string $materialContent,
        array $questionTypes = ['multiple_choice'],
        array $difficultyLevels = ['easy', 'medium', 'hard'],
        bool $enableValidation = true
    ): array {
        $prompt = $this->buildTopicQuizGenerationPrompt($topics, $materialContent, $questionTypes, $difficultyLevels, $enableValidation);
        
        $response = $this->sendRequest([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educator tasked with generating high-quality, multiple-choice quiz questions based on provided learning material and topics. 
                    Your goal is to create questions that are relevant, meaningful, and directly derived from the content, ensuring they test comprehension, application, 
                    and critical thinking without introducing nonsensical or irrelevant elements.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object']
        ], 'quiz_generation');

        return json_decode($response['content'], true) ?? [];
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
            // 'temperature' => 0.6,
            'response_format' => ['type' => 'json_object']
        ], 'question_reword');

        return json_decode($response['content'], true);
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
                    'content' => 'You are a supportive educational mentor who provides constructive, personalized feedback to help students improve their learning. Your feedback is always encouraging, specific, and actionable.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            // 'temperature' => 0.7,
            'response_format' => ['type' => 'json_object']
        ], 'feedback_generation');

        return json_decode($response['content'], true);
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
            // 'temperature' => 0.2,
            'response_format' => ['type' => 'json_object']
        ], 'obtl_title_extraction');

        return json_decode($response['content'], true);
    }

    /**
     * Build OBTL title extraction prompt
     */
    private function buildObtlTitlePrompt(string $obtlContent): string
    {
        return <<<PROMPT
    Extract the course or document title from the following OBTL (Outcome-Based Teaching and Learning) document.

    OBTL Document:
    $obtlContent

    Please extract the title information in the following JSON format:
    {
        "title": "the main course or document title",
        "course_code": "course code if available (e.g., CS101, MATH201)",
        "subtitle": "subtitle or additional title information if present",
        "full_title": "complete title including code and subtitle if applicable",
        "confidence": "high|medium|low",
        "extraction_notes": "any relevant notes about the title extraction"
    }

    Instructions:
    - Look for explicit title markers like "Course Title:", "Subject:", "Course Name:", etc.
    - If a course code is present, include it separately
    - If no clear title is found, use the most prominent heading or subject matter
    - Indicate confidence level based on how explicit the title information is
    PROMPT;
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
                    'content' => 'You are an expert in Outcome-Based Teaching and Learning (OBTL) who can extract and structure learning outcomes from curriculum documents. Do not include mission, vission, objective of the related insitutions or like.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            // 'temperature' => 0.2,
            'response_format' => ['type' => 'json_object']
        ], 'obtl_parsing');

        return json_decode($response['content'], true);
    }

    /**
     * Core method to send requests to OpenAI API
     */
    private function sendRequest(array $payload, string $requestType, int $attemptNumber = 1): array
    {
        $startTime = microtime(true);
        
        try {
            // Add model to payload
            $payload['model'] = $this->model;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                throw new Exception("OpenAI API error: " . $response->body());
            }

            $data = $response->json();
            $endTime = microtime(true);

            // Log API usage
            $this->logApiUsage(
                $requestType,
                $data['usage']['total_tokens'] ?? 0,
                $data['usage']['prompt_tokens'] ?? 0,
                $data['usage']['completion_tokens'] ?? 0,
                ($endTime - $startTime) * 1000, // Convert to milliseconds
                true
            );

            return [
                'content' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'],
                'model' => $data['model']
            ];

        } catch (Exception $e) {
            Log::error('OpenAI API request failed', [
                'request_type' => $requestType,
                'attempt' => $attemptNumber,
                'error' => $e->getMessage()
            ]);

            // Retry logic
            if ($attemptNumber < $this->maxRetries) {
                sleep(pow(2, $attemptNumber)); // Exponential backoff
                return $this->sendRequest($payload, $requestType, $attemptNumber + 1);
            }

            // Log failed request
            $this->logApiUsage($requestType, 0, 0, 0, 0, false, $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Build content analysis prompt
     */
    private function buildContentAnalysisPrompt(string $content, ?string $obtlContext): string
    {
        $contextSection = $obtlContext
            ? "\n\nOBTL Context:\n$obtlContext"
            : "";

        return <<<PROMPT
Analyze the following learning material and extract only high-level instructional topics suited for Bloom's Focus assessment design.

Learning Material:
$content
$contextSection

Output JSON using this schema:
{
    "content_summary": "2-3 sentence synthesis of the material",
    "topics": [
        {
            "topic": "concise topic title",
            "description": "1-2 sentence overview of what the learner should understand",
            "key_concepts": ["concept A", "concept B"],
            "recommended_question_count": 4-6,
            "cognitive_emphasis": "remember|understand|apply",
            "supporting_notes": "short optional note referencing source details"
        }
    ],
    "analysis_notes": "optional clarifications or assumptions made"
}

Requirements:
- Return between 5 and 8 topics when the material permits; otherwise include all defensibly distinct topics.
- recommended_question_count must be an integer between 4 and 6, inclusive.
- Only include concepts that are explicitly supported by the provided material.
PROMPT;
    }

    /**
     * Build Table of Specification prompt
     */
    private function buildToSPrompt(array $learningOutcomes, string $materialSummary, int $totalItems): string
    {
        $outcomesJson = json_encode($learningOutcomes, JSON_PRETTY_PRINT);

        return <<<PROMPT
Create a Table of Specification (ToS) for a quiz assessment based on the following information.

Learning Outcomes:
$outcomesJson

Material Summary:
$materialSummary

Total Quiz Items: $totalItems

Requirements:
- Focus primarily on Lower-Order Thinking Skills (Bloom's Focus): Remember, Understand, and Apply
- Distribute items across topics proportionally to their importance
- Ensure balanced coverage of all learning outcomes
- Each ToS item should specify the topic, cognitive level, and number of questions

Please provide the ToS in the following JSON format:
{
    "table_of_specification": [
        {
            "topic": "topic name",
            "learning_outcome": "associated learning outcome",
            "cognitive_level": "remember|understand|apply",
            "bloom_category": "knowledge|comprehension|application",
            "num_items": number,
            "weight_percentage": percentage,
            "sample_indicators": ["what students should demonstrate", ...]
        }
    ],
    "cognitive_distribution": {
        "remember": percentage,
        "understand": percentage,
        "apply": percentage
    },
    "total_items": $totalItems,
    "assessment_focus": "brief description of assessment focus"
}

Ensure the sum of num_items equals $totalItems.
PROMPT;
    }

    /**
     * Build quiz generation prompt
     */
    private function buildTopicQuizGenerationPrompt(
        array $topics,
        string $materialContent,
        array $questionTypes = ['multiple_choice'],
        array $difficultyLevels = ['easy', 'medium', 'hard'],
        bool $enableValidation = true
    ): string {
        $topicsJson = json_encode($topics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Calculate total questions needed
        $totalQuestions = collect($topics)->sum('num_items');

        // Build question type instructions
        $questionTypeInstructions = $this->buildQuestionTypeInstructions($questionTypes);

        // Build difficulty instructions
        $difficultyInstructions = $this->buildDifficultyInstructions($difficultyLevels);

        // Build validation instructions
        $validationInstructions = $enableValidation ? $this->buildValidationInstructions() : '';

        return <<<PROMPT
You are an expert educator tasked with generating high-quality quiz questions based on provided learning material and topics. Your goal is to create questions that are relevant, meaningful, and directly derived from the content, ensuring they test comprehension, application, and critical thinking while promoting educational objectives.

### Core Principles:
- **Educational Alignment**: Questions must align with Bloom's Taxonomy levels appropriate for the specified difficulty and cognitive emphasis in topics.
- **Content Accuracy**: Base all questions strictly on the provided material. Do not invent facts, concepts, or scenarios not explicitly supported by the content.
- **Question Variety**: Incorporate diverse question types and difficulty levels as specified to maintain engagement and assess different cognitive skills.
- **Quality Assurance**: Ensure each question is clear, unambiguous, grammatically correct, and pedagogically sound.

### Question Types Supported:
$questionTypeInstructions

### Difficulty Levels:
$difficultyInstructions

### Generation Requirements:
- Generate exactly $totalQuestions questions distributed across the specified topics.
- Balance question types and difficulty levels proportionally to create a comprehensive assessment.
- Each question must include a difficulty level and question type indicator.
- Ensure questions progress logically through topics and maintain consistent quality.

### Output Format:
Respond with a valid JSON array of objects. Each object represents a question with the following flexible structure based on question type:

### Example Context
Earth is the third planet from the Sun, known for being the only known planet to support life.

### Example Question to be generated based on context
What is the third planet from the Sun, know for being the only know planet to support life?

A. Mercury
B. Venus
C. Earth
D. Mars

For multiple_choice questions:
{
  "question_text": "Clear, concise question",
  "question_type": "multiple_choice",
  "difficulty": "easy|medium|hard",
  "topic": "associated topic name",
  "cognitive_level": "remember|understand|apply",
  "options": [
    {"option_letter": "A", "option_text": "Option text", "is_correct": true},
    {"option_letter": "B", "option_text": "Distractor", "is_correct": false},
    {"option_letter": "C", "option_text": "Distractor", "is_correct": false},
    {"option_letter": "D", "option_text": "Distractor", "is_correct": false}
  ],
  "explanation": "Brief explanation of correct answer"
}

$validationInstructions

### Topics:
$topicsJson

### Material Content:
$materialContent

Generate $totalQuestions questions following all guidelines above. Ensure comprehensive coverage and educational effectiveness.
PROMPT;
    }

    /**
     * Build question type specific instructions
     */
    private function buildQuestionTypeInstructions(array $questionTypes): string
    {
        $instructions = [];

        if (in_array('multiple_choice', $questionTypes)) {
            $instructions[] = "**Multiple Choice**: Provide 4 options (A-D) with one correct answer and three plausible distractors. Distractors should be based on common misconceptions or related incorrect information from the material.";
        }

        return implode("\n", $instructions);
    }

    /**
     * Build difficulty level instructions
     */
    private function buildDifficultyInstructions(array $difficultyLevels): string
    {
        $instructions = [];

        if (in_array('easy', $difficultyLevels)) {
            $instructions[] = "**Easy**: Basic recall and recognition of facts, definitions, or simple concepts from the material.";
        }

        if (in_array('medium', $difficultyLevels)) {
            $instructions[] = "**Medium**: Understanding and application of concepts, requiring explanation or connection of ideas.";
        }

        if (in_array('hard', $difficultyLevels)) {
            $instructions[] = "**Hard**: Analysis, evaluation, or synthesis requiring critical thinking and deeper comprehension.";
        }

        return implode("\n", $instructions);
    }

    /**
     * Build validation instructions for content quality
     */
    private function buildValidationInstructions(): string
    {
        return <<<VALIDATION
### Content Validation Checklist:
Before finalizing questions, validate each one against these criteria:
- **Accuracy**: Does the question and correct answer directly reflect information in the material?
- **Clarity**: Is the question unambiguous and clearly worded?
- **Relevance**: Does it directly relate to the assigned topic and learning objectives?
- **Cognitive Appropriateness**: Does the difficulty level match the cognitive demands?
- **Distractor Quality** (for multiple choice): Are incorrect options plausible but clearly wrong?
- **Educational Value**: Does the question promote meaningful learning rather than trivial recall?
- **Non-Contradiction**: Does nothing in the question contradict the source material?

If any question fails validation, revise it immediately to meet all criteria.
VALIDATION;
    }

    /**
     * Build question reword prompt
     */
    private function buildRewordPrompt(string $originalQuestion, array $originalOptions, int $regenerationCount): string
    {
        $optionsJson = json_encode($originalOptions, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Create an alternative version of the following quiz question. This is regeneration attempt #$regenerationCount (maximum 3 allowed).

Original Question:
$originalQuestion

Original Options:
$optionsJson

Requirements:
1. Maintain the SAME learning objective and cognitive level
2. Maintain the SAME difficulty level
3. Use different wording and phrasing
4. If regeneration count > 1, make sure it's substantially different from previous versions
5. Keep the same correct answer concept but reword it
6. Create new plausible distractors addressing similar misconceptions
7. Ensure the question tests the same knowledge/skill

Please provide the reworded question in the following JSON format:
{
    "reworded_question": {
        "question_text": "the reworded question",
        "options": [
            {
                "option_letter": "C",
                "option_text": "reworded option",
                "is_correct": true/false,
                "rationale": "explanation"
            }
        ],
        "explanation": "explanation of correct answer",
        "regeneration_notes": "what was changed from the original",
        "maintains_equivalence": true/false
    }
}
PROMPT;
    }

    /**
     * Build feedback generation prompt
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

Please provide comprehensive feedback in the following JSON format:
{
    "overall_feedback": "encouraging overall assessment",
    "score_interpretation": "what the score indicates about learning",
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
            "current_level": "description of current understanding",
            "gap_analysis": "what's missing",
            "priority": "high|medium|low"
        }
    ],
    "specific_recommendations": [
        {
            "recommendation": "actionable study suggestion",
            ""topic: "related topic",
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

Tone: Supportive, constructive, and encouraging. Focus on growth mindset and actionable steps.
PROMPT;
    }

    /**
     * Build OBTL parsing prompt
     */
    private function buildObtlParsingPrompt(string $obtlContent): string
    {
        return <<<PROMPT
Extract and structure learning outcomes from the following OBTL (Outcome-Based Teaching and Learning) document.

OBTL Document:
$obtlContent

Please extract the information in the following JSON format:
{
    "course_info": {
        "course_code": "code if available",
        "course_title": "title",
        "description": "course description"
    },
    "learning_outcomes": [
        {
            "outcome_code": "LO1, CLO1, etc.",
            "outcome_statement": "complete learning outcome statement",
            "cognitive_level": "remember|understand|apply|analyze|evaluate|create",
            "bloom_category": "specific Bloom's taxonomy category",
            "domain": "cognitive|affective|psychomotor",
            "assessment_methods": ["suggested assessment methods"],
            "keywords": ["key verbs and concepts"]
        }
    ],
    "competencies": [
        {
            "competency": "competency statement",
            "related_outcomes": ["outcome codes"],
            "level": "introductory|intermediate|advanced"
        }
    ],
    "prerequisites": ["prerequisite knowledge or skills"],
    "assessment_criteria": ["criteria for measuring achievement"]
}

Extract all explicit learning outcomes and infer the cognitive levels based on the action verbs used.
PROMPT;
    }

    /**
     * Log API usage to database
     */
    private function logApiUsage(
        string $requestType, 
        int $totalTokens, 
        int $promptTokens, 
        int $completionTokens,
        float $responseTime,
        bool $success,
        ?string $errorMessage = null
    ): void {
        try {
            $costPer1kTokens = config('services.openai.cost_per_1k_tokens', 0.002);
            $estimatedCost = ($totalTokens / 1000) * $costPer1kTokens;

            ChatGptApiLog::create([
                'user_id' => auth()->id(),
                'request_type' => $requestType,
                'model' => $this->model,
                'total_tokens' => $totalTokens,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'response_time_ms' => $responseTime,
                'estimated_cost' => $estimatedCost,
                'success' => $success,
                'error_message' => $errorMessage,
                'created_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log API usage', ['error' => $e->getMessage()]);
        }
    }

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