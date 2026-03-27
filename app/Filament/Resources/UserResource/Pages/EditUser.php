<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\CognitoService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('setPassword')
                ->label('Set password')
                ->icon('heroicon-o-key')
                ->modalHeading('Set new password')
                ->modalDescription('This will immediately replace the user’s password in Cognito. Deliver it securely; it will not be shown again.')
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Set password')
                ->form([
                    TextInput::make('password')
                        ->label('New password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8),
                    TextInput::make('password_confirmation')
                        ->label('Confirm password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password'),
                ])
                ->action(function (array $data): void {
                    /** @var \App\Models\User $user */
                    $user = $this->record;
                    $identifier = $user->email ?: $user->mobileNumber;

                    if (!$identifier) {
                        Notification::make()
                            ->title('User has no email or mobile number')
                            ->body('Cognito username is derived from email or mobile number; cannot set password.')
                            ->danger()
                            ->send();

                        return;
                    }

                    app(CognitoService::class)->setUserPassword($identifier, $data['password'], true);

                    Notification::make()
                        ->title('Password updated')
                        ->success()
                        ->send();
                }),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
