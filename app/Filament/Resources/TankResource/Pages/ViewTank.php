<?php

namespace App\Filament\Resources\TankResource\Pages;

use App\Filament\Resources\TankResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTank extends ViewRecord
{
    protected static string $resource = TankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Current Water Level')
                    ->schema([
                        TextEntry::make('current_level')
                            ->label('Water Level Percentage')
                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                            ->badge()
                            ->size('lg')
                            ->color(fn ($state): string => match (true) {
                                $state <= 10 => 'danger',
                                $state <= 20 => 'warning',
                                $state >= 90 => 'success',
                                default => 'primary',
                            }),
                        TextEntry::make('current_volume')
                            ->label('Current Volume')
                            ->formatStateUsing(fn ($state) => number_format($state, 2) . ' L'),
                        TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'danger' => 'critical',
                                'warning' => 'low',
                                'success' => 'normal',
                            ]),
                        TextEntry::make('latestReading.created_at')
                            ->label('Last Reading')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2),

                Section::make('Tank Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('location'),
                        TextEntry::make('organization.name')
                            ->label('Organization'),
                        TextEntry::make('sensor.device_id')
                            ->label('Sensor ID'),
                    ])
                    ->columns(2),

                Section::make('Physical Dimensions')
                    ->schema([
                        TextEntry::make('capacity_liters')
                            ->label('Total Capacity')
                            ->suffix(' L'),
                        TextEntry::make('height_mm')
                            ->label('Height')
                            ->suffix(' mm'),
                        TextEntry::make('diameter_mm')
                            ->label('Diameter')
                            ->suffix(' mm'),
                        TextEntry::make('shape')
                            ->badge(),
                        TextEntry::make('material'),
                        TextEntry::make('installation_height_mm')
                            ->label('Installation Height')
                            ->suffix(' mm'),
                    ])
                    ->columns(3),

                Section::make('Thresholds & Settings')
                    ->schema([
                        TextEntry::make('low_level_threshold')
                            ->label('Low Level Alert')
                            ->suffix('%'),
                        TextEntry::make('critical_level_threshold')
                            ->label('Critical Level Alert')
                            ->suffix('%'),
                        TextEntry::make('auto_refill_threshold')
                            ->label('Auto Refill Threshold')
                            ->suffix('%'),
                        TextEntry::make('refill_enabled')
                            ->label('Auto Refill')
                            ->formatStateUsing(fn ($state) => $state ? 'Enabled' : 'Disabled')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                    ])
                    ->columns(2),

                Section::make('Sensor Information')
                    ->schema([
                        TextEntry::make('sensor.status')
                            ->label('Sensor Status')
                            ->badge()
                            ->colors([
                                'success' => 'active',
                                'danger' => 'inactive',
                                'warning' => 'maintenance',
                            ]),
                        TextEntry::make('sensor.battery_level')
                            ->label('Battery Level')
                            ->suffix('%'),
                        TextEntry::make('sensor.signal_strength')
                            ->label('Signal Strength')
                            ->suffix('%'),
                        TextEntry::make('sensor.last_seen')
                            ->label('Last Seen')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->sensor !== null),
            ]);
    }
}
