<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Resources\SubscriptionPlanResource\RelationManagers;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Basic Plan'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Brief description of what this plan includes'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('currency')
                                    ->required()
                                    ->maxLength(3)
                                    ->default('USD')
                                    ->placeholder('USD'),
                            ]),

                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'annually' => 'Annually',
                            ])
                            ->default('monthly'),
                    ]),

                Forms\Components\Section::make('Plan Limits')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_tanks')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(5)
                                    ->suffix('tanks'),

                                Forms\Components\TextInput::make('max_users')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(3)
                                    ->suffix('users'),
                            ]),
                    ]),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\TagsInput::make('features')
                            ->placeholder('Enter features (press Enter after each)')
                            ->helperText('List the key features included in this plan')
                            ->suggestions([
                                'Real-time monitoring',
                                'Mobile app access',
                                'Email alerts',
                                'SMS notifications',
                                'API access',
                                'Advanced analytics',
                                'Custom reports',
                                'Priority support',
                                'Data export',
                                'Multi-user access',
                            ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Enable this plan for new subscriptions'),

                        Forms\Components\Toggle::make('is_featured')
                            ->default(false)
                            ->helperText('Mark as featured/recommended plan'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('price')
                    ->money('currency')
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'success',
                        'quarterly' => 'warning',
                        'annually' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('max_tanks')
                    ->suffix(' tanks')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_users')
                    ->suffix(' users')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('organizations_count')
                    ->counts('organizations')
                    ->label('Subscribers')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Plans')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Plans')
                    ->boolean()
                    ->trueLabel('Featured only')
                    ->falseLabel('Non-featured only')
                    ->native(false),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annually' => 'Annually',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Toggle Active Status')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => !$record->is_active]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('warning'),
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
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
