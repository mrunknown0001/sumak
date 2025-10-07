<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LearningOutcomeResource\Pages;
use App\Filament\Resources\LearningOutcomeResource\RelationManagers;
use App\Models\LearningOutcome;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LearningOutcomeResource extends Resource
{
    protected static ?string $model = LearningOutcome::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('outcome_code')->label('Outcome Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->sortable()->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('bloom_level')->label('Bloom Level')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
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
            'index' => Pages\ListLearningOutcomes::route('/'),
            'create' => Pages\CreateLearningOutcome::route('/create'),
            'edit' => Pages\EditLearningOutcome::route('/{record}/edit'),
        ];
    }
}
