<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanScheduleResource\Pages;
use App\Models\LoanSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanScheduleResource extends Resource
{
    protected static ?string $model = LoanSchedule::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('loan_id')
                    ->relationship('loan', 'ref_no')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.ref_no'),
                Tables\Columns\TextColumn::make('due_date')->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanSchedules::route('/'),
            'create' => Pages\CreateLoanSchedule::route('/create'),
            'edit' => Pages\EditLoanSchedule::route('/{record}/edit'),
        ];
    }
}