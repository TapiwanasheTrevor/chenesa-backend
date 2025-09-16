<?php

namespace App\Filament\Resources\WaterOrderResource\Pages;

use App\Filament\Resources\WaterOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaterOrders extends ListRecords
{
    protected static string $resource = WaterOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
