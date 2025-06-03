<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\CognitoService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $identifier = $data['email'] ?? $data['mobileNumber'] ?? null;

        if (!$identifier) {
            throw new \Exception('Either email or mobile number is required.');
        }

        $cognito = new CognitoService();
        $data['userSub'] = $cognito->createUser($identifier);

        return $data;
    }
}
