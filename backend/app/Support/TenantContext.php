<?php

namespace App\Support;

class TenantContext
{
    protected static ?int $tenantId = null;

    protected static bool $bypass = false;

    public static function set(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }

    public static function bypass(bool $value = true): void
    {
        self::$bypass = $value;
    }

    public static function isBypassed(): bool
    {
        return self::$bypass;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$bypass = false;
    }
}
