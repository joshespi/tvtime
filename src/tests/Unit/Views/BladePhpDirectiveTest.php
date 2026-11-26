<?php

namespace Tests\Unit\Views;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Blade pairs an inline `@php(...)` with the next `@endphp` in the file when
 * a block `@php` follows it, compiling the HTML in between as PHP — a parse
 * error that only shows when that view renders. Inline-after-block is fine;
 * inline-before-block is the trap.
 */
class BladePhpDirectiveTest extends TestCase
{
    public function test_no_view_puts_an_inline_php_directive_before_a_php_block(): void
    {
        $offenders = [];

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $contents = $file->getContents();
            $inline = strpos($contents, '@php(');
            $block = strpos($contents, '@endphp');

            if ($inline !== false && $block !== false && $inline < $block) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Use @php ... @endphp blocks in these views instead of @php(...).');
    }
}
