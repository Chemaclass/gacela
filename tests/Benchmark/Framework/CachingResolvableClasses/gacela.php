<?php

declare(strict_types=1);

namespace GacelaTest\Benchmark\Framework\CachingResolvableClasses;

use Gacela\Framework\AbstractConfigGacela;
use Gacela\Framework\Config\GacelaConfigBuilder\SuffixTypesBuilder;

return static fn () => new class() extends AbstractConfigGacela {
    public function suffixTypes(SuffixTypesBuilder $suffixTypesBuilder): void
    {
        $suffixTypesBuilder
            ->addFactory('FactoryModuleA')
            ->addConfig('ConfModuleA')
            ->addDependencyProvider('DepProModuleA');
    }
};
