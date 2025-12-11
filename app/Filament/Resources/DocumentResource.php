<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = "Learning Materials";

    protected static ?string $label = "Learning Materials";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('file_type')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('file_size')
                            ->label('File Size (bytes)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('uploaded_at')
                             ->required()
                             ->disabled(),
                        Forms\Components\Select::make('num_quiz_items')
                             ->label('Number of Quiz Items')
                             ->options([
                                 'automatic' => 'Automatic',
                                 '10' => '10',
                                 '15' => '15',
                                 '20' => '20',
                                 '30' => '30',
                             ])
                             ->default('automatic')
                             ->nullable(),
                     ]),
                Forms\Components\Section::make('Processing Status')
                    ->description('Status is updated by the processing pipeline once analysis completes.')
                    ->visible(fn (?Document $record) => filled($record))
                    ->schema([
                        Forms\Components\Placeholder::make('processing_status')
                            ->label('Status')
                            ->content(fn (?Document $record) => $record
                                ? Str::headline($record->processing_status)
                                : Str::headline(Document::PROCESSING_PENDING)),
                        Forms\Components\Placeholder::make('processed_at')
                            ->label('Processed At')
                            ->content(fn (?Document $record) => $record?->processed_at?->toDayDateTimeString() ?? 'Pending'),
                        Forms\Components\Placeholder::make('processing_error')
                            ->label('Last Error')
                            ->content(fn (?Document $record) => $record?->processing_error ?? 'None'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.course_title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Document::PROCESSING_COMPLETED => 'success',
                        Document::PROCESSING_WAITING_SELECTION => 'info',
                        Document::PROCESSING_FAILED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processing_error')
                    ->label('Last Error')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'course_title')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('processing_status')
                    ->options([
                        'completed' => 'Completed',
                        'processing' => 'Processing',
                        'waiting_selection' => 'Waiting Selection',
                        'pending' => 'Pending',
                        'failed' => 'Failed'
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('select_quiz_items')
                    ->label('Select Quiz Items')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Document $record): bool => $record->processing_status === Document::PROCESSING_WAITING_SELECTION)
                    ->modalHeading('Select Quiz Items to Generate')
                    ->modalDescription('Choose which table of specification items you want to generate quiz questions for.')
                    ->form([
                        Forms\Components\CheckboxList::make('selected_items')
                            ->label('Select Table of Specification Items')
                            ->options(function (Document $record) {
                                $tos = $record->topic->tableOfSpecification;
                                if (!$tos || $tos->tosItems->isEmpty()) {
                                    return [];
                                }

                                return $tos->tosItems->mapWithKeys(function ($tosItem) {
                                    $topicName = $tosItem->topic->name;
                                    $cognitiveLevel = ucfirst($tosItem->cognitive_level);
                                    $numItems = $tosItem->num_items;

                                    return [
                                        $tosItem->id => "{$topicName} - {$cognitiveLevel} ({$numItems} items)"
                                    ];
                                });
                            })
                            ->required()
                            ->minItems(1)
                            ->columns(1)
                    ])
                    ->action(function (Document $record, array $data): void {
                        $selectedItems = $data['selected_items'] ?? [];

                        if (empty($selectedItems)) {
                            throw new \Exception('Please select at least one item.');
                        }

                        // Call the controller method
                        $controller = app(\App\Http\Controllers\DocumentController::class);
                        $response = $controller->confirmQuizSelection(
                            request()->merge(['selected_items' => $selectedItems]),
                            $record
                        );

                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Quiz generation started')
                            ->body("Generating questions for " . count($selectedItems) . " selected items.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
