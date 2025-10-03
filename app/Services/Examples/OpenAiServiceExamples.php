<?php

namespace App\Services\Examples;

use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;

class OpenAiServiceExamples
{
    private OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Example 1: Analyze uploaded content
     */
    public function analyzeContentExample()
    {
        $content = "
            Introduction to Database Normalization
            
            Database normalization is the process of organizing data in a database to reduce redundancy 
            and improve data integrity. The main objectives are to eliminate redundant data and ensure 
            data dependencies make sense.
            
            First Normal Form (1NF):
            - Each table cell should contain a single value
            - Each record needs to be unique
            
            Second Normal Form (2NF):
            - Must be in 1NF
            - All non-key attributes must be fully dependent on the primary key
            
            Third Normal Form (3NF):
            - Must be in 2NF
            - No transitive dependencies
        ";

        $obtlContext = "
            Learning Outcome: Students should be able to explain database normalization concepts 
            and apply normalization rules to design efficient database schemas.
        ";

        try {
            $analysis = $this->openAiService->analyzeContent($content, $obtlContext);
            
            // Expected output structure:
            // [
            //     'key_concepts' => ['database normalization', 'redundancy', 'data integrity', ...],
            //     'main_topics' => [...],
            //     'suggested_learning_outcomes' => [...],
            //     'content_summary' => '...',
            //     'prerequisite_knowledge' => [...],
            //     'estimated_learning_time' => '...'
            // ]
            Log::debug("Data:", $analysis);
            return $analysis;
        } catch (\Exception $e) {
            Log::error('Content analysis failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 2: Generate Table of Specification
     */
    public function generateToSExample()
    {
        $learningOutcomes = [
            [
                'outcome' => 'Define database normalization and its purpose',
                'bloom_level' => 'remember',
                'category' => 'knowledge'
            ],
            [
                'outcome' => 'Explain the differences between 1NF, 2NF, and 3NF',
                'bloom_level' => 'understand',
                'category' => 'comprehension'
            ],
            [
                'outcome' => 'Apply normalization rules to design database schemas',
                'bloom_level' => 'apply',
                'category' => 'application'
            ]
        ];

        $materialSummary = "This material covers database normalization concepts, including the 
                            first three normal forms and their practical applications.";

        try {
            $tos = $this->openAiService->generateToS($learningOutcomes, $materialSummary, 20);
            
            // Expected output structure:
            // [
            //     'table_of_specification' => [
            //         [
            //             'subtopic' => 'Database Normalization Basics',
            //             'learning_outcome' => 'Define database normalization...',
            //             'cognitive_level' => 'remember',
            //             'num_items' => 5,
            //             'weight_percentage' => 25,
            //             ...
            //         ],
            //         ...
            //     ],
            //     'cognitive_distribution' => [...],
            //     'total_items' => 20
            // ]
            
            return $tos;
        } catch (\Exception $e) {
            Log::error('ToS generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 3: Generate quiz questions
     */
    public function generateQuizExample()
    {
        $tosItems = [
            [
                'subtopic' => 'Database Normalization Basics',
                'learning_outcome' => 'Define database normalization',
                'cognitive_level' => 'remember',
                'num_items' => 5
            ],
            [
                'subtopic' => 'Normal Forms',
                'learning_outcome' => 'Explain 1NF, 2NF, and 3NF',
                'cognitive_level' => 'understand',
                'num_items' => 8
            ],
            [
                'subtopic' => 'Practical Application',
                'learning_outcome' => 'Apply normalization rules',
                'cognitive_level' => 'apply',
                'num_items' => 7
            ]
        ];

        $materialContent = "Database normalization content here...";

        try {
            $quiz = $this->openAiService->generateQuizQuestions($tosItems, $materialContent, 20);
            
            // Expected output structure:
            // [
            //     'questions' => [
            //         [
            //             'question_number' => 1,
            //             'subtopic' => 'Database Normalization Basics',
            //             'cognitive_level' => 'remember',
            //             'question_text' => 'What is the primary purpose of database normalization?',
            //             'options' => [
            //                 ['option_letter' => 'A', 'option_text' => '...', 'is_correct' => true, ...],
            //                 ['option_letter' => 'B', 'option_text' => '...', 'is_correct' => false, ...],
            //                 ...
            //             ],
            //             'explanation' => '...',
            //             'estimated_difficulty' => 0.3,
            //             'time_estimate_seconds' => 60
            //         ],
            //         ...
            //     ],
            //     'distribution_check' => ['remember' => 5, 'understand' => 8, 'apply' => 7]
            // ]
            
            return $quiz;
        } catch (\Exception $e) {
            Log::error('Quiz generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 4: Reword a question (regeneration)
     */
    public function rewordQuestionExample()
    {
        $originalQuestion = "What is the primary purpose of database normalization?";
        
        $originalOptions = [
            [
                'option_letter' => 'A',
                'option_text' => 'To reduce data redundancy and improve data integrity',
                'is_correct' => true
            ],
            [
                'option_letter' => 'B',
                'option_text' => 'To increase database size',
                'is_correct' => false
            ],
            [
                'option_letter' => 'C',
                'option_text' => 'To make queries run slower',
                'is_correct' => false
            ],
            [
                'option_letter' => 'D',
                'option_text' => 'To create more tables',
                'is_correct' => false
            ]
        ];

        $regenerationCount = 1; // First regeneration (max 3)

        try {
            $reworded = $this->openAiService->rewordQuestion(
                $originalQuestion, 
                $originalOptions, 
                $regenerationCount
            );
            
            // Expected output structure:
            // [
            //     'reworded_question' => [
            //         'question_text' => 'Which statement best describes why database normalization is used?',
            //         'options' => [...],
            //         'explanation' => '...',
            //         'regeneration_notes' => 'Changed question structure from "What is" to "Which statement"...',
            //         'maintains_equivalence' => true
            //     ]
            // ]
            
            return $reworded;
        } catch (\Exception $e) {
            Log::error('Question reword failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 5: Generate personalized feedback
     */
    public function generateFeedbackExample()
    {
        $quizAttemptData = [
            'quiz_id' => 1,
            'user_id' => 123,
            'score' => 14,
            'total_questions' => 20,
            'percentage' => 70,
            'time_taken' => 18, // minutes
            'responses' => [
                [
                    'question' => 'What is database normalization?',
                    'subtopic' => 'Basics',
                    'cognitive_level' => 'remember',
                    'is_correct' => true,
                    'time_spent' => 45
                ],
                [
                    'question' => 'Explain the difference between 2NF and 3NF',
                    'subtopic' => 'Normal Forms',
                    'cognitive_level' => 'understand',
                    'is_correct' => false,
                    'time_spent' => 70
                ],
                // ... more responses
            ],
            'subtopic_performance' => [
                'Basics' => ['correct' => 4, 'total' => 5, 'percentage' => 80],
                'Normal Forms' => ['correct' => 5, 'total' => 8, 'percentage' => 62.5],
                'Practical Application' => ['correct' => 5, 'total' => 7, 'percentage' => 71.4]
            ]
        ];

        $userMasteryData = [
            'current_ability_estimate' => 0.3, // IRT theta
            'previous_ability_estimate' => 0.1,
            'improvement_trend' => 'improving',
            'attempts_count' => 3,
            'subtopic_mastery' => [
                'Basics' => ['mastery_level' => 0.8, 'status' => 'proficient'],
                'Normal Forms' => ['mastery_level' => 0.5, 'status' => 'developing'],
                'Practical Application' => ['mastery_level' => 0.6, 'status' => 'developing']
            ]
        ];

        try {
            $feedback = $this->openAiService->generateFeedback($quizAttemptData, $userMasteryData);
            
            // Expected output structure:
            // [
            //     'overall_feedback' => 'Great progress! You scored 70%...',
            //     'score_interpretation' => '...',
            //     'strengths' => [...],
            //     'areas_for_improvement' => [...],
            //     'specific_recommendations' => [...],
            //     'next_steps' => [...],
            //     'motivational_message' => '...',
            //     'estimated_mastery_timeline' => '...'
            // ]
            
            return $feedback;
        } catch (\Exception $e) {
            Log::error('Feedback generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 6: Parse OBTL document
     */
    public function parseObtlExample()
    {
        $obtlContent = "
            Course: Database Systems (CS301)
            
            Course Learning Outcomes:
            CLO1: Define fundamental database concepts and terminology
            CLO2: Explain the principles of database design and normalization
            CLO3: Apply SQL to create, query, and manage databases
            CLO4: Analyze database performance and optimization strategies
            
            Assessment Methods:
            - Quizzes: 20%
            - Midterm Exam: 30%
            - Project: 30%
            - Final Exam: 20%
        ";

        try {
            $parsed = $this->openAiService->parseObtlDocument($obtlContent);
            
            // Expected output structure:
            // [
            //     'course_info' => [
            //         'course_code' => 'CS301',
            //         'course_title' => 'Database Systems',
            //         'description' => '...'
            //     ],
            //     'learning_outcomes' => [
            //         [
            //             'outcome_code' => 'CLO1',
            //             'outcome_statement' => 'Define fundamental database concepts...',
            //             'cognitive_level' => 'remember',
            //             'bloom_category' => 'knowledge',
            //             ...
            //         ],
            //         ...
            //     ],
            //     'competencies' => [...],
            //     'prerequisites' => [...],
            //     'assessment_criteria' => [...]
            // ]
            
            return $parsed;
        } catch (\Exception $e) {
            Log::error('OBTL parsing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 7: Check API usage statistics
     */
    public function checkApiUsageExample()
    {
        try {
            $stats = $this->openAiService->getUserApiStats();
            
            // Expected output:
            // [
            //     'total_requests' => 150,
            //     'successful_requests' => 145,
            //     'failed_requests' => 5,
            //     'total_tokens' => 450000,
            //     'total_cost' => 67.50,
            //     'average_response_time' => 2500.5,
            //     'requests_by_type' => [
            //         'content_analysis' => ['count' => 30, 'tokens' => 90000],
            //         'quiz_generation' => ['count' => 50, 'tokens' => 200000],
            //         ...
            //     ]
            // ]
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('API usage check failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 8: Using in a controller
     */
    public function controllerIntegrationExample()
    {
        // In your controller:
        
        /*
        use App\Services\OpenAiService;

        class MaterialController extends Controller
        {
            public function __construct(private OpenAiService $openAiService) {}

            public function processUpload(Request $request)
            {
                // 1. Store the uploaded file
                $file = $request->file('material');
                $content = file_get_contents($file->getPathname());
                
                // 2. Analyze content
                $analysis = $this->openAiService->analyzeContent($content);
                
                // 3. Store analysis results
                $material = Material::create([
                    'file_path' => $file->store('materials'),
                    'processing_status' => 'analyzed'
                ]);
                
                AiAnalysis::create([
                    'material_id' => $material->id,
                    'extracted_content' => json_encode($analysis),
                    'key_concepts' => json_encode($analysis['key_concepts'])
                ]);
                
                // 4. Generate ToS
                $tos = $this->openAiService->generateToS(
                    $analysis['suggested_learning_outcomes'],
                    $analysis['content_summary']
                );
                
                // 5. Generate quiz
                $quiz = $this->openAiService->generateQuizQuestions(
                    $tos['table_of_specification'],
                    $content
                );
                
                return response()->json([
                    'message' => 'Material processed successfully',
                    'material_id' => $material->id,
                    'questions_generated' => count($quiz['questions'])
                ]);
            }
        }
        */
    }
}