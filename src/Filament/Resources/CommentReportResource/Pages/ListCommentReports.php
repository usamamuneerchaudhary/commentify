<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource;

class ListCommentReports extends ListRecords
{
    protected static string $resource = CommentReportResource::class;

    /**
     * Header actions for the reports list.
     */
    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
