<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarNavigationTranslationTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function localeProvider(): array
    {
        return [
            'ru' => ['ru'],
            'tk' => ['tk'],
        ];
    }

    /**
     * @dataProvider localeProvider
     */
    public function test_sidebar_group_titles_are_translated(string $locale): void
    {
        foreach (['operations', 'content', 'system'] as $group) {
            $key = "layout.nav_groups.{$group}";

            $this->assertNotSame($key, __($key, [], $locale), "Missing {$key} for locale {$locale}");
        }
    }
}
