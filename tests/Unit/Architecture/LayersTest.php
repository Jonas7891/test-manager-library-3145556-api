<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * T-19: constitution articles I, II and III, checked by reading the code's `use` statements.
 * It fails if someone adds a forbidden import.
 */
final class LayersTest extends TestCase
{
    private static function phpFiles(string $dir): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 3).'/app/'.$dir));
        foreach ($it as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return list<string> classes imported with `use` (including `use function`) */
    private static function imports(string $file): array
    {
        preg_match_all('/^use\s+(?:function\s+)?([^;]+);/m', file_get_contents($file), $m);

        return $m[1];
    }

    /** @return list<string> imports that are not allowed */
    public static function violations(string $dir, array $allowedPrefixes): array
    {
        $bad = [];
        foreach (self::phpFiles($dir) as $file) {
            foreach (self::imports($file) as $import) {
                $global = ! str_contains($import, '\\');
                $allowed = $global || array_filter($allowedPrefixes, fn ($p) => str_starts_with($import, $p));
                if (! $allowed) {
                    $bad[] = basename($file).' → '.$import;
                }
            }
        }

        return $bad;
    }

    public function test_domain_depends_on_nothing_article_i(): void
    {
        $this->assertSame([], self::violations('Domain', ['App\\Domain\\']));
    }

    public function test_application_only_knows_the_domain_article_ii(): void
    {
        $this->assertSame([], self::violations('Application', ['App\\Application\\', 'App\\Domain\\']));
    }

    public static function layersOutsideComposition(): array
    {
        return [['Domain'], ['Application'], ['Adapters/Inbound'], ['Console']];
    }

    #[DataProvider('layersOutsideComposition')]
    public function test_outbound_adapters_are_only_wired_in_port_bindings_article_iii(string $dir): void
    {
        $bad = [];
        foreach (self::phpFiles($dir) as $file) {
            if (str_contains(file_get_contents($file), 'App\\Adapters\\Outbound')) {
                $bad[] = basename($file);
            }
        }
        $this->assertSame([], $bad);
    }

    public function test_the_checker_detects_a_forbidden_import(): void
    {
        $this->assertNotSame([], self::violations('Adapters/Outbound', ['App\\Domain\\']), 'el verificador debe detectar importaciones fuera de lo permitido');
    }
}
