<?php

namespace App\Filament\Resources\TankResource\Pages;

use App\Filament\Resources\TankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTank extends EditRecord
{
    protected static string $resource = TankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
