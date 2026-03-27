<?php

namespace App\Services;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CognitoService
{
    protected CognitoIdentityProviderClient $client;

    public function __construct()
    {
        $credentials = new Credentials(
            config('services.cognito.key'),
            config('services.cognito.secret')
        );

        $this->client = new CognitoIdentityProviderClient([
            'region' => config('services.cognito.region'),
            'version' => 'latest',
            'credentials' => $credentials,
        ]);
    }

    public function createUser(string $identifier): string
    {
        $attributes = [];

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $attributes[] = ['Name' => 'email', 'Value' => $identifier];
            $attributes[] = ['Name' => 'email_verified', 'Value' => 'true'];
        } else {
            $attributes[] = ['Name' => 'phone_number', 'Value' => $identifier];
            $attributes[] = ['Name' => 'phone_number_verified', 'Value' => 'true'];
        }

        $tempPassword = 'Tempv9kLxzQx1!'; // ensures complexity (uppercase, lowercase, symbol)

        $result = $this->client->adminCreateUser([
            'UserPoolId' => config('services.cognito.user_pool_id'),
            'Username'   => $identifier,
            'UserAttributes' => $attributes,
            'TemporaryPassword' => $tempPassword,
            'MessageAction' => 'SUPPRESS',
        ]);

        // Set permanent password to avoid FORCE_CHANGE_PASSWORD status
        $this->client->adminSetUserPassword([
            'UserPoolId' => config('services.cognito.user_pool_id'),
            'Username'   => $identifier,
            'Password'   => $tempPassword,
            'Permanent'  => true,
        ]);

        foreach ($result['User']['Attributes'] as $attr) {
            if ($attr['Name'] === 'sub') {
                return $attr['Value'];
            }
        }

        throw new \Exception('Cognito userSub not found.');
    }

    public function deleteUser(string $identifier): void
    {
        try {
            $this->client->adminDeleteUser([
                'UserPoolId' => config('services.cognito.user_pool_id'),
                'Username'   => $identifier,
            ]);
        } catch (AwsException $e) {
            $code = $e->getAwsErrorCode();

            // If the user is already gone from Cognito, treat as success.
            if ($code === 'UserNotFoundException') {
                return;
            }

            Log::error('Failed to delete Cognito user', [
                'identifier' => $identifier,
                'aws_error_code' => $code,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function setUserPassword(string $identifier, string $password, bool $permanent = true): void
    {
        try {
            $this->client->adminSetUserPassword([
                'UserPoolId' => config('services.cognito.user_pool_id'),
                'Username'   => $identifier,
                'Password'   => $password,
                'Permanent'  => $permanent,
            ]);
        } catch (AwsException $e) {
            Log::error('Failed to set Cognito user password', [
                'identifier' => $identifier,
                'aws_error_code' => $e->getAwsErrorCode(),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
