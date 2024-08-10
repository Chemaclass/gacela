<?php

declare(strict_types=1);

namespace Gacela\Framework\Event\ConfigReader;

use Gacela\Framework\Event\GacelaEventInterface;

use function get_class;
use function sprintf;

final class ReadPhpConfigEvent implements GacelaEventInterface
{
    public function __construct(
        private string $absolutePath,
    ) {
    }

    public function absolutePath(): string
    {
        return $this->absolutePath;
    }

    public function toString(): string
    {
        return sprintf(
            '%s {absolutePath:"%s"}',
            get_class($this),
            $this->absolutePath,
        );
    }
}
