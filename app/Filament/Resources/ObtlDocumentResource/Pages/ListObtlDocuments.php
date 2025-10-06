<?php

namespace App\Filament\Resources\ObtlDocumentResource\Pages;

use App\Filament\Resources\ObtlDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessDocumentJob;
use App\Models\Course;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;


class ListObtlDocuments extends ListRecords
{
    protected static string $resource = ObtlDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('uploadobtldocument')
                ->label('Upload OBTL Document and Learning Materials')
                ->form([
                    Forms\Components\Select::make('course_id')
                        ->label('Select Course')
                        ->searchable()
                        ->options(Course::all()->pluck('course_title', 'id'))
                        ->required(),
                    Forms\Components\FileUpload::make('obtl_file')
                        ->label('Upload OBTL Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required(),
                    Forms\Components\FileUpload::make('learning_materials')
                        ->label('Upload Learning Materials')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required(),
                ])
                // upload and extract information
                ->modalHeading('Upload OBTL Document')
                ->modalButton('Upload')
                ->action(function (array $data) {
                    // handle the uploaded file
                    $obtlFile = $data['obtl_file'];
                    $learningMaterials = $data['learning_materials'];
                    // get full path
                    $obtlFileFullPath = Storage::disk('public')->path($obtlFile);
                    $learningMaterialsFullPaths = Storage::disk('public')->path($learningMaterials);
                    DB::beginTransaction();
                    try {
                        $document = Document::create([
                            'course_id' => $data['course_id'],
                            'user_id' => auth()->id(),
                            'title' => 'OBTL Document',
                            'file_path' => $obtlFileFullPath,
                            'file_type' => 'pdf',
                            'uploaded_at' => now(),
                        ]);

                        $learningMaterialsDocument = Document::create([
                            'course_id' => $data['course_id'],
                            'user_id' => auth()->id(),
                            'title' => 'Learning Materials',
                            'file_path' => $learningMaterialsFullPaths,
                            'file_type' => 'pdf',
                            'uploaded_at' => now(),
                        ]);

                        // ProcessDocumentJob::dispatch($document->id);
                        // ProcessDocumentJob::dispatch($learningMaterialsDocument->id);
                        Notification::make()
                            ->title('Documents uploaded successfully. Processing in background.')
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
                    // save documents

                })
        ];
    }
}
