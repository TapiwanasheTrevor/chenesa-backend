<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TankResource\Pages;
use App\Filament\Resources\TankResource\RelationManagers;
use App\Models\Tank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TankResource extends Resource
{
    protected static ?string $model = Tank::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Water Tanks';
    protected static ?string $modelLabel = 'Tank';
    protected static ?string $pluralModelLabel = 'Water Tanks';
    protected static ?string $navigationGroup = 'Device Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Forms\Components\Select::make('sensor_id')
                    ->relationship('sensor', 'device_id')
                    ->nullable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('location'),
                Forms\Components\TextInput::make('capacity_liters')
                    ->numeric()
                    ->required()
                    ->suffix('L'),
                Forms\Components\TextInput::make('height_mm')
                    ->numeric()
                    ->required()
                    ->suffix('mm'),
                Forms\Components\TextInput::make('diameter_mm')
                    ->numeric()
                    ->suffix('mm'),
                Forms\Components\Select::make('shape')
                    ->options([
                        'cylindrical' => 'Cylindrical',
                        'rectangular' => 'Rectangular',
                        'custom' => 'Custom',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('low_level_threshold')
                    ->numeric()
                    ->default(20)
                    ->suffix('%'),
                Forms\Components\TextInput::make('critical_level_threshold')
                    ->numeric()
                    ->default(10)
                    ->suffix('%'),
                Forms\Components\Toggle::make('refill_enabled')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('current_level')
                    ->label('Water Level')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state <= 10 => 'danger',
                        $state <= 20 => 'warning',
                        $state >= 90 => 'success',
                        default => 'primary',
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->leftJoin('sensor_readings as sr', function ($join) {
                            $join->on('tanks.id', '=', 'sr.tank_id')
                                ->whereRaw('sr.id = (SELECT id FROM sensor_readings WHERE tank_id = tanks.id ORDER BY created_at DESC LIMIT 1)');
                        })
                        ->orderBy('sr.water_level_percentage', $direction);
                    }),
                Tables\Columns\TextColumn::make('capacity_liters')
                    ->suffix(' L')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sensor.device_id')
                    ->label('Sensor'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'low',
                        'success' => 'normal',
                    ]),
                Tables\Columns\IconColumn::make('refill_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name'),
                Tables\Filters\SelectFilter::make('shape'),
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
            RelationManagers\UsersRelationManager::class,
            RelationManagers\ReadingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTanks::route('/'),
            'create' => Pages\CreateTank::route('/create'),
            'view' => Pages\ViewTank::route('/{record}'),
            'edit' => Pages\EditTank::route('/{record}/edit'),
        ];
    }
}
