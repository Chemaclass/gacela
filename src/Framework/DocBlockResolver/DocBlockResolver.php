<?php

declare(strict_types=1);

namespace Gacela\Framework\DocBlockResolver;

use Gacela\Framework\ClassResolver\ClassNameCacheInterface;
use Gacela\Framework\ClassResolver\DocBlockService\CustomServicesJsonProfiler;
use Gacela\Framework\ClassResolver\DocBlockService\DocBlockParser;
use Gacela\Framework\ClassResolver\DocBlockService\MissingClassDefinitionException;
use Gacela\Framework\ClassResolver\DocBlockService\UseBlockParser;
use Gacela\Framework\ClassResolver\FileProfilerInterface;
use Gacela\Framework\ClassResolver\InMemoryClassNameCache;
use Gacela\Framework\ClassResolver\ProfiledInMemoryCache;
use Gacela\Framework\ClassResolver\Profiler\GacelaProfiler;
use Gacela\Framework\Config\Config;
use ReflectionClass;

use function get_class;
use function is_string;

final class DocBlockResolver
{
    private const INTERFACE_SUFFIX = 'Interface';

    /** @var array<string,string> [fileName => fileContent] */
    private static array $fileContentCache = [];

    /** @var class-string */
    private string $callerClass;

    /** @var class-string|string */
    private string $callerParentClass;

    /**
     * @param class-string $callerClass
     * @param class-string|string $callerParentClass
     */
    private function __construct(string $callerClass, string $callerParentClass)
    {
        $this->callerClass = $callerClass;
        $this->callerParentClass = $callerParentClass;
    }

    public static function fromCaller(object $caller): self
    {
        return new self(
            get_class($caller),
            get_parent_class($caller) ?: ''
        );
    }

    public function hasParentCallMethod(): bool
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->callerParentClass !== ''
            && method_exists($this->callerParentClass, '__call');
    }

    public function getDocBlockResolvable(string $method): DocBlockResolvable
    {
        $className = $this->getClassName($method);
        $resolvableType = $this->normalizeResolvableType($className);

        return new DocBlockResolvable($className, $resolvableType);
    }

    /**
     * @return class-string
     */
    private function getClassName(string $method): string
    {
        $cacheKey = $this->generateCacheKey($method);
        $cache = $this->createClassNameCache();

        if (!$cache->has($cacheKey)) {
            $className = $this->getClassFromDoc($method);
            $cache->put($cacheKey, $className);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        /** @var class-string $className */
        $className = $cache->get($cacheKey);

        return $className;
    }

    private function normalizeResolvableType(string $resolvableType): string
    {
        /** @var list<string> $resolvableTypeParts */
        $resolvableTypeParts = explode('\\', ltrim($resolvableType, '\\'));
        $normalizedResolvableType = end($resolvableTypeParts);

        return is_string($normalizedResolvableType)
            ? $normalizedResolvableType
            : $resolvableType;
    }

    private function generateCacheKey(string $method): string
    {
        return $this->callerClass . '::' . $method;
    }

    private function createClassNameCache(): ClassNameCacheInterface
    {
        $inMemoryCache = new InMemoryClassNameCache(CustomServicesJsonProfiler::class);

        if ($this->isProjectProfilerEnabled()) {
            return new ProfiledInMemoryCache(
                $inMemoryCache,
                $this->createProfiler()
            );
        }

        return $inMemoryCache;
    }

    private function isProjectProfilerEnabled(): bool
    {
        return (new GacelaProfiler(Config::getInstance()))->isEnabled();
    }

    /**
     * @return class-string
     */
    private function getClassFromDoc(string $method): string
    {
        $reflectionClass = new ReflectionClass($this->callerClass);

        $className = $this->searchClassOverDocBlock($reflectionClass, $method);
        if (class_exists($className)) {
            return $className;
        }

        $className = $this->searchClassOverUseStatements($reflectionClass, $className);
        if (class_exists($className)) {
            return $className;
        }

        if (($pos = strpos($className, self::INTERFACE_SUFFIX)) !== false) {
            $className = substr($className, 0, $pos);
            if (class_exists($className)) {
                return $className;
            }
        }

        throw MissingClassDefinitionException::missingDefinition($this->callerClass, $method, $className);
    }

    private function searchClassOverDocBlock(ReflectionClass $reflectionClass, string $method): string
    {
        $docBlock = (string)$reflectionClass->getDocComment();

        return (new DocBlockParser())->getClassFromMethod($docBlock, $method);
    }

    /**
     * Look the uses, to find the fully-qualified class name for the className.
     */
    private function searchClassOverUseStatements(ReflectionClass $reflectionClass, string $className): string
    {
        $fileName = (string)$reflectionClass->getFileName();
        if (!isset(self::$fileContentCache[$fileName])) {
            self::$fileContentCache[$fileName] = (string)file_get_contents($fileName);
        }
        $phpFile = self::$fileContentCache[$fileName];

        return (new UseBlockParser())->getUseStatement($className, $phpFile);
    }

    private function createProfiler(): FileProfilerInterface
    {
        return new CustomServicesJsonProfiler(
            Config::getInstance()->getProfilerDir(),
        );
    }
}
