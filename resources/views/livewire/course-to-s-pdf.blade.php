<!DOCTYPE html>
<html>
<head>
    <title>Table of Specifications - {{ $course->course_title }} - {{ ucfirst($term ?? 'General') }} Exam</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Table of Specifications for {{ $course->course_title }} - {{ ucfirst($term ?? 'General') }} Exam</h1>
    <table>
        <thead>
            <tr>
                <th>Topic/Objective</th>
                <th>Learning Outcomes</th>
                <th>Assessment Type</th>
                <th>Weightage</th>
                <th>Difficulty Level</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tosItems as $tosItem)
                <tr>
                    <td>{{ $tosItem->topic ? $tosItem->topic->name : 'N/A' }}</td>
                    <td>{{ $tosItem->learningOutcome ? $tosItem->learningOutcome->description : 'N/A' }}</td>
                    <td>{{ $tosItem->bloom_category ?: 'N/A' }}</td>
                    <td>{{ $tosItem->weight_percentage }}%</td>
                    <td>{{ $tosItem->cognitive_level ?: 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>