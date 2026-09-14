<?php

namespace Tests\Unit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * Two production 500s have come from a model whose table name doesn't match
 * Eloquent's naive pluralisation (`watch_status`, `notifications_sent`), each
 * found at runtime and fixed with its own $table override. This catches the
 * whole class up front instead of one outage at a time.
 */
class ModelTableNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_model_resolves_to_an_existing_table(): void
    {
        $models = $this->models();

        // Guards against a glob that finds nothing quietly passing this test.
        $this->assertNotEmpty($models);

        foreach ($models as $class) {
            $table = (new $class)->getTable();

            $this->assertTrue(
                Schema::hasTable($table),
                "{$class} resolves to table [{$table}], which does not exist. Set \$table on the model."
            );
        }
    }

    /**
     * @return array<int, class-string<Model>>
     *
     * File::allFiles() rather than glob('Models/*.php'): the flat glob quietly
     * misses anything under Models/Concerns or any future subdirectory, so a
     * model added down there wouldn't be checked at all — exactly the kind of
     * silent gap this test exists to avoid.
     */
    private function models(): array
    {
        return collect(File::allFiles(app_path('Models')))
            ->map(fn ($file) => 'App\\Models\\'.str_replace(
                ['/', '\\', '.php'],
                ['\\', '\\', ''],
                $file->getRelativePathname()
            ))
            ->filter(function (string $class) {
                if (! class_exists($class)) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
            })
            ->values()
            ->all();
    }
}
