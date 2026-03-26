<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Services\CognitoService;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'userID';      // ✅ make sure your table has `userID` as the PK
    protected $keyType = 'string';         // ✅ string type if `userID` is not an integer
    public $incrementing = false;          // ✅ disable auto-increment if needed
    public $timestamps = true;  // default, can be omitted if already enabled


    protected $fillable = [
        'userID',
        'userSub',
        'firstName',
        'lastName',
        'userHandle',
        'mobileNumber',
        'email',
        'birthDate',
        'accountStatus',
        'lastLoginDate',
        'userSystemMessage',
        'profileImage',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthDate' => 'date',
        'lastLoginDate' => 'datetime',
    ];

    public function getAccountStatusLabelAttribute(): string
    {
        switch ((int) $this->accountStatus) {
            case 1:
                return 'Active';
            case 2:
                return 'Closed';
            case 3:
                return 'Paused';
            case 4:
                return 'Pending';
            default:
                return 'Unknown';
        }
    }
    public function setProfileImageAttribute($value)
    {
        if ($value && !Str::startsWith($value, 'https://')) {
            $this->attributes['profileImage'] = Storage::disk('s3')->url($value);
        } else {
            $this->attributes['profileImage'] = $value;
        }
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->userID)) {
                $user->userID = (string) Str::uuid();
            }
        });

        static::deleting(function (self $user) {
            $identifier = $user->email ?: $user->mobileNumber;

            // Cognito username is created from email or phone number.
            if (!$identifier) {
                return;
            }

            app(CognitoService::class)->deleteUser($identifier);
        });
    }

}
