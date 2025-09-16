<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaterOrderResource\Pages;
use App\Filament\Resources\WaterOrderResource\RelationManagers;
use App\Models\WaterOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WaterOrderResource extends Resource
{
    protected static ?string $model = WaterOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Water Orders';
    protected static ?string $modelLabel = 'Water Order';
    protected static ?string $pluralModelLabel = 'Water Orders';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Forms\Components\Select::make('tank_id')
                    ->relationship('tank', 'name')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'email')
                    ->required(),
                Forms\Components\TextInput::make('order_number')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('volume_liters')
                    ->numeric()
                    ->required()
                    ->suffix('L'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('delivery_date'),
                Forms\Components\Select::make('delivery_time_slot')
                    ->options([
                        'morning' => 'Morning (8AM - 12PM)',
                        'afternoon' => 'Afternoon (12PM - 5PM)',
                        'evening' => 'Evening (5PM - 8PM)',
                    ]),
                Forms\Components\Textarea::make('delivery_address'),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tank.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Ordered By'),
                Tables\Columns\TextColumn::make('volume_liters')
                    ->suffix(' L')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'confirmed',
                        'primary' => 'in_transit',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListWaterOrders::route('/'),
            'create' => Pages\CreateWaterOrder::route('/create'),
            'edit' => Pages\EditWaterOrder::route('/{record}/edit'),
        ];
    }
}
