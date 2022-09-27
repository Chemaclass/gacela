<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Framework\CustomNoCacheDirectory;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\ClassResolver\ClassNameProfiler;
use Gacela\Framework\ClassResolver\DocBlockService\CustomServicesProfiler;
use Gacela\Framework\Gacela;
use PHPUnit\Framework\TestCase;

final class NoFileCacheFeatureTest extends TestCase
{
    public function setUp(): void
    {
        Gacela::bootstrap(__DIR__, static function (GacelaConfig $config): void {
            $config->setCacheEnabled(false);
            $config->setProfilerDirectory('custom/no-caching-dir');
        });
    }

    public function test_custom_no_caching_dir(): void
    {
        $facade = new Module\Facade();
        self::assertSame('name', $facade->getName());

        self::assertFileDoesNotExist(__DIR__ . '/custom/no-caching-dir/' . ClassNameProfiler::CACHE_FILENAME);
        self::assertFileDoesNotExist(__DIR__ . '/custom/no-caching-dir/' . CustomServicesProfiler::CACHE_FILENAME);
    }
}
