<?php

namespace App\Filament\Resources\Books\Tables;

use App\Models\Book;
use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('author')
                    ->searchable(),
                TextColumn::make('publisher')
                    ->searchable(),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('Pinjam')
                        ->icon('heroicon-o-book-open')
                        ->requiresConfirmation()
                        ->visible(function () {
                            /** @var \App\Models\User|null $user */
                            $user = Filament::auth()->user();

                            return ! $user?->isAdmin();
                        })
                        ->disabled(fn (Book $record) => $record->stock < 1)
                        ->action(function (Book $record) {
                            $aktif = Loan::where('user_id', Filament::auth()->id())->where('status', 'dipinjam')->count();
                            if ($aktif >= 3) {
                                Notification::make()->title('Maksimal Pinjam 3 Buku!!')->danger()->send();
                            } else {
                                Loan::create([
                                    'user_id' => Filament::auth()->id(),
                                    'book_id' => $record->id,
                                    'loan_date' => now(),
                                    'due_date' => now()->addDays(7),
                                ]);
                                $record->decrement('stock');
                                Notification::make()->title('Buku telah berhasil dipinjam!')->success()->send();
                            }
                        }),
                     ])
                        ->toolbarActions([
                            BulkActionGroup::make([
                                DeleteBulkAction::make(),
                            ]),
                        ]);
                }
            }
