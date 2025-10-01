<?php

namespace App\Filament\Resources\DataPlanResource\Pages;

use App\Filament\Resources\DataPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataPlans extends ListRecords
{
    protected static string $resource = DataPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
