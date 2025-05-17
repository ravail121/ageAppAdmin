<?php
namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MyProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public static ?string $navigationLabel = 'My Profile';
    public static ?string $title = 'My Profile';
    public static string $view = 'filament.pages.my-profile';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public $name, $email, $password;

    public function mount(): void
    {
        $admin = Auth::user();
        $this->form->fill([
            'name' => $admin->name,
            'email' => $admin->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')->email()->required(),
            Forms\Components\TextInput::make('password')
                ->password()
                ->label('New Password')
                ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                ->nullable()
                ->maxLength(255),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();
        $admin = Auth::user();

        if (!$data['password']) {
            unset($data['password']);
        }

        $admin->update($data);

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}
