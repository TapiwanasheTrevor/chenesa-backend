<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SensorResource\Pages;
use App\Filament\Resources\SensorResource\RelationManagers;
use App\Models\Organization;
use App\Models\Sensor;
use App\Models\Tank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SensorResource extends Resource
{
    protected static ?string $model = Sensor::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Sensors';
    protected static ?string $modelLabel = 'Sensor';
    protected static ?string $pluralModelLabel = 'Sensors';
    protected static ?string $navigationGroup = 'Device Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device Information')
                    ->schema([
                        Forms\Components\TextInput::make('device_id')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->placeholder('Unique device identifier'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('imei')
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Device IMEI'),

                                Forms\Components\TextInput::make('sim_number')
                                    ->maxLength(20)
                                    ->placeholder('SIM card number'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('model')
                                    ->default('DF555')
                                    ->maxLength(50)
                                    ->placeholder('Sensor model'),

                                Forms\Components\TextInput::make('firmware_version')
                                    ->maxLength(20)
                                    ->placeholder('Firmware version'),
                            ]),
                    ]),

                Forms\Components\Section::make('Assignment & Status')
                    ->schema([
                        Forms\Components\Select::make('tank_id')
                            ->label('Assigned Tank')
                            ->relationship('tank', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('organization_id')
                                    ->label('Organization')
                                    ->relationship('organization', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('capacity')
                                    ->numeric()
                                    ->required()
                                    ->suffix('L'),
                            ])
                            ->helperText('Assign this sensor to a specific tank'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'maintenance' => 'Maintenance',
                                'unassigned' => 'Unassigned',
                            ])
                            ->required()
                            ->default('unassigned'),

                        Forms\Components\DatePicker::make('installation_date')
                            ->helperText('Date when sensor was installed'),
                    ]),

                Forms\Components\Section::make('Sensor Status (Read-only)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('battery_level')
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled()
                                    ->helperText('Last reported battery level'),

                                Forms\Components\TextInput::make('signal_strength')
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled()
                                    ->helperText('Last reported signal strength'),

                                Forms\Components\DateTimePicker::make('last_seen')
                                    ->disabled()
                                    ->helperText('Last communication time'),
                            ]),
                    ])
                    ->collapsible(),
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

                Tables\Actions\Action::make('assign_tank')
                    ->label('Assign Tank')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('tank_id')
                            ->label('Select Tank')
                            ->relationship('tank', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (Sensor $record, array $data) {
                        $record->update(['tank_id' => $data['tank_id']]);

                        Notification::make()
                            ->title('Sensor assigned successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Sensor $record) => !$record->tank_id),

                Tables\Actions\Action::make('unassign_tank')
                    ->label('Unassign')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Sensor $record) {
                        $record->update(['tank_id' => null]);

                        Notification::make()
                            ->title('Sensor unassigned successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Sensor $record) => (bool) $record->tank_id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Bulk Assign to Tank')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('tank_id')
                                ->label('Select Tank')
                                ->relationship('tank', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['tank_id' => $data['tank_id']]);
                            }

                            Notification::make()
                                ->title('Sensors assigned successfully')
                                ->body(count($records) . ' sensors assigned to tank')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulk_unassign')
                        ->label('Bulk Unassign')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['tank_id' => null]);
                            }

                            Notification::make()
                                ->title('Sensors unassigned successfully')
                                ->body(count($records) . ' sensors unassigned')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
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
