<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource\Pages;
use Usamamuneerchaudhary\Commentify\Models\CommentReport;

class CommentReportResource extends Resource
{
    protected static ?string $model = CommentReport::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-flag';

    protected static string|null|\UnitEnum $navigationGroup = 'Commentify';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('comment_id')
                    ->relationship('comment', 'id')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\TextInput::make('ip')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->columnSpanFull(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('comment.id')
                    ->label('Comment ID')
                    ->sortable()
                    ->searchable(),
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
                    }),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->recordActions([
                Action::make('review')
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
                Action::make('dismiss')
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('mark_reviewed')
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
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCommentReports::route('/'),
            'create' => Pages\CreateCommentReport::route('/create'),
            'view' => Pages\ViewCommentReport::route('/{record}'),
            'edit' => Pages\EditCommentReport::route('/{record}/edit'),
        ];
    }
}

