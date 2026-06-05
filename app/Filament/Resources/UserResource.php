<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Office;
use App\Models\Department;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Manajemen User';

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
        $isSuperAdmin = auth()->user()->hasRole('super_admin');

        return $form
            ->schema([
                // SECTION 1: DATA LOGIN & PRIBADI
                Forms\Components\Section::make('Informasi Akun')
                    ->description('Data yang digunakan untuk login ke sistem')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Foto Profil')
                            ->image()
                            ->directory('profile-photos')
                            ->avatar()
                            ->imageEditor(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->numeric()
                            ->required(!$isSuperAdmin) // opsional untuk superadmin
                            ->unique(ignoreRecord: true)
                            ->helperText($isSuperAdmin ? 'Opsional untuk Super Admin.' : ''),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Gunakan email aktif karyawan untuk login.'),

                        Forms\Components\TextInput::make('password')
                            ->label('Password Sementara')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                    ])->columns(2),

                // SECTION 2: PENEMPATAN KERJA
                Forms\Components\Section::make('Penempatan & Hak Akses')
                    ->description('Tentukan lokasi kerja dan level akses user')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'name')
                            ->required(!$isSuperAdmin)
                            ->reactive()
                            ->default(auth()->user()->company_id)
                            ->disabled(!$isSuperAdmin)
                            ->dehydrated(true)
                            ->afterStateUpdated(fn(callable $set) => $set('office_id', null) && $set('department_id', null) && $set('position_id', null)),

                        Forms\Components\Select::make('office_id')
                            ->label('Kantor Penempatan')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];
                                return Office::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->required(!$isSuperAdmin)
                            ->helperText('Tentukan di kantor mana karyawan ini akan bekerja.'),

                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];
                                return Department::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->reactive()
                            ->required(!$isSuperAdmin),

                        Forms\Components\Select::make('position_id')
                            ->label('Jabatan')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];
                                return Position::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->required(!$isSuperAdmin),

                        Forms\Components\Select::make('roles')
                            ->label('Level Akses (Role)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->reactive()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->description(fn($record) => "NIK: {$record->nik} | {$record->email}")
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan / Kantor')
                    ->description(fn($record) => $record->office?->name ?? '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: !auth()->user()->hasRole('super_admin')),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen / Jabatan')
                    ->description(fn($record) => $record->position?->name ?? '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin_pt',
                        'success' => 'karyawan',
                    ]),

                Tables\Columns\IconColumn::make('face_embedding')
                    ->label('Wajah')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => !empty($record->face_embedding))
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Filter Perusahaan')
                    ->relationship('company', 'name'),

                Tables\Filters\Filter::make('face_registered')
                    ->label('Sudah Registrasi Wajah')
                    ->query(fn(Builder $query) => $query->whereNotNull('face_embedding')),

                Tables\Filters\Filter::make('face_not_registered')
                    ->label('Belum Registrasi Wajah')
                    ->query(fn(Builder $query) => $query->whereNull('face_embedding')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}