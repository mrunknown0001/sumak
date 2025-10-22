<?php

namespace App\Filament\Resources\ObtlDocumentResource\Pages;

use App\Filament\Resources\ObtlDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Course;
use App\Models\ObtlDocument;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Jobs\ExtractObtlDocumentJob;
use Illuminate\Validation\ValidationException as LaravelValidationException;


class ListObtlDocuments extends ListRecords
{
    protected static string $resource = ObtlDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('uploadobtldocument')
                ->label('Upload OBTL Document')
                ->form([
                    Forms\Components\Select::make('course_id')
                        ->label('Select Course')
                        ->searchable()
                        ->options(fn () => Course::query()
                            ->where(function ($query) {
                                $query->whereNull('workflow_stage')
                                    ->orWhere('workflow_stage', Course::WORKFLOW_STAGE_DRAFT);
                            })
                            ->whereDoesntHave('obtlDocument')
                            ->orderBy('course_title')
                            ->pluck('course_title', 'id'))
                        ->hint('Only courses without an uploaded OBTL document are listed.')
                        ->required(),
                    Forms\Components\FileUpload::make('obtl_file')
                        ->label('Upload OBTL Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480) // 20MB
                        ->disk('public')
                        ->visibility('public')
                        ->directory('obtl-documents')
                        ->required(),
                ])
                ->modalHeading('Upload OBTL Document')
                ->modalButton('Upload')
                ->action(function (array $data) {
                    DB::beginTransaction();

                    try {
                        $course = Course::lockForUpdate()->find($data['course_id']);

                        if (! $course) {
                            throw ValidationException::withMessages([
                                'course_id' => 'The selected course could not be found.',
                            ]);
                        }

                        if ($course->workflow_stage !== null && $course->workflow_stage !== Course::WORKFLOW_STAGE_DRAFT) {
                            throw ValidationException::withMessages([
                                'course_id' => 'The selected course already has an OBTL document in progress.',
                            ]);
                        }

                        if ($course->obtlDocument()->exists()) {
                            throw ValidationException::withMessages([
                                'course_id' => 'An OBTL document has already been uploaded for this course.',
                            ]);
                        }

                        // Move file from temporary to permanent storage
                        $temporaryPath = $data['obtl_file'];
                        $permanentPath = 'obtl-documents/' . $course->id . '_' . time() . '.pdf';
                        
                        // Move the file
                        Storage::disk('public')->move($temporaryPath, $permanentPath);
                        
                        // Get the full path and size
                        $obtlFileFullPath = Storage::disk('public')->path($permanentPath);
                        $fileSize = Storage::disk('public')->size($permanentPath);

                        $document = ObtlDocument::create([
                            'course_id' => $course->id,
                            'user_id' => auth()->id(),
                            'title' => 'OBTL Document Title',
                            'file_path' => $obtlFileFullPath, // Or just store $permanentPath if you want relative path
                            'file_type' => 'pdf',
                            'file_size' => $fileSize,
                            'uploaded_at' => now(),
                            'processing_status' => ObtlDocument::PROCESSING_PENDING,
                        ]);

                        $course->update([
                            'workflow_stage' => Course::WORKFLOW_STAGE_OBTL_UPLOADED,
                            'obtl_uploaded_at' => now(),
                        ]);

                        ExtractObtlDocumentJob::dispatch($document->id);

                        Notification::make()
                            ->title('OBTL document uploaded successfully.')
                            ->body('Processing has started. You will be notified when extraction completes.')
                            ->success()
                            ->send();

                        DB::commit();
                    } catch (LaravelValidationException $exception) {
                        DB::rollBack();
                        
                        // Clean up file if it was moved
                        if (isset($permanentPath) && Storage::disk('public')->exists($permanentPath)) {
                            Storage::disk('public')->delete($permanentPath);
                        }
                        
                        $this->halt();

                        Notification::make()
                            ->title('Unable to upload OBTL document.')
                            ->body(collect($exception->errors())->flatten()->join(' '))
                            ->danger()
                            ->send();

                        throw $exception;
                    } catch (\Throwable $th) {
                        DB::rollBack();
                        
                        // Clean up file if it was moved
                        if (isset($permanentPath) && Storage::disk('public')->exists($permanentPath)) {
                            Storage::disk('public')->delete($permanentPath);
                        }

                        Notification::make()
                            ->title('Error uploading OBTL document.')
                            ->body('Please try again or contact support if the issue persists.')
                            ->danger()
                            ->send();

                        Log::error($th);
                    }
                })
        ];
    }
}
