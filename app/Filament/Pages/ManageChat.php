<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class ManageChat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Customer Chat';
    protected static ?string $slug = 'customer-chats';
    protected string $view = 'filament.pages.manage-chat';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->hasRole('Super Admin') || $user->hasRole('Perental');
    }
}