<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SensorReadingResource\Pages;
use App\Filament\Resources\SensorReadingResource\RelationManagers;
use App\Models\SensorReading;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SensorReadingResource extends Resource
{
    protected static ?string $model = SensorReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Sensor Readings';

    protected static ?string $modelLabel = 'Sensor Reading';

    protected static ?string $pluralModelLabel = 'Sensor Readings';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sensor_id')
                    ->relationship('sensor', 'device_id')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('tank_id')
                    ->relationship('tank', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('distance_mm')
                    ->label('Distance (mm)')
                    ->required()
                    ->numeric()
                    ->suffix('mm'),
                Forms\Components\TextInput::make('water_level_mm')
                    ->label('Water Level (mm)')
                    ->numeric()
                    ->suffix('mm'),
                Forms\Components\TextInput::make('water_level_percentage')
                    ->label('Water Level %')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
                Forms\Components\TextInput::make('volume_liters')
                    ->label('Volume (liters)')
                    ->numeric()
                    ->suffix('L'),
                Forms\Components\TextInput::make('temperature')
                    ->label('Temperature (°C)')
                    ->numeric()
                    ->suffix('°C'),
                Forms\Components\TextInput::make('battery_voltage')
                    ->label('Battery Voltage (V)')
                    ->numeric()
                    ->suffix('V')
                    ->step(0.01),
                Forms\Components\TextInput::make('signal_rssi')
                    ->label('Signal RSSI')
                    ->numeric()
                    ->suffix('dBm'),
                Forms\Components\Textarea::make('raw_data')
                    ->label('Raw Data (JSON)')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sensor.device_id')
                    ->label('Sensor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tank.name')
                    ->label('Tank')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('distance_mm')
                    ->label('Distance')
                    ->numeric()
                    ->sortable()
                    ->suffix(' mm')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('water_level_mm')
                    ->label('Water Level')
                    ->numeric()
                    ->sortable()
                    ->suffix(' mm')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('water_level_percentage')
                    ->label('Level %')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('%')
                    ->alignEnd()
                    ->color(fn ($state) => match(true) {
                        $state < 20 => 'danger',
                        $state < 50 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('volume_liters')
                    ->label('Volume')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix(' L')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('temperature')
                    ->label('Temp')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('°C')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('battery_voltage')
                    ->label('Battery')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->suffix('V')
                    ->alignEnd()
                    ->color(fn ($state) => match(true) {
                        $state < 3.3 => 'danger',
                        $state < 3.5 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('signal_rssi')
                    ->label('Signal')
                    ->numeric()
                    ->sortable()
                    ->suffix(' dBm')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sensor')
                    ->relationship('sensor', 'device_id')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('tank')
                    ->relationship('tank', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSensorReadings::route('/'),
            'create' => Pages\CreateSensorReading::route('/create'),
            'edit' => Pages\EditSensorReading::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count > 100 ? 'success' : 'primary';
    }
}
