<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableOfSpecificationResource\Pages;
use App\Models\TableOfSpecification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TableOfSpecificationResource extends Resource
{
    protected static ?string $model = TableOfSpecification::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 7;

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
                Tables\Columns\TextColumn::make('document.title')
                    ->label('Document')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state),
                Tables\Columns\TextColumn::make('document.course.course_title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Generated At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Items')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tosItems_count')
                    ->label('ToS Rows')
                    ->counts('tosItems')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lots_percentage')
                    ->label('Focus')
                    // ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0) . '% LOTS' : '—')
                    ->formatStateUsing(function (TableOfSpecification $tos) {
                        $distribution = $tos->cognitive_level_distribution;
                        $lots = $distribution['remember'] + $distribution['understand'] + $distribution['apply'];
                        $hots = $distribution['analyze'] + $distribution['evaluate'] + $distribution['create'];
                        return $lots . '% LOTS | '. $hots . '% HOTS';
                    })
                    ->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
            'index' => Pages\ListTableOfSpecifications::route('/'),
            'view' => Pages\ViewTableOfSpecification::route('/{record}'),
        ];
    }
}
