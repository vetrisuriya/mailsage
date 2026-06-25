<?php

declare(strict_types=1);

namespace MailSage\Categorization;

class Category
{
    /** @var array<string, string[]> */
    private static array $customCategories = [];

    /**
     * Register a custom category with associated keywords.
     *
     * @param string[] $keywords
     */
    public static function register(string $name, array $keywords): void
    {
        self::$customCategories[strtolower($name)] = array_map('strtolower', $keywords);
    }

    /**
     * Remove a custom category.
     */
    public static function unregister(string $name): void
    {
        unset(self::$customCategories[strtolower($name)]);
    }

    /**
     * Clear all custom categories.
     */
    public static function clearAll(): void
    {
        self::$customCategories = [];
    }

    /**
     * @return array<string, string[]>
     */
    public static function getCustomCategories(): array
    {
        return self::$customCategories;
    }

    /**
     * Check whether a custom category is registered.
     */
    public static function has(string $name): bool
    {
        return isset(self::$customCategories[strtolower($name)]);
    }
}
