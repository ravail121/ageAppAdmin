<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationSort(): int
    {
        return -1; // higher priority (lower number = higher up)
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('userID')
                ->label('User ID')
                ->maxLength(50)
                ->disabled()
                ->visible(fn (string $context) => in_array($context, ['edit', 'view'])),            
            
                Forms\Components\TextInput::make('userSub')
                ->label('User Sub')
                ->maxLength(50)
                ->disabled()
                ->visible(fn (string $context) => in_array($context, ['edit', 'view'])),
            
    
                Forms\Components\TextInput::make('firstName')
                    ->required()
                    ->maxLength(50),
    
                Forms\Components\TextInput::make('lastName')
                    ->required()
                    ->maxLength(50),
    
                    Forms\Components\TextInput::make('userHandle')
                    ->label('User Handle')
                    ->required()
                    ->maxLength(50)
                    ->disabled(fn (string $context) => in_array($context, ['edit', 'view']))
                    ->rules([
                        fn (callable $get, $context) => function ($attribute, $value, $fail) use ($get, $context) {
                            if (!empty($value)) {
                                $query = \App\Models\User::where('userHandle', $value);
                                if ($context === 'edit' && $record = $get('record')) {
                                    $query->where('id', '!=', $record->id);
                                }
                                if ($query->exists()) {
                                    $fail('This user handle is already in use.');
                                }
                            }
                        },
                    ]),
                
                
    
                    Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(100)
                    ->disabled(fn (string $context) => $context === 'edit')
                    ->rules([
                        fn (callable $get, $context) => function ($attribute, $value, $fail) use ($get, $context) {
                            $mobile = $get('mobileNumber');
                            if (empty($value) && empty($mobile)) {
                                $fail('Either email or mobile number is required.');
                            }
                            if (!empty($value) && !empty($mobile)) {
                                $fail('Please fill only one: email or mobile number — not both.');
                            }
                
                            if (!empty($value)) {
                                $query = \App\Models\User::where('email', $value);
                                if ($context === 'edit' && $record = $get('record')) {
                                    $query->where('id', '!=', $record->id);
                                }
                                if ($query->exists()) {
                                    $fail('This email is already in use.');
                                }
                            }
                        },
                    ]),
                
                
                
                // Mobile Number field
                Forms\Components\TextInput::make('mobileNumber')
                ->label('Mobile Number')
                ->maxLength(20)
                ->disabled(fn (string $context) => $context === 'edit')
                ->rules([
                    fn (callable $get, $context) => function ($attribute, $value, $fail) use ($get, $context) {
                        $email = $get('email');
                        if (empty($value) && empty($email)) {
                            $fail('Either mobile number or email is required.');
                        }
                        if (!empty($value) && !empty($email)) {
                            $fail('Please fill only one: mobile number or email — not both.');
                        }
            
                        if (!empty($value)) {
                            $query = \App\Models\User::where('mobileNumber', $value);
                            if ($context === 'edit' && $record = $get('record')) {
                                $query->where('id', '!=', $record->id);
                            }
                            if ($query->exists()) {
                                $fail('This mobile number is already in use.');
                            }
                        }
                    },
                ]),
            
            
    
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
    
                    Forms\Components\DateTimePicker::make('lastLoginDate')
    ->disabled()
    ->visible(fn (string $context) => in_array($context, ['edit', 'view'])),

    
                Forms\Components\Textarea::make('userSystemMessage')
                    ->columnSpanFull(),
    
                Forms\Components\Fieldset::make('Profile Image')
                    ->schema([
                        Forms\Components\FileUpload::make('profileImage')
                            ->label('')
                            ->image()
                            ->disk('s3')
                            ->directory('profileImages')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->saveUploadedFileUsing(function ($file) {
                                $disk = 's3';
                                $directory = 'profileImages';
                                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                                $path = $directory . '/' . $filename;
    
                                $success = Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
    
                                if ($success) {
                                    return Storage::disk($disk)->url($path); // store full S3 URL
                                }
    
                                throw new \Exception('Failed to upload file to S3');
                            })
                            ->deleteUploadedFileUsing(function ($state) {
                                $disk = 's3';
                                $parsedUrl = parse_url($state, PHP_URL_PATH);
                                $path = ltrim($parsedUrl, '/'); // remove leading slash if present
    
                                if (Storage::disk($disk)->exists($path)) {
                                    Storage::disk($disk)->delete($path);
                                }
                            })
                            ->hidden(fn (string $context) => $context === 'view'),
    
                        Forms\Components\Placeholder::make('profileImageDisplay')
                            ->label('')
                            ->content(fn ($record) => $record && $record->profileImage
                                ? new HtmlString('<img src="' . $record->profileImage . '" class="h-20 w-20 rounded-full object-cover">')
                                : 'No image available')
                            ->visible(fn (string $context) => $context === 'view')
                            ->extraAttributes(['class' => 'filament-forms-markdown-component']),
                    ])
                    ->columnSpanFull(),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('userID')
                    ->label('User ID')
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
