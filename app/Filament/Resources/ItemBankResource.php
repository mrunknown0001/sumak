<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemBankResource\Pages;
use App\Filament\Resources\ItemBankResource\RelationManagers;
use App\Models\ItemBank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemBankResource extends Resource
{
    protected static ?string $model = ItemBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

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
                Tables\Columns\TextColumn::make('question')
                    ->limit(80)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('correct_answer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('topic.name')
                    ->label('Topic')
                    ->searchable()
                    ->sortable(),    
                Tables\Columns\TextColumn::make('topic.document.title') 
                    ->label('Document')
                    ->searchable()
                    ->sortable()
            ])
            ->recordUrl(null)
            ->filters([
                Tables\Filters\SelectFilter::make('topic.document')
                    ->relationship('topic.document', 'title')
                    ->searchable(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListItemBanks::route('/'),
            'create' => Pages\CreateItemBank::route('/create'),
            'edit' => Pages\EditItemBank::route('/{record}/edit'),
        ];
    }
}
