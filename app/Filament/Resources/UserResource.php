<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('userSub')
                    ->maxLength(50)
                    ->disabled(fn (string $context) => $context === 'edit'),
                Forms\Components\TextInput::make('firstName')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('lastName')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('userHandle')
                    ->required()
                    ->maxLength(50),
                    Forms\Components\TextInput::make('mobileNumber')
                    ->label('Mobile Number')
                    ->maxLength(20)
                    ->disabled(fn (string $context) => $context === 'edit'),
                
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(100)
                    ->disabled(fn (string $context) => $context === 'edit'),
                Forms\Components\DatePicker::make('birthDate')
                    ->required(),
                    Forms\Components\Select::make('accountStatus')
                    ->label('Account Status')
                    ->required()
                    ->options([
                        1 => 'Active',
                        2 => 'Closed',
                        3 => 'Paused',
                        4 => 'Pending',
                    ])
                    ->default(4)
                    ->native(false),
                
                Forms\Components\DateTimePicker::make('lastLoginDate'),
                Forms\Components\Textarea::make('userSystemMessage')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('profileImage')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('userID')
                    ->searchable()
                    ->limit(7),
                Tables\Columns\TextColumn::make('firstName')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastName')
                    ->searchable(),
                Tables\Columns\TextColumn::make('userHandle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobileNumber')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birthDate')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('accountStatus')
                    ->label('Account Status')
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            1 => 'Active',
                            2 => 'Closed',
                            3 => 'Paused',
                            4 => 'Pending',
                            default => 'Unknown',
                        };
                    })
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => (int) $state === 1, // Active
                        'danger' => fn ($state) => (int) $state === 2,  // Closed
                        'warning' => fn ($state) => (int) $state === 3, // Paused
                        'gray'    => fn ($state) => (int) $state === 4, // Pending
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('lastLoginDate')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('profileImage')
                    ->label('Profile')
                    ->circular()
                    ->height(40)
                    ->width(40),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
