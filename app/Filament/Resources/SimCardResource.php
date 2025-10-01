<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SimCardResource\Pages;
use App\Filament\Resources\SimCardResource\RelationManagers;
use App\Models\SimCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SimCardResource extends Resource
{
    protected static ?string $model = SimCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Connectivity';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SIM Card Details')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('iccid')
                            ->label('ICCID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Integrated Circuit Card ID (usually 19-20 digits)'),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('provider')
                            ->required()
                            ->options([
                                'MTN' => 'MTN',
                                'Vodacom' => 'Vodacom',
                                'Cell C' => 'Cell C',
                                'Telkom' => 'Telkom',
                                'Rain' => 'Rain',
                                'Other' => 'Other',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('network_type')
                            ->required()
                            ->options([
                                '2G' => '2G',
                                '3G' => '3G',
                                '4G' => '4G',
                                '5G' => '5G',
                            ])
                            ->default('4G'),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                                'expired' => 'Expired',
                            ])
                            ->default('inactive'),
                    ])->columns(2),

                Forms\Components\Section::make('Balance & Dates')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->numeric()
                            ->default(0)
                            ->prefix('R')
                            ->step(0.01)
                            ->helperText('Current airtime balance'),
                        Forms\Components\TextInput::make('data_balance_mb')
                            ->label('Data Balance (MB)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('MB'),
                        Forms\Components\DatePicker::make('activation_date'),
                        Forms\Components\DatePicker::make('expiry_date'),
                        Forms\Components\DatePicker::make('last_recharge_date'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('iccid')
                    ->label('ICCID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->copyable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('network_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '5G' => 'success',
                        '4G' => 'info',
                        '3G' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'warning',
                        'expired' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('ZAR')
                    ->sortable()
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('data_balance_mb')
                    ->label('Data (MB)')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('N/A')
                    ->color(fn ($state) => $state !== null && $state < 100 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null),
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sensors_count')
                    ->counts('sensors')
                    ->label('Sensors')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'MTN' => 'MTN',
                        'Vodacom' => 'Vodacom',
                        'Cell C' => 'Cell C',
                        'Telkom' => 'Telkom',
                        'Rain' => 'Rain',
                    ])
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recharge')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn (SimCard $record): string => route('filament.admin.resources.sim-cards.recharge', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSimCards::route('/'),
            'create' => Pages\CreateSimCard::route('/create'),
            'edit' => Pages\EditSimCard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
