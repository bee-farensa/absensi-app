<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToleranceCoefficientTestResource\Pages;
use App\Models\ToleranceCoefficientTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ToleranceCoefficientTestResource extends Resource
{
    protected static ?string $model = ToleranceCoefficientTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Tolerance Coefficient Testing';
    protected static ?string $navigationGroup = 'Testing & Monitoring';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasRole('super_admin')) {
            return $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test Information')
                    ->schema([
                        Forms\Components\TextInput::make('test_phase')
                            ->label('Test Phase')
                            ->disabled(),

                        Forms\Components\TextInput::make('tolerance_coefficient')
                            ->label('Tolerance Coefficient (k)')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DatePicker::make('test_date')
                            ->label('Test Date')
                            ->disabled(),

                        Forms\Components\Select::make('result')
                            ->label('Result')
                            ->options([
                                'accepted' => '✓ Accepted',
                                'rejected' => '✗ Rejected',
                            ])
                            ->disabled()
                            ->color(fn($state) => match($state) {
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Location & GPS Data')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('gps_accuracy')
                            ->label('GPS Accuracy (m)')
                            ->numeric()
                            ->disabled()
                            ->suffix('meter'),

                        Forms\Components\TextInput::make('attendance_type')
                            ->label('Attendance Type')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Distance Calculation')
                    ->schema([
                        Forms\Components\TextInput::make('distance_to_office')
                            ->label('Distance to Office (m)')
                            ->numeric()
                            ->disabled()
                            ->suffix('meter'),

                        Forms\Components\TextInput::make('office_radius')
                            ->label('Base Office Radius (m)')
                            ->numeric()
                            ->disabled()
                            ->suffix('meter'),

                        Forms\Components\TextInput::make('effective_radius')
                            ->label('Effective Radius (m)')
                            ->numeric()
                            ->disabled()
                            ->suffix('meter'),

                        Forms\Components\TextInput::make('distance_variance')
                            ->label('Distance Variance (m)')
                            ->numeric()
                            ->disabled()
                            ->helperText('Negative = inside radius, Positive = outside radius'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('test_date')
                    ->label('Date')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('test_phase')
                    ->label('Phase')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'phase_1' => 'info',
                        'phase_2' => 'warning',
                        'phase_3' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tolerance_coefficient')
                    ->label('k Value')
                    ->alignment('center')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Office')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('attendance_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'check_in' => 'success',
                        'check_out' => 'warning',
                        default => 'gray',
                    })
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('result')
                    ->label('Result')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match($state) {
                        'accepted' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('distance_to_office')
                    ->label('Distance (m)')
                    ->numeric(decimalPlaces: 2)
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('effective_radius')
                    ->label('Effective Radius (m)')
                    ->numeric(decimalPlaces: 2)
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gps_accuracy')
                    ->label('GPS Acc (m)')
                    ->numeric(decimalPlaces: 2)
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('test_phase')
                    ->label('Phase')
                    ->options([
                        'phase_1' => 'Phase 1 (k=0.5)',
                        'phase_2' => 'Phase 2 (k=1.0)',
                        'phase_3' => 'Phase 3 (k=1.2)',
                    ]),

                Tables\Filters\SelectFilter::make('tolerance_coefficient')
                    ->label('Tolerance Coefficient')
                    ->options([
                        '0.5' => 'k = 0.5 (Ketat)',
                        '1.0' => 'k = 1.0 (Normal)',
                        '1.2' => 'k = 1.2 (Toleransi)',
                    ]),

                Tables\Filters\SelectFilter::make('result')
                    ->label('Result')
                    ->options([
                        'accepted' => 'Accepted ✓',
                        'rejected' => 'Rejected ✗',
                    ]),

                Tables\Filters\SelectFilter::make('attendance_type')
                    ->label('Attendance Type')
                    ->options([
                        'check_in' => 'Check In',
                        'check_out' => 'Check Out',
                    ]),

                Tables\Filters\SelectFilter::make('office_id')
                    ->label('Office')
                    ->relationship('office', 'name'),

                Tables\Filters\Filter::make('test_date')
                    ->label('Test Date')
                    ->form([
                        Forms\Components\DatePicker::make('test_date_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('test_date_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['test_date_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('test_date', '>=', $date),
                            )
                            ->when(
                                $data['test_date_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('test_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
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
            'index' => Pages\ListToleranceCoefficientTests::route('/'),
            'view' => Pages\ViewToleranceCoefficientTest::route('/{record}'),
        ];
    }
}