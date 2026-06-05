<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Manajemen Kantor';

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
                \Filament\Forms\Components\Section::make('Informasi Lokasi Kantor')
                    ->description('Tentukan koordinat dan radius untuk pembatasan wilayah absen (Geofencing).')
                    ->schema([
                        \Filament\Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'name') 
                            ->required()
                            ->searchable()
                            ->preload(),

                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Nama Cabang/Kantor')
                            ->required()
                            ->placeholder('Contoh: Kantor Cabang Jakarta'),

                        \Filament\Forms\Components\Toggle::make('is_branch')
                            ->label('Sebagai Kantor Cabang?')
                            ->default(true)
                            ->required(),

                        \Filament\Forms\Components\Textarea::make('address')
                            ->label('Alamat Kantor')
                            ->required()
                            ->placeholder('Masukkan alamat lengkap kantor...')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\TextInput::make('phone_number')
                            ->label('Nomor Telepon Kantor')
                            ->tel()
                            ->placeholder('Contoh: 0341-xxxxxxx')
                            ->maxLength(20),

                        \Filament\Forms\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('-6.123456'),

                                \Filament\Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('106.123456'),

                                \Filament\Forms\Components\TextInput::make('radius')
                                    ->label('Radius (Meter)')
                                    ->numeric()
                                    ->default(15) 
                                    ->required()
                                    ->suffix('Meter'),
                            ]),

                        \Filament\Forms\Components\Section::make('Jam Operasional')
                            ->schema([
                                \Filament\Forms\Components\TimePicker::make('check_in_time')
                                    ->label('Jam Masuk')
                                    ->required()
                                    ->default('08:00'),

                                \Filament\Forms\Components\TimePicker::make('check_out_time')
                                    ->label('Jam Pulang')
                                    ->required()
                                    ->default('17:00'),
                            ])->columns(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Informasi Kantor')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $in = $record->check_in_time ? Carbon::parse($record->check_in_time)->format('H:i') : '-';
                        $out = $record->check_out_time ? Carbon::parse($record->check_out_time)->format('H:i') : '-';
                        $telp = $record->phone_number ? " | Telp: {$record->phone_number}" : "";
                        return "Jam: $in - $out | Alamat: {$record->address}" . $telp;
                    })
                    ->wrap(),

                \Filament\Tables\Columns\IconColumn::make('is_branch')
                    ->label('Tipe')
                    ->boolean()
                    ->trueIcon('heroicon-o-map')
                    ->falseIcon('heroicon-o-home-modern')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn($record) => $record->is_branch ? 'Kantor Cabang' : 'Kantor Pusat'),

                \Filament\Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => auth()->user()->hasRole('super_admin')),

                \Filament\Tables\Columns\TextColumn::make('radius')
                    ->label('Radius')
                    ->suffix(' m')
                    ->icon('heroicon-m-map-pin')
                    ->color('success')
                    ->alignCenter(),

                \Filament\Tables\Columns\TextColumn::make('location_details')
                    ->label('Koordinat')
                    ->getStateUsing(fn($record) => "{$record->latitude}, {$record->longitude}")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
