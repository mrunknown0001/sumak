<?php

namespace App\Livewire;

use App\Models\Course;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseToS extends Component
{
    public $course;
    public $term;

    public function mount($courseId, $term = null)
    {
        // Validate courseId
        $courseId = (int) $courseId;
        if ($courseId <= 0) {
            abort(404, 'Invalid course ID');
        }

        // Validate term
        if (!in_array($term, ['midterm', 'final'])) {
            abort(404, 'Invalid term specified');
        }

        // Retrieve course with related ToS data for the specific term
        $course = Course::with([
            'tableOfSpecifications' => function ($query) use ($term) {
                $query->where('term', $term)->with('tosItems.topic', 'tosItems.learningOutcome');
            }
        ])->find($courseId);

        if (!$course) {
            abort(404, 'Course not found');
        }

        $this->course = $course;
        $this->term = $term;
    }

    public function exportPdf()
    {
        $tosItems = $this->course->tableOfSpecifications->first()->tosItems ?? collect();

        $html = view('livewire.course-to-s-pdf', [
            'course' => $this->course,
            'tosItems' => $tosItems,
            'term' => $this->term,
        ])->render();

        $pdf = Pdf::loadHTML($html);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'table_of_specifications_' . $this->course->course_code . '_' . $this->term . '.pdf');
    }

    public function exportCsv()
    {
        $tosItems = $this->course->tableOfSpecifications->first()->tosItems ?? collect();

        $csvData = "Topic/Objective,Learning Outcomes,Assessment Type,Weightage,Difficulty Level\n";

        foreach ($tosItems as $item) {
            $csvData .= '"' . ($item->topic ? $item->topic->name : 'N/A') . '",';
            $csvData .= '"' . ($item->learningOutcome ? $item->learningOutcome->description : 'N/A') . '",';
            $csvData .= '"' . ($item->bloom_category ?: 'N/A') . '",';
            $csvData .= '"' . $item->weight_percentage . '%",';
            $csvData .= '"' . ($item->cognitive_level ?: 'N/A') . '"' . "\n";
        }

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, 'table_of_specifications_' . $this->course->course_code . '_' . $this->term . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.course-to-s')
            ->layout('layouts.app');
    }
}
