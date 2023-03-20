<?php

declare(strict_types=1);

namespace Gacela\Framework;

use Gacela\Framework\ClassResolver\DocBlockService\DocBlockServiceResolver;
use Gacela\Framework\DocBlockResolver\DocBlockResolver;

trait DocBlockResolverAwareTrait
{
    /** @var array<string,?mixed> */
    private array $customServices = [];

    /**
     * @psalm-suppress LessSpecificImplementedReturnType
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method = '', $parameters = [])
    {
        if (isset($this->customServices[$method])) {
            return $this->customServices[$method];
        }

        $docBlockResolver = DocBlockResolver::fromCaller($this);
        $resolvable = $docBlockResolver->getDocBlockResolvable($method);

        $this->customServices[$method] = (new DocBlockServiceResolver($resolvable->resolvableType()))
            ->resolve($resolvable->className());

        return $this->customServices[$method];
    }
}
