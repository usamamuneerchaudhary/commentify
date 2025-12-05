<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource;

class ViewComment extends ViewRecord
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

