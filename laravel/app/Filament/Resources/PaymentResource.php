<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Loan Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('borrower_id')
                    ->relationship('borrower', 'name')
                    ->required()
                    ->label('Borrower')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('loan_id', null);
                    })
                    ->preload(),
                
                Forms\Components\Select::make('loan_id')
                    ->relationship('loan', 'loan_id')
                    ->required()
                    ->label('Loan Reference')
                    ->searchable()
                    ->preload()
                    ->options(function (callable $get) {
                        $borrowerId = $get('borrower_id');
                        if ($borrowerId) {
                            return \App\Models\Loan::where('borrower_id', $borrowerId)
                                ->whereIn('status', [1, 2, 5]) // Confirmed, Released, or Overdue loans
                                ->get()
                                ->mapWithKeys(function ($loan) {
                                    return [$loan->loan_id => "Loan #{$loan->loan_id} (Balance: Ksh " . number_format($loan->remainingBalance(), 2) . ")"];
                                });
                        }
                        return \App\Models\Loan::whereIn('status', [1, 2, 5])
                            ->get()
                            ->mapWithKeys(function ($loan) {
                                return [$loan->loan_id => "Loan #{$loan->loan_id} (Balance: Ksh " . number_format($loan->remainingBalance(), 2) . ")"];
                            });
                    }),
                
                Forms\Components\TextInput::make('payment_amount')
                    ->numeric()
                    ->required()
                    ->label('Payment Amount')
                    ->minValue(1)
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, $fail) use ($get) {
                                $loanId = $get('loan_id');
                                if ($loanId) {
                                    $loan = \App\Models\Loan::find($loanId);
                                    if ($loan && $value > $loan->remainingBalance()) {
                                        $fail("The payment amount cannot exceed the remaining balance of Ksh " . number_format($loan->remainingBalance(), 2));
                                    }
                                }
                            };
                        },
                    ]),
                
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->label('Payment Date')
                    ->default(now())
                    ->maxDate(now()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_id')
                    ->label('Loan ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('borrower.name')
                    ->label('Borrower')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('payment_amount')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->sortable()
                    ->label('Amount'),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                
               
            ])
            ->modifyQueryUsing(function ($query) {
                $loanId = request()->query('loan_id');
                $borrowerId = request()->query('borrower_id');
                
                if ($loanId) {
                    $query->where('loan_id', $loanId);
                }
                
                if ($borrowerId) {
                    $query->where('borrower_id', $borrowerId);
                }
                
                return $query;
            })
            ->filters([
                Tables\Filters\SelectFilter::make('borrower')
                    ->relationship('borrower', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('loan')
                    ->relationship('loan', 'loan_id')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListPayments::route('/'),
            //'create' => Pages\CreatePayment::route('/create'),
            //'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}