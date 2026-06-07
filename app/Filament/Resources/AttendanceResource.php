<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
// use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
// use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Data Absensi';

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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('time_out')
                    ->label('Jam Pulang'),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->is_late ? 'Terlambat' : 'Tepat Waktu')
                    ->color(fn(string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Terlambat' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\ImageColumn::make('pic_in')
                    ->label('Foto Masuk')
                    ->disk('public')
                    ->visibility('public')
                    ->circular(),

                Tables\Columns\ImageColumn::make('pic_out')
                    ->label('Foto Pulang')
                    ->disk('public')
                    ->visibility('public')
                    ->circular(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                //Filter Nama Karyawan (Hanya yang satu PT dengan Admin)
                SelectFilter::make('user_id')
                    ->label('Pilih Karyawan')
                    ->options(function () {
                        $user = auth()->user();
                        $query = \App\Models\User::query();

                        // Jika bukan super_admin, hanya tampilkan karyawan di PT yang sama
                        if (!$user->hasRole('super_admin')) {
                            $query->where('company_id', $user->company_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->searchable(),

                //Filter PT / Perusahaan (Hanya untuk Super Admin)
                SelectFilter::make('company_id')
                    ->label('PT / Perusahaan')
                    ->options(\App\Models\Company::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->whereHas('user', function ($query) use ($value) {
                                $query->where('company_id', $value);
                            })
                        );
                    })
                    ->visible(fn() => auth()->user()->hasRole('super_admin'))
                    ->searchable(),

                //Filter Departemen
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->options(function () {
                        $user = auth()->user();
                        $query = \App\Models\Department::query();

                        // Jika bukan super_admin, hanya tampilkan departemen di PT yang sama
                        if (!$user->hasRole('super_admin')) {
                            $query->where('company_id', $user->company_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $user = auth()->user();
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->whereHas('user', function ($q) use ($value, $user) {
                                $q->where('department_id', $value);
                                // Extra security: jika bukan super_admin, pastikan department dari company yang sama
                                if (!$user->hasRole('super_admin')) {
                                    $q->where('company_id', $user->company_id);
                                }
                            })
                        );
                    })
                    ->searchable(),

                //Filter Kantor / Cabang
                SelectFilter::make('office_id')
                    ->label('Kantor / Cabang')
                    ->options(function () {
                        $user = auth()->user();
                        $query = \App\Models\Office::query();

                        if (!$user->hasRole('super_admin')) {
                            $query->where('company_id', $user->company_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->where('office_id', $value)
                        );
                    })
                    ->searchable(),
            ])
            ->actions([
                //
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
            'index' => Pages\ListAttendances::route('/'),
        ];
    }
}
