<?php

declare(strict_types=1);

namespace GacelaTest\Integration\Framework\UsingGacelaConfig\LocalConfig\Domain;

use GacelaTest\Integration\Framework\UsingGacelaConfig\LocalConfig\Infrastructure\CustomCompanyGenerator;

final class NumberService
{
    private CustomCompanyGenerator $numberGenerator;

    public function __construct(CustomCompanyGenerator $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
    }

    public function generateCompanyAndName(): string
    {
        return $this->numberGenerator->company('Gacela');
    }
}
