<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Course;
use App\Models\Document;
use App\Models\ObtlDocument;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            // Actions\Action::make('document')
            //     ->label('Upload Document')
            //     ->form([
            //         Forms\Components\Select::make('course_id')
            //             ->label('Select Course')
            //             ->searchable()
            //             ->options(function () {
            //                 $coursesQuery = Course::query()
            //                     ->whereIn('workflow_stage', [
            //                         Course::WORKFLOW_STAGE_OBTL_UPLOADED,
            //                         Course::WORKFLOW_STAGE_MATERIALS_UPLOADED,
            //                     ])
            //                     ->whereHas('obtlDocument', fn ($query) => $query
            //                         ->where('processing_status', ObtlDocument::PROCESSING_COMPLETED))
            //                     ->orderBy('course_title');

            //                 $courses = $coursesQuery->pluck('course_title', 'id');

            //                 Log::debug('ListDocuments available courses for upload', [
            //                     'course_count' => $courses->count(),
            //                     'course_ids' => $courses->keys(),
            //                 ]);

            //                 return $courses;
            //             })
            //             ->hint('Only courses with a completed OBTL document are available for material upload.')
            //             ->required(),
            //         Forms\Components\FileUpload::make('document_file')
            //             ->label('Upload  Document')
            //             ->acceptedFileTypes(['application/pdf'])
            //             ->maxSize(20480) // 20MB
            //             ->required(),
            //     ])
            //     // upload and extract information
            //     ->modalHeading('Upload Document')
            //     ->modalButton('Upload')
            //     ->action(function (array $data) {
            //         $documentFile = $data['document_file'];
            //         $documentFileFullPath = Storage::disk('public')->path($documentFile);
            //         $fileSize = Storage::disk('public')->size($documentFile);

            //         DB::beginTransaction();

            //         try {
            //             $course = Course::lockForUpdate()
            //                 ->with('obtlDocument')
            //                 ->find($data['course_id']);

            //             if (! $course) {
            //                 throw LaravelValidationException::withMessages([
            //                     'course_id' => 'The selected course could not be found.',
            //                 ]);
            //             }

            //             $obtlDocument = $course->obtlDocument;

            //             if (! $obtlDocument || $obtlDocument->processing_status !== ObtlDocument::PROCESSING_COMPLETED) {
            //                 throw LaravelValidationException::withMessages([
            //                     'course_id' => 'The selected course does not have a completed OBTL document.',
            //                 ]);
            //             }

            //             if (! in_array($course->workflow_stage, [
            //                 Course::WORKFLOW_STAGE_OBTL_UPLOADED,
            //                 Course::WORKFLOW_STAGE_MATERIALS_UPLOADED,
            //             ], true)) {
            //                 throw LaravelValidationException::withMessages([
            //                     'course_id' => 'Course materials cannot be uploaded until the OBTL stage is completed.',
            //                 ]);
            //             }

            //             $document = Document::create([
            //                 'course_id' => $course->id,
            //                 'user_id' => auth()->id(),
            //                 'title' => pathinfo($documentFile, PATHINFO_FILENAME) ?: 'Course Material',
            //                 'file_path' => $documentFileFullPath,
            //                 'file_type' => pathinfo($documentFile, PATHINFO_EXTENSION) ?: 'pdf',
            //                 'file_size' => $fileSize,
            //                 'uploaded_at' => now(),
            //                 'processing_status' => Document::PROCESSING_PENDING,
            //             ]);

            //             $course->update([
            //                 'workflow_stage' => Course::WORKFLOW_STAGE_MATERIALS_UPLOADED,
            //                 'materials_uploaded_at' => $course->materials_uploaded_at ?? now(),
            //             ]);

            //             ProcessDocumentJob::dispatch($document->id, [
            //                 'has_obtl' => true,
            //             ]);

            //             Notification::make()
            //                 ->title('Document uploaded successfully.')
            //                 ->body('Processing has started. You will be notified when analysis completes.')
            //                 ->success()
            //                 ->send();

            //             DB::commit();
            //         } catch (LaravelValidationException $exception) {
            //             DB::rollBack();
            //             $this->halt();

            //             Notification::make()
            //                 ->title('Document upload blocked')
            //                 ->body(collect($exception->errors())->flatten()->join(' '))
            //                 ->danger()
            //                 ->send();

            //             throw $exception;
            //         } catch (\Throwable $th) {
            //             DB::rollBack();

            //             Notification::make()
            //                 ->title('Error uploading document.')
            //                 ->body('Please try again or contact support if the issue persists.')
            //                 ->danger()
            //                 ->send();

            //             Log::error($th);
            //         }

            //     })
        ];
    }
}
