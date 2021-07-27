<?php

declare(strict_types=1);

namespace Gacela\Framework\ClassResolver;

use Gacela\Framework\ClassResolver\ClassNameFinder\ClassNameFinderInterface;
use RuntimeException;
use function in_array;

abstract class AbstractClassResolver
{
    private const ALLOWED_TYPES_FOR_ANONYMOUS_GLOBAL = ['Config', 'Factory', 'DependencyProvider'];

    /** @var array<string,null|object> */
    protected static array $cachedInstances = [];

    /** @var array<string,object> */
    private static array $cachedGlobalInstances = [];

    protected static ?ClassNameFinderInterface $classNameFinder = null;

    abstract public function resolve(object $callerClass): ?object;

    abstract protected function getResolvableType(): string;

    /**
     * @param object|string $context
     */
    public static function addAnonymousGlobal($context, string $type, object $resolvedClass): void
    {
        self::validateTypeForAnonymousGlobalRegistration($type);

        if (is_object($context)) {
            $callerClass = get_class($context);
            /** @var string[] $callerClassParts */
            $callerClassParts = explode('\\', ltrim($callerClass, '\\'));
            $contextName = end($callerClassParts);
        } else {
            $contextName = $context;
        }

        self::addGlobal(
            sprintf('\%s\%s\%s', ClassInfo::MODULE_NAME_ANONYMOUS, $contextName, $type),
            $resolvedClass
        );
    }

    public static function overrideExistingResolvedClass(string $className, object $resolvedClass): void
    {
        $key = self::getGlobalKeyFromClassName($className);

        self::addGlobal($key, $resolvedClass);
    }

    /**
     * @internal so the Locator can access to the global instances before creating a new instance
     */
    public static function getGlobalInstance(string $className): ?object
    {
        $key = self::getGlobalKeyFromClassName($className);

        return self::$cachedGlobalInstances[$key]
            ?? self::$cachedGlobalInstances['\\' . $key]
            ?? null;
    }

    private static function getGlobalKeyFromClassName(string $className): string
    {
        preg_match('~(?<pre_namespace>.*)\\\((?:^|[A-Z])[a-z]+)(?<resolvable_type>.*)~', $className, $matches);
        $resolvableType = $matches['resolvable_type'] ?? '';

        return (empty($resolvableType) || $resolvableType === 'Provider')
            ? $className
            : sprintf('\\%s\\%s', ltrim($matches['pre_namespace'], '\\'), $resolvableType);
    }

    private static function validateTypeForAnonymousGlobalRegistration(string $type): void
    {
        if (!in_array($type, self::ALLOWED_TYPES_FOR_ANONYMOUS_GLOBAL)) {
            throw new RuntimeException(
                "Type '$type' not allowed. Valid types: " . implode(', ', self::ALLOWED_TYPES_FOR_ANONYMOUS_GLOBAL)
            );
        }
    }

    private static function addGlobal(string $key, object $resolvedClass): void
    {
        self::$cachedGlobalInstances[$key] = $resolvedClass;
    }

    public function doResolve(object $callerClass): ?object
    {
        $classInfo = new ClassInfo($callerClass);
        $cacheKey = $this->getCacheKey($classInfo);
        if (isset(self::$cachedInstances[$cacheKey])) {
            return self::$cachedInstances[$cacheKey];
        }

        $resolvedClass = $this->resolveGlobal($cacheKey);
        if (null !== $resolvedClass) {
            return $resolvedClass;
        }

        $resolvedClassName = $this->findClassName($classInfo);
        if (null === $resolvedClassName) {
            return null;
        }

        self::$cachedInstances[$cacheKey] = $this->createInstance($resolvedClassName);

        return self::$cachedInstances[$cacheKey];
    }

    private function resolveGlobal(string $cacheKey): ?object
    {
        $resolvedClass = self::$cachedGlobalInstances[$cacheKey] ?? null;

        if (null === $resolvedClass) {
            return null;
        }

        self::$cachedInstances[$cacheKey] = $resolvedClass;

        return self::$cachedInstances[$cacheKey];
    }

    private function getCacheKey(ClassInfo $classInfo): string
    {
        return $classInfo->getCacheKey($this->getResolvableType());
    }

    private function findClassName(ClassInfo $classInfo): ?string
    {
        return $this->getClassNameFinder()->findClassName(
            $classInfo,
            $this->getResolvableType()
        );
    }

    private function getClassNameFinder(): ClassNameFinderInterface
    {
        if (null === self::$classNameFinder) {
            self::$classNameFinder = (new ClassResolverFactory())->createClassNameFinder();
        }

        return self::$classNameFinder;
    }

    private function createInstance(string $resolvedClassName): ?object
    {
        if (class_exists($resolvedClassName)) {
            /** @psalm-suppress MixedMethodCall */
            return new $resolvedClassName();
        }

        return null;
    }
}
