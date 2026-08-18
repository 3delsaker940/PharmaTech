<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PharmacyResource\Pages;
use App\Filament\Resources\PharmacyResource\RelationManagers;
use App\Models\Pharmacy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PharmacyResource extends Resource
{
    protected static ?string $model = Pharmacy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Pharmacy Name')
                    ->disabled(),

                // إظهار المحافظة أولاً
                Forms\Components\TextInput::make('governorate')
                    ->label('Governorate')
                    ->formatStateUsing(fn($record) => $record?->city?->governorate?->name)
                    ->disabled(),

                // إظهار المدينة
                Forms\Components\TextInput::make('city')
                    ->label('City')
                    ->formatStateUsing(fn($record) => $record?->city?->name)
                    ->disabled(),

                Forms\Components\TextInput::make('address')
                    ->label('Address')
                    ->disabled(),

                Forms\Components\TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->tel()
                    ->disabled(),

                Forms\Components\TextInput::make('license_number')
                    ->label('License Number')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPharmacies::route('/'),
            'create' => Pages\CreatePharmacy::route('/create'),
            'edit' => Pages\EditPharmacy::route('/{record}/edit'),
        ];
    }
}
