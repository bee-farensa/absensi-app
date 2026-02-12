<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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
    protected static ?string $navigationIcon = 'heroicon-o-users'; // Lebih cocok pakai icon users
    protected static ?string $navigationLabel = 'Manajemen User';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        // Multi-tenancy: Admin PT hanya bisa lihat usernya sendiri
        if (!auth()->user()->hasRole('super_admin')) {
            return $query->where('company_id', auth()->user()->company_id);
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: DATA LOGIN & PRIBADI
                Forms\Components\Section::make('Informasi Akun')
                    ->description('Data yang digunakan untuk login ke sistem')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (Username App)')
                            ->required()
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->helperText('Gunakan NIK untuk login di aplikasi mobile.'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email (Login Web)')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: nik@perusahaan.com'),

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
                            ->required()
                            ->reactive()
                            ->default(auth()->user()->company_id)
                            ->disabled(!auth()->user()->hasRole('super_admin'))
                            ->afterStateUpdated(fn(callable $set) => $set('department_id', null) && $set('position_id', null)),

                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];
                                return Department::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->reactive()
                            ->required(),

                        Forms\Components\Select::make('position_id')
                            ->label('Jabatan')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) return [];
                                return Position::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->required(),

                        Forms\Components\Select::make('roles')
                            ->label('Level Akses (Role)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin_pt',
                        'success' => 'karyawan',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Filter Perusahaan')
                    ->relationship('company', 'name'),
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