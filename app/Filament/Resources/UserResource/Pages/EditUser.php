<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\CognitoService;
use Aws\Exception\AwsException;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

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
                        ->minLength(8)
                        ->rules([
                            'regex:/[a-z]/',
                            'regex:/[A-Z]/',
                            'regex:/[0-9]/',
                            'regex:/[^A-Za-z0-9]/',
                        ])
                        ->helperText('Must be at least 8 characters and include uppercase, lowercase, number, and symbol.'),
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

                    // Keep original casing; Cognito usernames can be case-sensitive.
                    $identifier = trim($identifier);

                    try {
                        Log::info('Filament admin set password requested', [
                            'identifier' => $identifier,
                            'userID' => $user->userID ?? null,
                        ]);

                        app(CognitoService::class)->setUserPassword($identifier, $data['password'], true);
                        $userInfo = app(CognitoService::class)->getUser($identifier);
                    } catch (AwsException $e) {
                        $code = $e->getAwsErrorCode();
                        $message = $e->getAwsErrorMessage() ?: $e->getMessage();

                        Notification::make()
                            ->title('Cognito rejected the password')
                            ->body(($code ? "{$code}: " : '') . $message)
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Password updated')
                        ->body('Username: ' . $identifier . (isset($userInfo['UserLastModifiedDate']) ? (' • Cognito last modified: ' . (string) $userInfo['UserLastModifiedDate']) : ''))
                        ->success()
                        ->send();
                }),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
