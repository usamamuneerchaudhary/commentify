<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource\Pages;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|null|\UnitEnum $navigationGroup = 'Commentify';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->nullable()
                    ->label('Parent Comment')
                    ->disabled(),
                Forms\Components\Textarea::make('body')
                    ->required()
                    ->maxLength(5000)
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Supports Markdown formatting'),
                Forms\Components\Select::make('commentable_type')
                    ->label('Commentable Type')
                    ->options(function (Comment $record) {
                        if ($record->commentable_type) {
                            return [$record->commentable_type => class_basename($record->commentable_type)];
                        }
                        return [];
                    })
                    ->disabled(),
                Forms\Components\TextInput::make('commentable_id')
                    ->label('Commentable ID')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->withCount(['likes', 'children', 'reports']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->default('Unknown'),
                Tables\Columns\TextColumn::make('body')
                    ->label('Comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(function (Comment $record) {
                        return strip_tags($record->presenter()->markdownBody());
                    }),
                Tables\Columns\TextColumn::make('commentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commentable_id')
                    ->label('Item ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('isParent')
                    ->label('Is Parent')
                    ->boolean()
                    ->getStateUsing(fn (Comment $record) => $record->isParent())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('parent_id')
                    ->label('Parent ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->default(0),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Replies')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->default(0),
                Tables\Columns\TextColumn::make('reports_count')
                    ->label('Reports')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => ($state ?? 0) > 0 ? 'danger' : 'gray')
                    ->default(0)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User')
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('isParent')
                    ->label('Parent Comments Only')
                    ->queries(
                        true: fn ($query) => $query->whereNull('parent_id'),
                        false: fn ($query) => $query->whereNotNull('parent_id'),
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CommentResource\RelationManagers\RepliesRelationManager::class,
            CommentResource\RelationManagers\ReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'view' => Pages\ViewComment::route('/{record}'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}

