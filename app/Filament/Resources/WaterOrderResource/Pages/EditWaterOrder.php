<?php

namespace App\Filament\Resources\WaterOrderResource\Pages;

use App\Filament\Resources\WaterOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWaterOrder extends EditRecord
{
    protected static string $resource = WaterOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
