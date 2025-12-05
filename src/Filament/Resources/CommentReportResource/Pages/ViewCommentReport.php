<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource;

class ViewCommentReport extends ViewRecord
{
    protected static string $resource = CommentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

