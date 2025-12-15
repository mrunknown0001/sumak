<div class="mx-auto max-w-5xl space-y-8 px-4 py-8 text-slate-900 dark:text-slate-100">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Table of Specification for {{ $course->course_title }}</h1>
                {{-- <div>
                    <button wire:click="exportPdf" class="btn btn-primary me-2" aria-label="Export Table of Specifications as PDF">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button wire:click="exportCsv" class="btn btn-success me-2" aria-label="Export Table of Specifications as CSV">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <a href="{{ route('student.course.show', $course->id) }}" class="btn btn-secondary" aria-label="Back to Course Detail">
                        <i class="fas fa-arrow-left"></i> Back to Course Detail
                    </a>
                </div> --}}
            </div>

            @if($course->tosItems && $course->tosItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Topic/Objective</th>
                                <th>Learning Outcomes</th>
                                <th>Assessment Type</th>
                                <th>Weightage</th>
                                <th>Difficulty Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($course->tosItems as $tosItem)
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
                </div>
            @else
                <div class="alert alert-info">
                    <h4>No Table of Specifications Available</h4>
                    <p>The Table of Specifications for this course has not been generated yet or is unavailable.</p>
                </div>
            @endif
        </div>
    </div>
</div>
