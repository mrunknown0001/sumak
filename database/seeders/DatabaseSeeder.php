<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Document;
use App\Models\Topic;
use App\Models\Subtopic;
use App\Models\TableOfSpecification;
use App\Models\TosItem;
use App\Models\ItemBank;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            // Add other seeders here if needed
            UserSeeder::class,
        ]);

        // Create test users
        $student = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'student@sumakquiz.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@sumakquiz.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create sample course
        $course = Course::create([
            'user_id' => $student->id,
            'course_code' => 'CS101',
            'course_title' => 'Introduction to Computer Science',
            'description' => 'Fundamental concepts in computer science including programming, algorithms, and data structures.',
        ]);

        // Create sample document
        $document = Document::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'title' => 'Lecture 1: Computer Fundamentals',
            'file_path' => 'documents/sample_lecture.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024000,
            'content_summary' => 'Introduction to computer hardware, software, and basic programming concepts.',
            'uploaded_at' => now(),
        ]);

        // Create topics
        $topic1 = Topic::create([
            'document_id' => $document->id,
            'name' => 'Computer Hardware',
            'order_index' => 0,
        ]);

        $topic2 = Topic::create([
            'document_id' => $document->id,
            'name' => 'Computer Software',
            'order_index' => 1,
        ]);

        $topic3 = Topic::create([
            'document_id' => $document->id,
            'name' => 'Programming Basics',
            'order_index' => 2,
        ]);

        // Create subtopics
        $subtopic1 = Subtopic::create([
            'topic_id' => $topic1->id,
            'name' => 'Input and Output Devices',
            'order_index' => 0,
        ]);

        $subtopic2 = Subtopic::create([
            'topic_id' => $topic1->id,
            'name' => 'Central Processing Unit',
            'order_index' => 1,
        ]);

        $subtopic3 = Subtopic::create([
            'topic_id' => $topic2->id,
            'name' => 'System Software',
            'order_index' => 0,
        ]);

        $subtopic4 = Subtopic::create([
            'topic_id' => $topic2->id,
            'name' => 'Application Software',
            'order_index' => 1,
        ]);

        $subtopic5 = Subtopic::create([
            'topic_id' => $topic3->id,
            'name' => 'Variables and Data Types',
            'order_index' => 0,
        ]);

        // Create Table of Specification
        $tos = TableOfSpecification::create([
            'document_id' => $document->id,
            'total_items' => 20,
            'lots_percentage' => 100.00,
            'cognitive_level_distribution' => [
                'remember' => 50,
                'understand' => 50,
            ],
            'assessment_focus' => 'LOTS-based assessment focusing on fundamental computer science concepts',
            'generated_at' => now(),
        ]);

        // Create ToS Items
        $tosItem1 = TosItem::create([
            'tos_id' => $tos->id,
            'subtopic_id' => $subtopic1->id,
            'cognitive_level' => 'remember',
            'bloom_category' => 'knowledge',
            'num_items' => 4,
            'weight_percentage' => 20.00,
            'sample_indicators' => ['Identify input devices', 'Name output devices'],
        ]);

        $tosItem2 = TosItem::create([
            'tos_id' => $tos->id,
            'subtopic_id' => $subtopic2->id,
            'cognitive_level' => 'understand',
            'bloom_category' => 'comprehension',
            'num_items' => 4,
            'weight_percentage' => 20.00,
            'sample_indicators' => ['Explain CPU functions', 'Describe processing cycles'],
        ]);

        $tosItem3 = TosItem::create([
            'tos_id' => $tos->id,
            'subtopic_id' => $subtopic3->id,
            'cognitive_level' => 'remember',
            'bloom_category' => 'knowledge',
            'num_items' => 4,
            'weight_percentage' => 20.00,
            'sample_indicators' => ['List system software types', 'Identify OS functions'],
        ]);

        $tosItem4 = TosItem::create([
            'tos_id' => $tos->id,
            'subtopic_id' => $subtopic4->id,
            'cognitive_level' => 'understand',
            'bloom_category' => 'comprehension',
            'num_items' => 4,
            'weight_percentage' => 20.00,
            'sample_indicators' => ['Differentiate application types', 'Explain software purposes'],
        ]);

        $tosItem5 = TosItem::create([
            'tos_id' => $tos->id,
            'subtopic_id' => $subtopic5->id,
            'cognitive_level' => 'remember',
            'bloom_category' => 'knowledge',
            'num_items' => 4,
            'weight_percentage' => 20.00,
            'sample_indicators' => ['Define data types', 'Identify variable declarations'],
        ]);

        // Create sample questions in Item Bank
        $this->createSampleQuestions($tosItem1, $subtopic1);
        $this->createSampleQuestions($tosItem2, $subtopic2);
        $this->createSampleQuestions($tosItem3, $subtopic3);
        $this->createSampleQuestions($tosItem4, $subtopic4);
        $this->createSampleQuestions($tosItem5, $subtopic5);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Student Login: student@sumakquiz.com / password');
        $this->command->info('Admin Login: admin@sumakquiz.com / password');
    }

    private function createSampleQuestions(TosItem $tosItem, Subtopic $subtopic): void
    {
        $questions = [
            [
                'question' => 'Which of the following is an input device?',
                'options' => [
                    ['option_letter' => 'A', 'option_text' => 'Monitor', 'is_correct' => false],
                    ['option_letter' => 'B', 'option_text' => 'Keyboard', 'is_correct' => true],
                    ['option_letter' => 'C', 'option_text' => 'Printer', 'is_correct' => false],
                    ['option_letter' => 'D', 'option_text' => 'Speaker', 'is_correct' => false],
                ],
                'correct_answer' => 'B',
                'explanation' => 'A keyboard is an input device used to enter data into a computer.',
                'difficulty_b' => -0.5,
            ],
            [
                'question' => 'What is the primary function of the CPU?',
                'options' => [
                    ['option_letter' => 'A', 'option_text' => 'Store data permanently', 'is_correct' => false],
                    ['option_letter' => 'B', 'option_text' => 'Display information', 'is_correct' => false],
                    ['option_letter' => 'C', 'option_text' => 'Process instructions', 'is_correct' => true],
                    ['option_letter' => 'D', 'option_text' => 'Connect to internet', 'is_correct' => false],
                ],
                'correct_answer' => 'C',
                'explanation' => 'The CPU (Central Processing Unit) is responsible for processing instructions and performing calculations.',
                'difficulty_b' => 0.0,
            ],
            [
                'question' => 'Which is an example of system software?',
                'options' => [
                    ['option_letter' => 'A', 'option_text' => 'Microsoft Word', 'is_correct' => false],
                    ['option_letter' => 'B', 'option_text' => 'Operating System', 'is_correct' => true],
                    ['option_letter' => 'C', 'option_text' => 'Web Browser', 'is_correct' => false],
                    ['option_letter' => 'D', 'option_text' => 'Video Game', 'is_correct' => false],
                ],
                'correct_answer' => 'B',
                'explanation' => 'An operating system is system software that manages computer hardware and software resources.',
                'difficulty_b' => -0.3,
            ],
            [
                'question' => 'What type of data does a boolean variable store?',
                'options' => [
                    ['option_letter' => 'A', 'option_text' => 'Numbers only', 'is_correct' => false],
                    ['option_letter' => 'B', 'option_text' => 'Text only', 'is_correct' => false],
                    ['option_letter' => 'C', 'option_text' => 'True or False values', 'is_correct' => true],
                    ['option_letter' => 'D', 'option_text' => 'Decimal numbers', 'is_correct' => false],
                ],
                'correct_answer' => 'C',
                'explanation' => 'Boolean variables store logical values: true or false.',
                'difficulty_b' => 0.2,
            ],
        ];

        foreach ($questions as $questionData) {
            ItemBank::create([
                'tos_item_id' => $tosItem->id,
                'subtopic_id' => $subtopic->id,
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answer' => $questionData['correct_answer'],
                'explanation' => $questionData['explanation'],
                'cognitive_level' => $tosItem->cognitive_level,
                'difficulty_b' => $questionData['difficulty_b'],
                'time_estimate_seconds' => 60,
                'created_at' => now(),
            ]);
        }
    }
}
