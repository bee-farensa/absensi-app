<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                // Kita bungkus dalam Section supaya tampilannya rapi ada kotaknya
                \Filament\Forms\Components\FileUpload::make('logo')
                    ->image() // Memastikan yang diupload adalah gambar
                    ->directory('company-logos') // Folder penyimpanan di storage/app/public
                    ->imageEditor(), // Fitur tambahan untuk crop/edit gambar sederhana
                \Filament\Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Masukkan detail data kantor pusat.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('address')
                            ->label('Alamat Kantor')
                            ->maxLength(255),
                    ])->columns(2),

                \Filament\Forms\Components\Section::make('Konfigurasi Absensi')
                    ->description('Atur lokasi koordinat dan jam kerja.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->placeholder('-6.xxxx'),
                        \Filament\Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->placeholder('112.xxxx'),
                        \Filament\Forms\Components\TextInput::make('radius')
                            ->label('Radius (Meter)')
                            ->numeric()
                            ->default(100),
                        \Filament\Forms\Components\TimePicker::make('check_in_time')
                            ->label('Jam Masuk')
                            ->default('08:00'),
                        \Filament\Forms\Components\TimePicker::make('check_out_time')
                            ->label('Jam Pulang')
                            ->default('17:00'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo'), // Membuat tampilan logo berbentuk bulat
                // Menampilkan nama perusahaan dan bisa dicari (searchable)
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nama PT')
                    ->searchable(),
                // Menampilkan alamat
                \Filament\Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50),
                // Menampilkan jam masuk dan pulang
                \Filament\Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Jam Masuk')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('H:i')),
                \Filament\Tables\Columns\TextColumn::make('check_out_time')
                    ->label('Jam Pulang')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('H:i')),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
