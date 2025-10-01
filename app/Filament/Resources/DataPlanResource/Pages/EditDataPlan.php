<?php

namespace App\Filament\Resources\DataPlanResource\Pages;

use App\Filament\Resources\DataPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataPlan extends EditRecord
{
    protected static string $resource = DataPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
