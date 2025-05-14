<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Loan Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Loan Application Details')
                    ->schema([
                        Forms\Components\Select::make('borrower_id')
                            ->relationship('borrower', 'name')
                            ->required()
                            ->label('Borrower')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Borrower Name')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_no')
                                    ->required()
                                    ->label('Contact Number')
                                    ->maxLength(15)
                                    ->reactive()
                                    ->unique(table: \App\Models\Borrower::class, column: 'contact_no', ignoreRecord: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            try {
                                                $query = \App\Models\Borrower::where('contact_no', $state);
                                                if ($get('borrower_id')) {
                                                    $query->where('borrower_id', '!=', $get('borrower_id'));
                                                }
                                                $query->firstOrFail();
                                                $set('contact_no', null);
                                                Notification::make()
                                                    ->title('Contact number already exists')
                                                    ->danger()
                                                    ->send();
                                            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                                                // No duplicate found, proceed
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('national_id')
                                    ->required()
                                    ->label('National ID')
                                    ->maxLength(20)
                                    ->reactive()
                                    ->unique(table: \App\Models\Borrower::class, column: 'national_id', ignoreRecord: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            try {
                                                $query = \App\Models\Borrower::where('national_id', $state);
                                                if ($get('borrower_id')) {
                                                    $query->where('borrower_id', '!=', $get('borrower_id'));
                                                }
                                                $query->firstOrFail();
                                                $set('national_id', null);
                                                Notification::make()
                                                    ->title('National ID already exists')
                                                    ->danger()
                                                    ->send();
                                            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                                                // No duplicate found, proceed
                                            }
                                        }
                                    }),
                            ])
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Create Borrower')
                                    ->modalSubmitActionLabel('Create')
                                    ->modalWidth('lg');
                            })
                            ->options(function () {
                                return \App\Models\Borrower::all()->mapWithKeys(function ($borrower) {
                                    $display = $borrower->name;
                                    if ($borrower->contact_no) {
                                        $display .= " - {$borrower->contact_no}";
                                    }
                                    return [$borrower->borrower_id => $display];
                                })->toArray();
                            }),
                        Forms\Components\Select::make('ltype_id')
                            ->relationship('loanType', 'ltype_name')
                            ->required()
                            ->label('Loan Type')
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('ltype_name')
                                    ->required()
                                    ->label('Loan Type Name')
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Create Loan Type')
                                    ->modalSubmitActionLabel('Create')
                                    ->modalWidth('lg');
                            })
                            ->options(function () {
                                return \App\Models\LoanType::all()->mapWithKeys(function ($type) {
                                    $display = $type->ltype_name;
                                    if ($type->ltype_desc) {
                                        $display .= " - {$type->ltype_desc}";
                                    }
                                    return [$type->ltype_id => $display];
                                })->toArray();
                            }),
                        Forms\Components\Select::make('lplan_id')
                            ->relationship('loanPlan', 'lplan_interest')
                            ->required()
                            ->label('Loan Plan')
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('lplan_interest')
                                    ->required()
                                    ->numeric()
                                    ->label('Interest Rate (%)')
                                    ->minValue(0),
                                Forms\Components\TextInput::make('lplan_penalty')
                                    ->required()
                                    ->numeric()
                                    ->label('Penalty Rate (%)')
                                    ->minValue(0),
                            ])
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Create Loan Plan')
                                    ->modalSubmitActionLabel('Create')
                                    ->modalWidth('lg');
                            })
                            ->options(function () {
                                return \App\Models\LoanPlan::all()->mapWithKeys(function ($plan) {
                                    return [$plan->lplan_id => "{$plan->lplan_interest}% interest rate, {$plan->lplan_penalty}% penalty"];
                                })->toArray();
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->label('Loan Amount')
                            ->minValue(1),
                        Forms\Components\TextInput::make('daily_amount')
                            ->numeric()
                            ->required()
                            ->label('Daily Amount')
                            ->minValue(1),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->label('Status')
                            ->options([
                                0 => 'Request',
                                1 => 'Confirmed',
                                2 => 'Released',
                                3 => 'Completed',
                                4 => 'Denied',
                            ])
                            ->default(0)
                            ->hidden(fn (?string $operation): bool => $operation === 'create'),
                        Forms\Components\DatePicker::make('date_released')
                            ->required()
                            ->label('Loan Release Date')
                            ->default(""),
                        Forms\Components\Hidden::make('total_loan')
                            ->required(),
                        Forms\Components\Hidden::make('duration'),
                    ])
                    ->columns(1),
                Section::make('Calculation Results')
                    ->schema([
                        Placeholder::make('total_payable_amount')
                            ->label('Total Payable Amount')
                            ->content(function ($get) {
                                return $get('total_payable_amount') ? 'Ksh. ' . number_format($get('total_payable_amount'), 2) : '-';
                            }),
                        Placeholder::make('loan_duration')
                            ->label('Loan Duration')
                            ->content(function ($get) {
                                return $get('loan_duration') ? number_format($get('loan_duration'), 0) . ' Days' : '-';
                            }),
                        Placeholder::make('daily_payable_amount')
                            ->label('Daily Payable Amount')
                            ->content(function ($get) {
                                return $get('daily_payable_amount') ? 'Ksh. ' . number_format($get('daily_payable_amount'), 2) : '-';
                            }),
                        Placeholder::make('penalty_amount')
                            ->label('Penalty Amount')
                            ->content(function ($get) {
                                return $get('penalty_amount') ? 'Ksh. ' . number_format($get('penalty_amount'), 2) : '-';
                            }),
                        Placeholder::make('due_date')
                            ->label('Due Date')
                            ->content(function ($get) {
                                return $get('due_date') ? Carbon::parse($get('due_date'))->format('Y-m-d') : '-';
                            }),
                    ])
                    ->columns(2)
                    ->hidden(fn ($get) => !$get('total_payable_amount')),
                Forms\Components\Actions::make([
                    Action::make('calculate')
                        ->label('Calculate Amount')
                        ->action(function ($get, $set) {
                            $lplan_id = $get('lplan_id');
                            $amount = floatval($get('amount'));
                            $daily_amount = floatval($get('daily_amount'));
                            $date_released = $get('date_released');

                            if (!$lplan_id || !$amount || !$daily_amount || !$date_released) {
                                Notification::make()
                                    ->title('Validation Error')
                                    ->body('Please enter Loan Plan, Amount, Daily Amount, and Release Date to calculate.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            if ($daily_amount > $amount) {
                                Notification::make()
                                    ->title('Validation Error')
                                    ->body('Daily amount cannot be greater than loan amount.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $loan_plan = \App\Models\LoanPlan::find($lplan_id);
                            if (!$loan_plan) {
                                Notification::make()
                                    ->title('Invalid Loan Plan')
                                    ->body('The selected loan plan is invalid.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $interest = floatval($loan_plan->lplan_interest);
                            $penalty = floatval($loan_plan->lplan_penalty);

                            $total_amount = $amount + ($amount * ($interest / 100));
                            $duration = $total_amount / $daily_amount;

                            if ($duration <= 0) {
                                Notification::make()
                                    ->title('Invalid Calculation')
                                    ->body('The loan repayment period cannot be 0.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $due_date = Carbon::parse($date_released)->addDays(round($duration, 0))->toDateString();

                            $set('total_loan', round($total_amount, 2));
                            $set('duration', round($duration, 0));
                            $set('total_payable_amount', round($total_amount, 2));
                            $set('loan_duration', round($duration, 0));
                            $set('daily_payable_amount', round($daily_amount, 2));
                            $set('penalty_amount', round($penalty, 2));
                            $set('due_date', $due_date);

                            Notification::make()
                                ->title('Calculation Complete')
                                ->body('Loan calculations have been updated successfully.')
                                ->success()
                                ->send();
                        })
                        ->color('primary'),
                ]),
                Section::make('Payment Summary')
                    ->schema([
                        Placeholder::make('total_paid')
                            ->label('Total Paid')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return 'Ksh. ' . number_format($record->totalPaid(), 2);
                                }
                                return '-';
                            }),
                        Placeholder::make('remaining_balance')
                            ->label('Remaining Balance')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return 'Ksh. ' . number_format($record->remainingBalance(), 2);
                                }
                                return '-';
                            }),
                        Placeholder::make('progress')
                            ->label('Payment Progress')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return round($record->paymentProgress(), 2) . '%';
                                }
                                return '-';
                            }),
                    ])
                    ->columns(1)
                    ->hidden(fn ($record) => !$record),
            ]);
    }

 public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_id')
                    ->label('Loan ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('borrower.name')
                    ->label('Borrower')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_loan')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_amount')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_released')
                    ->label('Date Released')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->state(function (Loan $record) {
                        return $record->totalPaid();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Balance')
                    ->numeric()
                    ->prefix('Ksh ')
                    ->state(function (Loan $record) {
                        return $record->remainingBalance();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Loan $record) {
                        if ($record->remainingBalance() == 0) {
                            return 'Completed';
                        }
                        $dueDate = $record->due_date ? \Carbon\Carbon::parse($record->due_date) : null;
                        if ($record->remainingBalance() > 0 && $dueDate && \Carbon\Carbon::today()->gt($dueDate)) {
                            return 'Overdue';
                        }
                        return 'Pending';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Overdue' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Completed' => 'Completed',
                        'Overdue' => 'Overdue',
                        'Pending' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'Completed') {
                            $query->whereRaw('(total_loan - COALESCE((SELECT SUM(payment_amount) FROM payment WHERE payment.loan_id = loan.loan_id), 0)) = 0');
                        } elseif ($data['value'] === 'Overdue') {
                            $query->whereRaw('(total_loan - COALESCE((SELECT SUM(payment_amount) FROM payment WHERE payment.loan_id = loan.loan_id), 0)) > 0')
                                ->whereRaw('DATE_ADD(date_released, INTERVAL duration DAY) < CURDATE()');
                        } elseif ($data['value'] === 'Pending') {
                            $query->whereRaw('(total_loan - COALESCE((SELECT SUM(payment_amount) FROM payment WHERE payment.loan_id = loan.loan_id), 0)) > 0')
                                ->whereRaw('DATE_ADD(date_released, INTERVAL duration DAY) >= CURDATE()');
                        }
                        return $query;
                    }),
                Tables\Filters\Filter::make('date_released')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_released', '>=', $date)
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_released', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Loan')
                    ->icon('heroicon-o-plus'),
                Tables\Actions\Action::make('export')
                    ->label('Export Released Loans')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function (Table $table) {
                        $filterData = $table->getFilter('date_released')->getState();
                        return Excel::download(new LoansExport($filterData), 'released-loans-export-' . now()->format('Y-m-d') . '.xlsx');
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('payments')
                    ->label('Payments')
                    ->url(fn (Loan $record): string => PaymentResource::getUrl('index', [
                        'loan_id' => $record->loan_id,
                        'borrower_id' => $record->borrower_id,
                    ]))
                    ->icon('heroicon-o-banknotes'),
                Tables\Actions\Action::make('addPayment')
                    ->label('Add Payment')
                    ->form([
                        Forms\Components\Hidden::make('loan_id')
                            ->default(fn (Loan $record) => $record->loan_id),
                        Forms\Components\Hidden::make('borrower_id')
                            ->default(fn (Loan $record) => $record->borrower_id),
                        Forms\Components\TextInput::make('payment_amount')
                            ->numeric()
                            ->required()
                            ->label('Payment Amount (Ksh)')
                            ->minValue(1),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->label('Payment Date')
                            ->default("")
                            ->maxDate(now()),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        $payment = new \App\Models\Payment();
                        $payment->loan_id = $data['loan_id'];
                        $payment->borrower_id = $data['borrower_id'];
                        $payment->payment_amount = $data['payment_amount'];
                        $payment->payment_date = $data['payment_date'];
                        $payment->save();

                        Notification::make()
                            ->title('Payment recorded successfully')
                            ->body('Amount: Ksh ' . number_format($data['payment_amount'], 2))
                            ->success()
                            ->send();
                    })
                    ->slideOver()
                    ->modalWidth('md')
                    ->icon('heroicon-o-plus')
                    ->color('success'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (Loan $record, Tables\Actions\DeleteAction $action) {
                        if ($record->hasPayments()) {
                            Notification::make()
                                ->title('Cannot Delete Loan')
                                ->body('This loan has payments associated with it. Please delete the payments first.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $hasPayments = false;
                            foreach ($records as $record) {
                                if ($record->hasPayments()) {
                                    $hasPayments = true;
                                    break;
                                }
                            }
                            if ($hasPayments) {
                                Notification::make()
                                    ->title('Cannot Delete Loans')
                                    ->body('Some selected loans have payments associated with them.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $records->each->delete();
                            Notification::make()
                                ->title('Loans deleted successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->paginated([5]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['total_loan']) || is_null($data['total_loan'])) {
            Notification::make()
                ->title('Calculation Required')
                ->body('Please click "Calculate Amount" before saving the loan.')
                ->danger()
                ->send();
            throw new \Exception('Total loan amount is required.');
        }
        return $data;
    }
}