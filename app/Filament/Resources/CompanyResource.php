<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Manajemen Perusahaan';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika yang login BUKAN Super Admin, batasi hanya perusahaannya saja
        if (!auth()->user()->hasRole('super_admin')) {
            return $query->where('id', auth()->user()->company_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: BRANDING (Logo & Warna)
                Forms\Components\Section::make('Branding Aplikasi')
                    ->description('Atur identitas visual perusahaan untuk tampilan aplikasi mobile karyawan.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo Perusahaan')
                            ->image()
                            ->directory('company-logos')
                            ->imageEditor()
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('theme_color')
                            ->label('Warna Tema Aplikasi')
                            ->default('#2ecc71') // Default hijau sesuai desainmu
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                // SECTION 2: INFORMASI DASAR
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Masukkan detail data kantor pusat.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Alamat Kantor')
                            ->maxLength(255),
                    ])->columns(2),

                // SECTION 3: KONFIGURASI ABSENSI
                Forms\Components\Section::make('Konfigurasi Absensi & Lokasi')
                    ->description('Atur titik koordinat GPS dan jam operasional.')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->placeholder('-6.xxxx'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->placeholder('112.xxxx'),
                        Forms\Components\TextInput::make('radius')
                            ->label('Radius (Meter)')
                            ->numeric()
                            ->default(100),
                        Forms\Components\TimePicker::make('check_in_time')
                            ->label('Jam Masuk')
                            ->default('08:00'),
                        Forms\Components\TimePicker::make('check_out_time')
                            ->label('Jam Pulang')
                            ->default('17:00'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama PT')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ColorColumn::make('theme_color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Jam Masuk')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('H:i') : '-'),

                Tables\Columns\TextColumn::make('check_out_time')
                    ->label('Jam Pulang')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('H:i') : '-'),
            ])
            ->filters([])
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}