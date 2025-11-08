<?php

namespace App\Filament\Resources\TankResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Assigned Users';

    protected static ?string $recordTitleAttribute = 'email';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('users', 'email')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        $record->full_name . ' (' . $record->email . ')'
                    ),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('can_order_water')
                            ->label('Can Order Water')
                            ->default(true)
                            ->helperText('Allow this user to place water orders for this tank'),

                        Forms\Components\Toggle::make('receive_alerts')
                            ->label('Receive Alerts')
                            ->default(true)
                            ->helperText('Send tank alerts to this user'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'primary' => 'user',
                    ]),

                Tables\Columns\IconColumn::make('can_order_water')
                    ->label('Can Order')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('receive_alerts')
                    ->label('Alerts')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Assigned')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                        'super_admin' => 'Super Admin',
                    ]),
                Tables\Filters\TernaryFilter::make('can_order_water')
                    ->label('Can Order Water'),
                Tables\Filters\TernaryFilter::make('receive_alerts')
                    ->label('Receives Alerts'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('User')
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                $record->full_name . ' (' . $record->email . ') - ' . ucfirst($record->role)
                            )
                            ->helperText('Select a user to assign to this tank'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('can_order_water')
                                    ->label('Can Order Water')
                                    ->default(true)
                                    ->helperText('Allow this user to place water orders'),

                                Forms\Components\Toggle::make('receive_alerts')
                                    ->label('Receive Alerts')
                                    ->default(true)
                                    ->helperText('Send tank alerts to this user'),
                            ]),
                    ])
                    ->modalHeading('Assign User to Tank'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('can_order_water')
                                    ->label('Can Order Water')
                                    ->helperText('Allow this user to place water orders'),

                                Forms\Components\Toggle::make('receive_alerts')
                                    ->label('Receive Alerts')
                                    ->helperText('Send tank alerts to this user'),
                            ]),
                    ])
                    ->modalHeading('Update User Permissions'),

                Tables\Actions\DetachAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove User from Tank')
                    ->modalDescription('Are you sure you want to remove this user\'s access to this tank?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ]);
    }
}
