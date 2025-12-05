<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Usamamuneerchaudhary\Commentify\Models\CommentReport;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Reports';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->nullable()
                    ->disabled(),
                Forms\Components\TextInput::make('ip')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'dismissed' => 'Dismissed',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('reviewed_by')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->nullable()
                    ->visible(fn ($get) => $get('status') !== 'pending'),
                Forms\Components\DateTimePicker::make('reviewed_at')
                    ->visible(fn ($get) => $get('status') !== 'pending'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reporter')
                    ->sortable()
                    ->searchable()
                    ->default('Guest'),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'success',
                        'dismissed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('review')
                    ->label('Mark as Reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CommentReport $record) => $record->status === 'pending')
                    ->action(function (CommentReport $record) {
                        $record->update([
                            'status' => 'reviewed',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
                \Filament\Actions\Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CommentReport $record) => $record->status === 'pending')
                    ->action(function (CommentReport $record) {
                        $record->update([
                            'status' => 'dismissed',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('mark_reviewed')
                        ->label('Mark as Reviewed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function (CommentReport $record) {
                                $record->update([
                                    'status' => 'reviewed',
                                    'reviewed_by' => auth()->id(),
                                    'reviewed_at' => now(),
                                ]);
                            });
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

