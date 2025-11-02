<?php

namespace App\Enums;

enum GroupRole: string
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case MEMBER = 'member';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
            self::MEMBER => 'Member',
        };
    }

    /**
     * Get role hierarchy level 
     */
    public function level(): int
    {
        return match ($this) {
            self::ADMIN => 3,
            self::MODERATOR => 2,
            self::MEMBER => 1,
        };
    }

    /**
     * Check if this role has higher or equal level than another role
     */
    public function isHigherOrEqualTo(GroupRole $role): bool
    {
        return $this->level() >= $role->level();
    }
}
