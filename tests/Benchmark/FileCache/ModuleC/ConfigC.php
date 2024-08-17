<?php

declare(strict_types=1);

namespace GacelaTest\Benchmark\FileCache\ModuleC;

use Gacela\Framework\AbstractConfig;

final class ConfigC extends AbstractConfig
{
    public function getConfigValue(): string
    {
        return $this->get('config-key');
    }
}
