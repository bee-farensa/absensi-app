<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                            ->relationship('company', 'name') // Sesuai Poin 2.E 
                            ->required()
                            ->searchable()
                            ->preload(),

                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Nama Cabang/Kantor')
                            ->required()
                            ->placeholder('Contoh: Kantor Pusat Jakarta'),

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
                                    ->default(100) // Jarak standar
                                    ->required()
                                    ->suffix('Meter'),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kantor')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('radius')
                    ->label('Radius')
                    ->suffix(' m'),

                \Filament\Tables\Columns\TextColumn::make('latitude')
                    ->label('Koordinat')
                    ->formatStateUsing(fn($record) => $record->latitude . ', ' . $record->longitude),
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
