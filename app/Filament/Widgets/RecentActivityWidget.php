<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\WaterOrder;
use App\Models\Tank;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent System Activity';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Alert::query()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M d, H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'danger' => 'low_water',
                        'warning' => 'refill_reminder',
                        'success' => 'refill_complete',
                        'info' => 'system',
                    ]),

                Tables\Columns\TextColumn::make('tank.name')
                    ->label('Tank')
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (Alert $record): string {
                        return $record->message;
                    }),

                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'success' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}