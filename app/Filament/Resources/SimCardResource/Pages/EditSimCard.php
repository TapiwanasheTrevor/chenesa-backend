<?php

namespace App\Filament\Resources\SimCardResource\Pages;

use App\Filament\Resources\SimCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimCard extends EditRecord
{
    protected static string $resource = SimCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
