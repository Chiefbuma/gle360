<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BorrowerResource\Pages;
use App\Models\Borrower;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class BorrowerResource extends Resource
{
    protected static ?string $model = Borrower::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Loan Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->label('Name or Plate Number')
                    ->inlineLabel()
                    ->reactive()
                    ->unique(table: Borrower::class, column: 'name', ignoreRecord: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            try {
                                $query = Borrower::where('name', $state);
                                if ($get('borrower_id')) {
                                    $query->where('borrower_id', '!=', $get('borrower_id'));
                                }
                                $query->firstOrFail();
                                $set('name', null);
                                Notification::make()
                                    ->title('Name or plate number already exists')
                                    ->danger()
                                    ->send();
                            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                                // No duplicate found, proceed
                            }
                        }
                    }),
                TextInput::make('contact_no')
                    ->required()
                    ->maxLength(15)
                    ->label('Contact Number')
                    ->inlineLabel()
                    ->reactive()
                    ->unique(table: Borrower::class, column: 'contact_no', ignoreRecord: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            try {
                                $query = Borrower::where('contact_no', $state);
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
                TextInput::make('national_id')
                    ->required()
                    ->maxLength(20)
                    ->label('National ID')
                    ->inlineLabel()
                    ->reactive()
                    ->unique(table: Borrower::class, column: 'national_id', ignoreRecord: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            try {
                                $query = Borrower::where('national_id', $state);
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
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name or Plate Number'),
                TextColumn::make('contact_no')->label('Contact Number'),
                TextColumn::make('national_id')->label('National ID'),
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
            'index' => Pages\ListBorrowers::route('/'),
            //'create' => Pages\CreateBorrower::route('/create'),
            //'edit' => Pages\EditBorrower::route('/{record}/edit'),
        ];
    }
}