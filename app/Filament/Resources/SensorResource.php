<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SensorResource\Pages;
use App\Filament\Resources\SensorResource\RelationManagers;
use App\Models\Sensor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SensorResource extends Resource
{
    protected static ?string $model = Sensor::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Sensors';
    protected static ?string $modelLabel = 'Sensor';
    protected static ?string $pluralModelLabel = 'Sensors';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('device_id')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                Forms\Components\TextInput::make('imei')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sim_number')
                    ->maxLength(20),
                Forms\Components\TextInput::make('model')
                    ->default('DF555')
                    ->maxLength(50),
                Forms\Components\TextInput::make('firmware_version')
                    ->maxLength(20),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Maintenance',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('installation_date'),
                Forms\Components\TextInput::make('battery_level')
                    ->numeric()
                    ->suffix('%')
                    ->disabled(),
                Forms\Components\TextInput::make('signal_strength')
                    ->numeric()
                    ->suffix('%')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tank.name')
                    ->label('Assigned Tank'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('battery_level')
                    ->suffix('%')
                    ->color(fn ($state) => match(true) {
                        $state < 20 => 'danger',
                        $state < 50 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('signal_strength')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('last_seen')
                    ->dateTime()
                    ->color(fn ($record) =>
                        $record->last_seen && $record->last_seen->diffInMinutes(now()) > 30
                            ? 'danger'
                            : 'success'
                    ),
                Tables\Columns\TextColumn::make('model'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
                Tables\Filters\Filter::make('offline')
                    ->query(fn ($query) => $query->where('last_seen', '<', now()->subMinutes(30))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSensors::route('/'),
            'create' => Pages\CreateSensor::route('/create'),
            'edit' => Pages\EditSensor::route('/{record}/edit'),
        ];
    }
}
