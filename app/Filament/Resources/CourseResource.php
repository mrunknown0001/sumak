<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('course_title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('course_code')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(65535),
                    ]),
                Forms\Components\Section::make('Workflow Status')
                    ->description('OBTL extraction must complete before course materials can be uploaded.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('workflow_stage_badge')
                            ->label('Workflow Stage')
                            ->content(fn (?Course $record) => $record
                                ? Str::headline($record->workflow_stage)
                                : Str::headline(Course::WORKFLOW_STAGE_DRAFT)),
                        Forms\Components\Placeholder::make('obtl_uploaded_at')
                            ->label('OBTL Uploaded At')
                            ->content(fn (?Course $record) => $record?->obtl_uploaded_at?->toDayDateTimeString() ?? 'Not yet uploaded'),
                        Forms\Components\Placeholder::make('materials_uploaded_at')
                            ->label('Materials Uploaded At')
                            ->content(fn (?Course $record) => $record?->materials_uploaded_at?->toDayDateTimeString() ?? 'Not yet uploaded'),
                    ])
                    ->visible(fn (?Course $record) => filled($record)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course_title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course_code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('workflow_stage')
                    ->label('Workflow')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Course::WORKFLOW_STAGE_DRAFT => 'warning',
                        Course::WORKFLOW_STAGE_OBTL_UPLOADED => 'info',
                        Course::WORKFLOW_STAGE_MATERIALS_UPLOADED => 'success',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('obtl_uploaded_at')
                    ->label('OBTL Uploaded')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('materials_uploaded_at')
                    ->label('Materials Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
