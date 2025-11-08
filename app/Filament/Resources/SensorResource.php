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

                        Forms\Components\Select::make('sim_card_id')
                            ->label('Assigned SIM Card')
                            ->relationship('simCard', 'iccid')
                            ->searchable()
                            ->preload()
                            ->helperText('Assign a SIM card to this sensor for connectivity'),

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
                                Forms\Components\Placeholder::make('battery_status')
                                    ->label('Battery Level')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists || !$record->battery_level) {
                                            return 'No data';
                                        }
                                        $level = $record->battery_level;
                                        $color = $level < 20 ? 'danger' : ($level < 50 ? 'warning' : 'success');
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex items-center gap-2">
                                                <span class="text-lg font-bold text-' . $color . '-600">' . $level . '%</span>
                                            </div>'
                                        );
                                    }),

                                Forms\Components\Placeholder::make('signal_status')
                                    ->label('Signal Strength')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists || !$record->signal_strength) {
                                            return 'No data';
                                        }
                                        $strength = $record->signal_strength;
                                        $color = $strength < 30 ? 'danger' : ($strength < 60 ? 'warning' : 'success');
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex items-center gap-2">
                                                <span class="text-lg font-bold text-' . $color . '-600">' . $strength . '%</span>
                                            </div>'
                                        );
                                    }),

                                Forms\Components\Placeholder::make('last_seen_status')
                                    ->label('Last Seen')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists || !$record->last_seen) {
                                            return 'Never';
                                        }
                                        $diffMinutes = $record->last_seen->diffInMinutes(now());
                                        $color = $diffMinutes > 30 ? 'danger' : 'success';
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex flex-col gap-1">
                                                <span class="text-sm font-medium text-' . $color . '-600">' .
                                                    $record->last_seen->diffForHumans() .
                                                '</span>
                                                <span class="text-xs text-gray-500">' .
                                                    $record->last_seen->format('M j, Y H:i:s') .
                                                '</span>
                                            </div>'
                                        );
                                    }),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('latest_reading_info')
                                    ->label('Latest Reading')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists) {
                                            return 'No readings yet';
                                        }
                                        try {
                                            $latest = $record->latestReading;
                                            if (!$latest) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<span class="text-sm text-gray-500">No readings yet</span>'
                                                );
                                            }
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium">Distance: ' . $latest->distance_mm . ' mm</span>
                                                    <span class="text-sm">Level: ' . ($latest->water_level_percentage ?? 0) . '%</span>
                                                    <span class="text-xs text-gray-500">' .
                                                        $latest->created_at->diffForHumans() .
                                                    '</span>
                                                </div>'
                                            );
                                        } catch (\Exception $e) {
                                            return 'Error loading readings';
                                        }
                                    }),

                                Forms\Components\Placeholder::make('total_readings')
                                    ->label('Total Readings')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists) {
                                            return '0';
                                        }
                                        try {
                                            $count = $record->readings()->count();
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex flex-col gap-1">
                                                    <span class="text-2xl font-bold text-primary-600">' .
                                                        number_format($count) .
                                                    '</span>
                                                    <span class="text-xs text-gray-500">readings stored</span>
                                                </div>'
                                            );
                                        } catch (\Exception $e) {
                                            return '0';
                                        }
                                    }),

                                Forms\Components\Placeholder::make('reading_rate')
                                    ->label('Expected Next Reading')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists || !$record->last_seen) {
                                            return 'Unknown';
                                        }
                                        try {
                                            $nextReading = $record->last_seen->copy()->addMinutes(10);
                                            $isOverdue = $nextReading->isPast();
                                            $color = $isOverdue ? 'danger' : 'success';
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium text-' . $color . '-600">' .
                                                        ($isOverdue ? 'Overdue' : $nextReading->diffForHumans()) .
                                                    '</span>
                                                    <span class="text-xs text-gray-500">10-minute interval</span>
                                                </div>'
                                            );
                                        } catch (\Exception $e) {
                                            return 'Unknown';
                                        }
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->hidden(fn ($record) => !$record || !$record->exists),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('pulse')
                    ->label('')
                    ->view('filament.tables.columns.sensor-pulse')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Sensor Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->imei ? "IMEI: {$record->imei}" : null),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('battery_level')
                    ->label('Battery')
                    ->suffix('%')
                    ->color(fn ($state) => match(true) {
                        $state < 20 => 'danger',
                        $state < 50 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('signal_strength')
                    ->label('Signal')
                    ->suffix('%')
                    ->color(fn ($state) => match(true) {
                        $state < 30 => 'danger',
                        $state < 60 => 'warning',
                        default => 'success'
                    }),
                Tables\Columns\TextColumn::make('last_seen')
                    ->dateTime()
                    ->color(fn ($record) => !$record->last_seen ? 'danger' : ($record->last_seen->diffInHours(now()) < 10 ? 'success' : ($record->last_seen->diffInHours(now()) < 24 ? 'warning' : 'danger'))),
            ])
            ->poll('5s')
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
