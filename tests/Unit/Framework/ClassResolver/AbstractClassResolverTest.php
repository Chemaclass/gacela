<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Framework\ClassResolver;

use Gacela\Framework\ClassResolver\AbstractClassResolver;
use PHPUnit\Framework\TestCase;

final class AbstractClassResolverTest extends TestCase
{
    public function test_error_when_non_allowed_anon_global_type(): void
    {
        $this->expectErrorMessage("Type 'Custom' not allowed");

        AbstractClassResolver::addAnonymousGlobal($this, 'Custom', new class() {
        });
    }

    public function test_allowed_factory_anon_global(): void
    {
        AbstractClassResolver::addAnonymousGlobal($this, 'Factory', new class() {
        });

        self::assertTrue(true); # Assert non error is thrown
    }

    public function test_allowed_config_anon_global(): void
    {
        AbstractClassResolver::addAnonymousGlobal($this, 'Config', new class() {
        });

        self::assertTrue(true); # Assert non error is thrown
    }

    public function test_allowed_dependency_provider_anon_global(): void
    {
        AbstractClassResolver::addAnonymousGlobal($this, 'DependencyProvider', new class() {
        });

        self::assertTrue(true); # Assert non error is thrown
    }
}
