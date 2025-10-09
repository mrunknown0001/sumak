<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Course;
use App\Models\LearningOutcome;
use App\Models\LearningOutcomeDocument;
use App\Models\Document;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Support\Facades\Log;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('document')
                ->label('Upload Document')
                ->form([
                    Forms\Components\Select::make('course_id')
                        ->label('Select Course')
                        ->searchable()
                        ->options(Course::all()->pluck('course_title', 'id'))
                        ->required(),
                    Forms\Components\FileUpload::make('document_file')
                        ->label('Upload  Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required(),
                ])
                // upload and extract information
                ->modalHeading('Upload Document')
                ->modalButton('Upload')
                ->action(function (array $data) {
                    // handle the uploaded file
                    $documentFile = $data['document_file'];
                    // get full path
                    $documentFileFullPath = Storage::disk('public')->path($documentFile);

                    DB::beginTransaction();
                    try {

                        $document = Document::create([
                            'course_id' => $data['course_id'],
                            'user_id' => auth()->id(),
                            'title' => 'OBTL Document Title',
                            'file_path' => $documentFileFullPath,
                            'file_type' => 'pdf',
                            'uploaded_at' => now(),
                        ]);

                        // Dispatch job to extract title
                        ProcessDocumentJob::dispatch($document->id);

                        Notification::make()
                            ->title('Document uploaded successfully.')
                            ->success()
                            ->send();
                        
                        DB::commit();
                    } catch (\Throwable $th) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error uploading documents.')
                            ->danger()
                            ->send();
                        Log::error($th);
                    }

                })
        ];
    }
}
