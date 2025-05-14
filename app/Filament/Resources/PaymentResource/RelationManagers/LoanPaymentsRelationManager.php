<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoanPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanPayments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('loan_id')
                    ->relationship('loan', 'ref_no')
                    ->required(),
                    
                Forms\Components\TextInput::make('payment_amount')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.ref_no')
                    ->label('Loan Ref'),
                    
                Tables\Columns\TextColumn::make('payment_amount')
                    ->numeric()
                    ->money(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}