<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObtlDocumentResource\Pages;
use App\Filament\Resources\ObtlDocumentResource\RelationManagers;
use App\Models\ObtlDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ObtlDocumentResource extends Resource
{
    protected static ?string $model = ObtlDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ]),
                Forms\Components\Section::make('Processing Status')
                    ->description('OBTL processing must complete successfully before learning materials can be uploaded.')
                    ->schema([
                        Forms\Components\Placeholder::make('processing_status')
                            ->label('Status')
                            ->content(fn (?ObtlDocument $record) => $record
                                ? Str::headline($record->processing_status)
                                : Str::headline(ObtlDocument::PROCESSING_PENDING)),
                        Forms\Components\Placeholder::make('processed_at')
                            ->label('Processed At')
                            ->content(fn (?ObtlDocument $record) => $record?->processed_at?->toDayDateTimeString() ?? 'Pending'),
                        Forms\Components\Placeholder::make('error_message')
                            ->label('Last Error')
                            ->content(fn (?ObtlDocument $record) => $record?->error_message ?? 'None'),
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
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ObtlDocument::PROCESSING_COMPLETED => 'success',
                        ObtlDocument::PROCESSING_FAILED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Last Error')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListObtlDocuments::route('/'),
            'create' => Pages\CreateObtlDocument::route('/create'),
            'edit' => Pages\EditObtlDocument::route('/{record}/edit'),
        ];
    }
}
