<?php

declare(strict_types=1);

namespace GacelaTest\Integration\Framework\UsingConfigInterfacesMapping;

use Gacela\Framework\Gacela;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    public function test_remove_key_from_container(): void
    {
        $this->expectDeprecation();
        Gacela::init(__DIR__);
        $facade = new LocalConfig\Facade();

        self::assertSame(
            'Hello Gacela! Name: Chemaclass & Jesus',
            $facade->generateCompanyAndName()
        );
    }
}
