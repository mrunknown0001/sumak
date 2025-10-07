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
                        ->options(Course::all()->pluck('course_title', 'id'))
                        ->required(),
                    Forms\Components\FileUpload::make('obtl_file')
                        ->label('Upload OBTL Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required(),
                ])
                // upload and extract information
                ->modalHeading('Upload OBTL Document')
                ->modalButton('Upload')
                ->action(function (array $data) {
                    // handle the uploaded file
                    $obtlFile = $data['obtl_file'];
                    // get full path
                    $obtlFileFullPath = Storage::disk('public')->path($obtlFile);

                    DB::beginTransaction();
                    try {

                        $document = ObtlDocument::create([
                            'course_id' => $data['course_id'],
                            'user_id' => auth()->id(),
                            'title' => 'OBTL Document Title',
                            'file_path' => $obtlFileFullPath,
                            'file_type' => 'pdf',
                            'uploaded_at' => now(),
                        ]);

                        // Dispatch job to extract title
                        ExtractObtlDocumentJob::dispatch($document->id);

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
