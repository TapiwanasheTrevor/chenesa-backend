<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertResource\Pages;
use App\Filament\Resources\AlertResource\RelationManagers;
use App\Models\Alert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Alerts';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Forms\Components\Select::make('tank_id')
                    ->relationship('tank', 'name'),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('severity')
                    ->required()
                    ->maxLength(255)
                    ->default('info'),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_resolved')
                    ->required(),
                Forms\Components\DateTimePicker::make('resolved_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('organization.name'),
                Tables\Columns\TextColumn::make('tank.name'),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_resolved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAlerts::route('/'),
            'create' => Pages\CreateAlert::route('/create'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
