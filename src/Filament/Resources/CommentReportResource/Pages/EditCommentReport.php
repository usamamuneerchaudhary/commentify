<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource;

class EditCommentReport extends EditRecord
{
    protected static string $resource = CommentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

