<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Replies';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Forms\Components\Textarea::make('body')
                    ->required()
                    ->maxLength(5000)
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText('Supports Markdown formatting'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->withCount(['likes', 'reports']);
            })
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('body')
                    ->label('Reply')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(function (Comment $record) {
                        return strip_tags($record->presenter()->markdownBody());
                    }),
                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->default(0),
                Tables\Columns\TextColumn::make('reports_count')
                    ->label('Reports')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => ($state ?? 0) > 0 ? 'danger' : 'gray')
                    ->default(0),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'asc');
    }
}

