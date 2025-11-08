<?php

namespace App\Filament\Resources\SensorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    protected static ?string $title = 'Sensor Readings';

    protected static ?string $recordTitleAttribute = 'created_at';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('water_level_percentage')
                    ->label('Water Level %')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('volume_liters')
                    ->label('Volume (L)')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('distance_mm')
                    ->label('Distance (mm)')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('temperature')
                    ->label('Temperature (°C)')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                Tables\Columns\TextColumn::make('tank.name')
                    ->label('Tank')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('water_level_percentage')
                    ->label('Level %')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('%')
                    ->color(fn ($state) => match(true) {
                        $state < 20 => 'danger',
                        $state < 50 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('volume_liters')
                    ->label('Volume')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix(' L'),
                Tables\Columns\TextColumn::make('distance_mm')
                    ->label('Distance')
                    ->numeric()
                    ->sortable()
                    ->suffix(' mm')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temp')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('°C')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('battery_voltage')
                    ->label('Battery')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('V')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signal_rssi')
                    ->label('Signal')
                    ->numeric()
                    ->suffix(' dBm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // Read-only - no create action
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
