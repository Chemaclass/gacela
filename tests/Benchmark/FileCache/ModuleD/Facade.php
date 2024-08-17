<?php

declare(strict_types=1);

namespace GacelaTest\Benchmark\FileCache\ModuleD;

use Gacela\Framework\AbstractFacade;
use Gacela\Framework\DocBlockResolverAwareTrait;
use GacelaTest\Benchmark\FileCache\ModuleD\Infra\EntityManager;
use GacelaTest\Benchmark\FileCache\ModuleD\Infra\Repository;

/**
 * @method FactoryD getFactory()
 * @method Repository getRepository()
 * @method EntityManager getEntityManager()
 */
final class Facade extends AbstractFacade
{
    use DocBlockResolverAwareTrait;

    public function loadGacelaCacheFile(): array
    {
        return [
            $this->getFactory()->getArrayConfigAndProvidedDependency(),
            $this->getRepository()->getAll(),
            $this->getEntityManager()->updateEntity(),
        ];
    }
}
