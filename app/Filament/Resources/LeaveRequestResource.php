<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Izin & Cuti';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasRole('super_admin')) {
            return $query->whereHas('user', function ($query) {
                $query->where('company_id', auth()->user()->company_id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengajuan Izin')
                    ->description('Silakan isi detail pengajuan cuti, sakit, atau izin karyawan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Karyawan')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        $query = \App\Models\User::query();
                                        if (!auth()->user()->hasRole('super_admin')) {
                                            $query->where('company_id', auth()->user()->company_id);
                                        }
                                        return $query->pluck('name', 'id');
                                    }),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'Sakit' => 'Sakit',
                                        'Izin' => 'Izin',
                                        'Cuti' => 'Cuti',
                                    ])
                                    ->required()
                                    ->label('Tipe Izin'),

                                Forms\Components\DatePicker::make('start_date')
                                    ->required()
                                    ->native(false)
                                    ->label('Tanggal Mulai'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->required()
                                    ->native(false)
                                    ->label('Tanggal Selesai')
                                    ->after('start_date'),
                            ]),

                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Alasan')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Bukti/Lampiran (Surat Dokter/Undangan)')
                            ->directory('absensi/dokumen_izin')
                            ->disk('cloudinary')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->helperText('Format: JPG, PNG, atau PDF. Maks 2MB.')
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                $slugName = Str::slug($filename);
                                return 'izin-' . $slugName . '-' . time() . '.' . $file->getClientOriginalExtension();
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Karyawan')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipe')->badge(),
                Tables\Columns\TextColumn::make('reason')->label('Alasan')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('start_date')->label('Mulai')->date(),
                Tables\Columns\TextColumn::make('end_date')->label('Selesai')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('attachment')
                    ->label('Bukti')
                    ->formatStateUsing(fn($state) => $state ? 'Lihat Dokumen' : 'Tidak Ada Bukti')
                    ->color(fn($state) => $state ? 'primary' : 'gray')
                    ->icon(fn($state) => $state ? 'heroicon-o-document' : null)
                    ->url(fn($record) => $record->attachment ? \Storage::disk('cloudinary')->url($record->attachment) : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('company')
                    ->label('Filter Perusahaan')
                    ->relationship('user.company', 'name')
                    ->visible(fn() => auth()->user()->hasRole('super_admin')),

                \Filament\Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filter Karyawan')
                    ->searchable()
                    ->options(function () {
                        $query = \App\Models\User::query();
                        if (!auth()->user()->hasRole('super_admin')) {
                            $query->where('company_id', auth()->user()->company_id);
                        }
                        return $query->pluck('name', 'id');
                    }),

                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Disetujui',
                        'Rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn($record) => $record->update(['status' => 'Approved']))
                    ->requiresConfirmation()
                    ->visible(
                        fn($record) =>
                        $record->status === 'Pending' &&
                        auth()->user()->hasAnyRole(['super_admin', 'admin_pt'])
                    ),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn($record) => $record->update(['status' => 'Rejected']))
                    ->requiresConfirmation()
                    ->visible(
                        fn($record) =>
                        $record->status === 'Pending' &&
                        auth()->user()->hasAnyRole(['super_admin', 'admin_pt'])
                    ),
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}