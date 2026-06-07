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
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                // SECTION 1: BRANDING (Logo)
                Forms\Components\Section::make('Branding Aplikasi')
                    ->description('Atur identitas visual perusahaan untuk tampilan aplikasi mobile karyawan.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo Perusahaan')
                            ->image()
                            ->directory('absensi/logo_perusahaan')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                // Mengambil nama asli file tanpa ekstensi
                                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                                // Mengubah nama file jadi format slug (bersih dari spasi/karakter aneh)
                                $slugName = Str::slug($filename);

                                // Menggabungkan slug nama + timestamp unik + ekstensi asli file
                                return $slugName . '-' . time() . '.' . $file->getClientOriginalExtension();
                            }),
                    ]), // <-- Tadi kurang penutup kurung siku bagian ini

                // SECTION 2: INFORMASI PERUSAHAAN
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Detail nama perusahaan.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),
                    ]),

                // SECTION 3: KANTOR PUSAT
                Forms\Components\Section::make('Informasi Kantor Pusat')
                    ->description('Tentukan lokasi dan jam operasional kantor pusat saat membuat perusahaan baru.')
                    ->visible(fn(string $context): bool => $context === 'create')
                    ->schema([
                        Forms\Components\Textarea::make('office_address')
                            ->label('Alamat Kantor Pusat')
                            ->required()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('office_phone_number')
                            ->label('Nomor Telepon Kantor Pusat')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('office_latitude')
                            ->label('Latitude')
                            ->required()
                            ->numeric()
                            ->dehydrated(false)
                            ->placeholder('-7.123456'),

                        Forms\Components\TextInput::make('office_longitude')
                            ->label('Longitude')
                            ->required()
                            ->numeric()
                            ->dehydrated(false)
                            ->placeholder('112.123456'),

                        Forms\Components\TextInput::make('office_radius')
                            ->label('Radius Absensi (meter)')
                            ->required()
                            ->numeric()
                            ->dehydrated(false)
                            ->placeholder('5'),

                        Forms\Components\TimePicker::make('office_check_in_time')
                            ->label('Jam Masuk')
                            ->required()
                            ->default('08:00')
                            ->seconds(false)
                            ->dehydrated(false),

                        Forms\Components\TimePicker::make('office_check_out_time')
                            ->label('Jam Pulang')
                            ->required()
                            ->default('17:00')
                            ->seconds(false)
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('cloudinary')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama PT')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('super_admin')),
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