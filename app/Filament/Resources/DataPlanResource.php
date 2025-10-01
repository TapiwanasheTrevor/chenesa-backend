<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataPlanResource\Pages;
use App\Filament\Resources\DataPlanResource\RelationManagers;
use App\Models\DataPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DataPlanResource extends Resource
{
    protected static ?string $model = DataPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Connectivity';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., MTN 1GB Monthly'),
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
                        Forms\Components\Select::make('plan_type')
                            ->required()
                            ->options([
                                'data_only' => 'Data Only',
                                'voice_and_data' => 'Voice & Data',
                                'sms_and_data' => 'SMS & Data',
                                'bundle' => 'Bundle',
                            ])
                            ->default('data_only'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Plan Specifications')
                    ->schema([
                        Forms\Components\TextInput::make('data_amount_mb')
                            ->label('Data Amount (MB)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('MB')
                            ->helperText('Enter data amount in MB (e.g., 1024 for 1GB)'),
                        Forms\Components\TextInput::make('validity_days')
                            ->required()
                            ->numeric()
                            ->suffix('days')
                            ->helperText('How many days the plan is valid'),
                        Forms\Components\TextInput::make('cost')
                            ->required()
                            ->numeric()
                            ->prefix('R')
                            ->step(0.01)
                            ->helperText('Cost of the plan'),
                        Forms\Components\TextInput::make('currency')
                            ->default('ZAR')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_amount_mb')
                    ->label('Data')
                    ->formatStateUsing(fn ($state) => $state >= 1024
                        ? number_format($state / 1024, 2) . ' GB'
                        : number_format($state, 2) . ' MB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('validity_days')
                    ->label('Validity')
                    ->suffix(' days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->money('ZAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'data_only' => 'Data Only',
                        'voice_and_data' => 'Voice & Data',
                        'sms_and_data' => 'SMS & Data',
                        'bundle' => 'Bundle',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'MTN' => 'MTN',
                        'Vodacom' => 'Vodacom',
                        'Cell C' => 'Cell C',
                        'Telkom' => 'Telkom',
                        'Rain' => 'Rain',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        'data_only' => 'Data Only',
                        'voice_and_data' => 'Voice & Data',
                        'sms_and_data' => 'SMS & Data',
                        'bundle' => 'Bundle',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListDataPlans::route('/'),
            'create' => Pages\CreateDataPlan::route('/create'),
            'edit' => Pages\EditDataPlan::route('/{record}/edit'),
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
