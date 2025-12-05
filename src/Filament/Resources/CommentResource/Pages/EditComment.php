<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}

