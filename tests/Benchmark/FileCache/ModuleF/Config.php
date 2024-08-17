<?php

declare(strict_types=1);

namespace GacelaTest\Benchmark\FileCache\ModuleF;

use Gacela\Framework\AbstractConfig;

final class Config extends AbstractConfig
{
    public function getConfigValue(): string
    {
        return $this->get('config-key');
    }
}
