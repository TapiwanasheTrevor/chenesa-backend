<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Organizations';
    protected static ?string $modelLabel = 'Organization';
    protected static ?string $pluralModelLabel = 'Organizations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'residential' => 'Residential',
                        'commercial' => 'Commercial',
                        'industrial' => 'Industrial',
                    ])
                    ->required(),
                Forms\Components\Select::make('country')
                    ->options([
                        'zimbabwe' => 'Zimbabwe',
                        'south_africa' => 'South Africa',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('contact_email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('contact_phone'),
                Forms\Components\Textarea::make('address'),
                Forms\Components\TextInput::make('city'),
                Forms\Components\Select::make('subscription_status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'residential',
                        'success' => 'commercial',
                        'warning' => 'industrial',
                    ]),
                Tables\Columns\TextColumn::make('country')
                    ->badge(),
                Tables\Columns\TextColumn::make('contact_email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('subscription_status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
                Tables\Filters\SelectFilter::make('country'),
                Tables\Filters\SelectFilter::make('subscription_status'),
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
