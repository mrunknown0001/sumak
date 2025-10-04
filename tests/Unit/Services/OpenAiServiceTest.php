<?php

namespace Tests\Unit\Services;

use App\Services\OpenAiService;
use App\Models\ChatGptApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiServiceTest extends TestCase
{
    use RefreshDatabase;

    private OpenAiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OpenAiService::class);
    }

    /** @test */
    public function it_analyzes_content_successfully()
    {
        // Mock the HTTP response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'key_concepts' => ['database', 'normalization'],
                                'main_topics' => [
                                    [
                                        'topic' => 'Database Normalization',
                                        'subtopics' => ['1NF', '2NF', '3NF'],
                                        'cognitive_level' => 'understand',
                                        'estimated_difficulty' => 'medium'
                                    ]
                                ],
                                'content_summary' => 'Introduction to database normalization',
                                'estimated_learning_time' => '45 minutes'
                            ])
                        ]
                    ]
                ],
                'usage' => [
                    'total_tokens' => 1000,
                    'prompt_tokens' => 600,
                    'completion_tokens' => 400
                ],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $content = "Database normalization is a process...";
        $result = $this->service->analyzeContent($content);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key_concepts', $result);
        $this->assertArrayHasKey('main_topics', $result);
        $this->assertEquals(['database', 'normalization'], $result['key_concepts']);

        // Check if API usage was logged
        $this->assertDatabaseHas('chat_gpt_api_logs', [
            'request_type' => 'content_analysis',
            'success' => true,
            'total_tokens' => 1000
        ]);
    }

    /** @test */
    public function it_generates_table_of_specification()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'table_of_specification' => [
                                    [
                                        'subtopic' => 'Database Basics',
                                        'cognitive_level' => 'remember',
                                        'num_items' => 5,
                                        'weight_percentage' => 25
                                    ]
                                ],
                                'total_items' => 20
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 800, 'prompt_tokens' => 500, 'completion_tokens' => 300],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $learningOutcomes = [
            ['outcome' => 'Define normalization', 'bloom_level' => 'remember']
        ];
        
        $result = $this->service->generateToS($learningOutcomes, 'Summary', 20);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('table_of_specification', $result);
        $this->assertEquals(20, $result['total_items']);
    }

    /** @test */
    public function it_generates_quiz_questions()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'questions' => [
                                    [
                                        'question_number' => 1,
                                        'question_text' => 'What is normalization?',
                                        'options' => [
                                            ['option_letter' => 'A', 'option_text' => 'A process', 'is_correct' => true],
                                            ['option_letter' => 'B', 'option_text' => 'A database', 'is_correct' => false],
                                            ['option_letter' => 'C', 'option_text' => 'A table', 'is_correct' => false],
                                            ['option_letter' => 'D', 'option_text' => 'A field', 'is_correct' => false],
                                        ]
                                    ]
                                ]
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 1500, 'prompt_tokens' => 800, 'completion_tokens' => 700],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $tosItems = [['subtopic' => 'Basics', 'num_items' => 1]];
        $result = $this->service->generateQuizQuestions($tosItems, 'Content', 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertCount(1, $result['questions']);
    }

    /** @test */
    public function it_rewords_questions()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'reworded_question' => [
                                    'question_text' => 'How would you describe normalization?',
                                    'options' => [
                                        ['option_letter' => 'A', 'option_text' => 'A method', 'is_correct' => true],
                                        ['option_letter' => 'B', 'option_text' => 'A system', 'is_correct' => false],
                                        ['option_letter' => 'C', 'option_text' => 'A tool', 'is_correct' => false],
                                        ['option_letter' => 'D', 'option_text' => 'An object', 'is_correct' => false],
                                    ],
                                    'maintains_equivalence' => true
                                ]
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 600, 'prompt_tokens' => 350, 'completion_tokens' => 250],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $result = $this->service->rewordQuestion(
            'What is normalization?',
            [['option_text' => 'A process', 'is_correct' => true]],
            1
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reworded_question', $result);
        $this->assertTrue($result['reworded_question']['maintains_equivalence']);
    }

    /** @test */
    public function it_generates_personalized_feedback()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'overall_feedback' => 'Great effort!',
                                'strengths' => [['area' => 'Basics', 'description' => 'Strong understanding']],
                                'areas_for_improvement' => [['area' => 'Advanced', 'priority' => 'high']],
                                'specific_recommendations' => [['recommendation' => 'Review chapter 3']],
                                'next_steps' => ['Practice more questions', 'Review notes']
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 900, 'prompt_tokens' => 400, 'completion_tokens' => 500],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $attemptData = ['score' => 15, 'total_questions' => 20];
        $masteryData = ['current_ability_estimate' => 0.5];
        
        $result = $this->service->generateFeedback($attemptData, $masteryData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_feedback', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('next_steps', $result);
    }

    /** @test */
    public function it_retries_on_failure()
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('Server error', 500)
                ->push('Server error', 500)
                ->push([
                    'choices' => [
                        ['message' => ['content' => json_encode(['key_concepts' => ['test']])]]
                    ],
                    'usage' => ['total_tokens' => 100, 'prompt_tokens' => 50, 'completion_tokens' => 50],
                    'model' => 'gpt-4o-mini'
                ], 200)
        ]);

        $result = $this->service->analyzeContent('Test content');

        $this->assertIsArray($result);
        // Should succeed on third attempt
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_logs_failed_requests()
    {
        Http::fake([
            'api.openai.com/*' => Http::response('Server error', 500)
        ]);

        $this->expectException(\Exception::class);

        try {
            $this->service->analyzeContent('Test content');
        } catch (\Exception $e) {
            // Check if failure was logged
            $this->assertDatabaseHas('chat_gpt_api_logs', [
                'request_type' => 'content_analysis',
                'success' => false
            ]);
            
            throw $e;
        }
    }

    /** @test */
    public function it_tracks_api_usage_statistics()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        ChatGptApiLog::factory()->count(10)->create([
            'user_id' => $user->id,
            'request_type' => 'content_analysis',
            'total_tokens' => 1000,
            'estimated_cost' => 0.15
        ]);

        $stats = $this->service->getUserApiStats($user->id);

        $this->assertEquals(10, $stats['total_requests']);
        $this->assertEquals(10000, $stats['total_tokens']);
        $this->assertEquals(1.50, $stats['total_cost']);
    }

    /** @test */
    public function it_parses_obtl_documents()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'course_info' => ['course_code' => 'CS301'],
                                'learning_outcomes' => [
                                    [
                                        'outcome_code' => 'CLO1',
                                        'outcome_statement' => 'Define concepts',
                                        'cognitive_level' => 'remember'
                                    ]
                                ]
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 700, 'prompt_tokens' => 400, 'completion_tokens' => 300],
                'model' => 'gpt-4o-mini'
            ], 200)
        ]);

        $result = $this->service->parseObtlDocument('Course: CS301...');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('course_info', $result);
        $this->assertArrayHasKey('learning_outcomes', $result);
        $this->assertEquals('CS301', $result['course_info']['course_code']);
    }
}