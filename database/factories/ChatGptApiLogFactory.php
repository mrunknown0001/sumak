<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatGptApiLogResource\Pages;
use App\Models\ChatGptApiLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatGptApiLogResource extends Resource
{
    protected static ?string $model = ChatGptApiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'API Usage';

    protected static ?string $navigationGroup = 'System Monitoring';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('request_type')
                            ->disabled(),
                        Forms\Components\TextInput::make('model')
                            ->disabled(),
                        Forms\Components\Toggle::make('success')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Token Usage')
                    ->schema([
                        Forms\Components\TextInput::make('total_tokens')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('prompt_tokens')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('completion_tokens')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('estimated_cost')
                            ->prefix('$')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Performance')
                    ->schema([
                        Forms\Components\TextInput::make('response_time_ms')
                            ->suffix('ms')
                            ->disabled(),
                        Forms\Components\Textarea::make('error_message')
                            ->disabled()
                            ->visible(fn ($record) => !$record?->success),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('request_type')
                    ->label('Request Type')
                    ->colors([
                        'primary' => 'content_analysis',
                        'success' => 'tos_generation',
                        'warning' => 'quiz_generation',
                        'info' => 'feedback_generation',
                        'secondary' => 'question_reword',
                    ])
                    ->formatStateUsing(fn (string $state): string => 
                        str_replace('_', ' ', ucwords($state, '_'))
                    ),

                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('success')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estimated_cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD')
                            ->label('Total Cost'),
                    ]),

                Tables\Columns\TextColumn::make('response_time_ms')
                    ->label('Response Time')
                    ->formatStateUsing(fn ($state) => $state < 1000 
                        ? round($state) . ' ms' 
                        : round($state / 1000, 2) . ' s'
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('request_type')
                    ->options([
                        'content_analysis' => 'Content Analysis',
                        'tos_generation' => 'ToS Generation',
                        'quiz_generation' => 'Quiz Generation',
                        'question_reword' => 'Question Reword',
                        'feedback_generation' => 'Feedback Generation',
                        'obtl_parsing' => 'OBTL Parsing',
                    ]),

                Tables\Filters\SelectFilter::make('model')
                    ->options([
                        'gpt-4o-mini' => 'GPT-4o Mini',
                        'gpt-4o' => 'GPT-4o',
                        'gpt-4-turbo' => 'GPT-4 Turbo',
                    ]),

                Tables\Filters\TernaryFilter::make('success')
                    ->label('Status')
                    ->placeholder('All requests')
                    ->trueLabel('Successful only')
                    ->falseLabel('Failed only'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatGptApiLogs::route('/'),
            // 'view' => Pages\ViewChatGptApiLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('created_at', '>=', now()->subDay())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getNavigationBadge();
        
        if ($count > 100) {
            return 'danger';
        } elseif ($count > 50) {
            return 'warning';
        }
        
        return 'success';
    }
}