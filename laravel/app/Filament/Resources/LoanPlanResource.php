<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanPlanResource\Pages;
use App\Models\LoanPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanPlanResource extends Resource
{
    protected static ?string $model = LoanPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'Loan Settings';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('lplan_interest')->numeric()->required(),
                Forms\Components\TextInput::make('lplan_penalty')->numeric()->required(),
                ])
      
                ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lplan_interest'),
                Tables\Columns\TextColumn::make('lplan_penalty'),
            ])
            
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->label('New Payment')
                    ->icon('heroicon-o-plus')
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
            'index' => Pages\ListLoanPlans::route('/'),
            //'create' => Pages\CreateLoanPlan::route('/create'),
            //'edit' => Pages\EditLoanPlan::route('/{record}/edit'),
        ];
    }
}