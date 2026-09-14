<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

/**
 * A Blade @directive() inside a component tag's attribute never compiles. The
 * component-tag compiler captures the attribute text as a literal PHP string
 * before the directive pass runs over the file, so
 * `<x-card x-data="catalogSearch(@js($url))">` ships the raw text `@js($url)`
 * to the browser. That shipped once: Alpine tried to compile it, threw a
 * SyntaxError, and took every component on the page down with it — which read
 * as the installed PWA force-closing.
 *
 * Only {{ }} / {!! !!} echo syntax is special-cased inside a component tag, so
 * `{{ \Illuminate\Support\Js::from($url) }}` is the form that works. Plain HTML
 * elements are unaffected (the directive pass reaches them normally), which is
 * why this only scans <x-...> tags.
 */
class BladeComponentAttributesTest extends TestCase
{
    public function test_no_blade_directive_inside_a_component_tag_attribute(): void
    {
        $files = $this->bladeFiles();

        // Guards against a glob that finds nothing quietly passing this test.
        $this->assertNotEmpty($files);

        $violations = [];

        foreach ($files as $path) {
            foreach ($this->componentTags(file_get_contents($path)) as $tag) {
                preg_match_all('/=\s*"([^"]*)"/', $tag, $values);

                foreach ($values[1] as $value) {
                    if (preg_match('/@[a-zA-Z]\w*\s*\(/', $value, $directive)) {
                        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

                        $violations[] = "{$relative}: {$directive[0]} in \"{$value}\"";
                    }
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", [
            'Blade directives do not compile inside a component tag attribute.',
            'Use {{ \Illuminate\Support\Js::from($value) }} instead of @js($value):',
            ...$violations,
        ]));
    }

    /**
     * The attribute text of every `<x-...>` tag in the given template. Quoted
     * runs are consumed whole so a `>` inside an attribute value doesn't end
     * the tag early.
     *
     * @return array<int, string>
     */
    private function componentTags(string $contents): array
    {
        preg_match_all('/<x-[\w.:-]+((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)\/?>/s', $contents, $matches);

        return $matches[1];
    }

    /** @return array<int, string> */
    private function bladeFiles(): array
    {
        $directory = new \RecursiveDirectoryIterator(resource_path('views'));

        return collect(iterator_to_array(new \RecursiveIteratorIterator($directory)))
            ->filter(fn (\SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn (\SplFileInfo $file) => $file->getPathname())
            ->values()
            ->all();
    }
}
