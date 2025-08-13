<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserStatus: string implements HasLabel, HasColor, HasIcon, HasDescription
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Archived = 'archived';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Blocked => 'danger',
            self::Pending => 'warning',
            default => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Active => 'heroicon-s-check-badge',
            self::Pending => 'heroicon-s-clock',
            default => 'heroicon-s-no-symbol',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'User can log in and use the system.',
            self::Blocked => 'User is temporarily prevented from accessing the system.',
            self::Inactive => 'User account exists but is not currently active (e.g., not yet verified or deactivated).',
            self::Pending => 'User registration is awaiting approval or email verification.',
            self::Archived => 'User account is removed or archived, but data may be retained for records.',
        };
    }

    public static function default(): self
    {
        return self::Active;
    }
}
