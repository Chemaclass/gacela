<?php

declare(strict_types=1);

namespace Gacela\Framework\Bootstrap;

use Closure;
use Gacela\Framework\ClassResolver\Cache\GacelaFileCache;
use Gacela\Framework\Config\GacelaConfigBuilder\ConfigBuilder;
use Gacela\Framework\Config\GacelaConfigBuilder\MappingInterfacesBuilder;
use Gacela\Framework\Config\GacelaConfigBuilder\SuffixTypesBuilder;
use Gacela\Framework\Event\Dispatcher\ConfigurableEventDispatcher;
use Gacela\Framework\Event\Dispatcher\EventDispatcherInterface;
use Gacela\Framework\Event\Dispatcher\NullEventDispatcher;
use RuntimeException;

use function is_callable;

final class SetupGacela extends AbstractSetupGacela
{
    private const DEFAULT_ARE_EVENT_LISTENERS_ENABLED = true;
    private const DEFAULT_SHOULD_RESET_IN_MEMORY_CACHE = false;
    private const DEFAULT_FILE_CACHE_ENABLED = GacelaFileCache::DEFAULT_ENABLED_VALUE;
    private const DEFAULT_FILE_CACHE_DIRECTORY = GacelaFileCache::DEFAULT_DIRECTORY_VALUE;
    private const DEFAULT_PROJECT_NAMESPACES = [];
    private const DEFAULT_CONFIG_KEY_VALUES = [];
    private const DEFAULT_GENERIC_LISTENERS = [];
    private const DEFAULT_SPECIFIC_LISTENERS = [];
    private const DEFAULT_SERVICES_TO_EXTEND = [];

    /** @var callable(ConfigBuilder):void */
    private $configFn;

    /** @var callable(MappingInterfacesBuilder,array<string,mixed>):void */
    private $mappingInterfacesFn;

    /** @var callable(SuffixTypesBuilder):void */
    private $suffixTypesFn;

    /** @var ?array<string,class-string|object|callable> */
    private ?array $externalServices = null;

    private ?ConfigBuilder $configBuilder = null;

    private ?SuffixTypesBuilder $suffixTypesBuilder = null;

    private ?MappingInterfacesBuilder $mappingInterfacesBuilder = null;

    private ?bool $shouldResetInMemoryCache = null;

    private ?bool $fileCacheEnabled = null;

    private ?string $fileCacheDirectory = null;

    /** @var ?list<string> */
    private ?array $projectNamespaces = null;

    /** @var ?array<string,mixed> */
    private ?array $configKeyValues = null;

    private ?bool $areEventListenersEnabled = null;

    /** @var ?list<callable> */
    private ?array $genericListeners = null;

    /** @var ?array<class-string,list<callable>> */
    private ?array $specificListeners = null;

    private ?EventDispatcherInterface $eventDispatcher = null;

    /** @var ?array<string,bool> */
    private ?array $changedProperties = null;

    /** @var ?array<string,list<Closure>> */
    private ?array $servicesToExtend = null;

    public function __construct()
    {
        $this->configFn = static function (): void {
        };
        $this->mappingInterfacesFn = static function (): void {
        };
        $this->suffixTypesFn = static function (): void {
        };
    }

    public static function fromFile(string $gacelaFilePath): self
    {
        if (!is_file($gacelaFilePath)) {
            throw new RuntimeException("Invalid file path: '{$gacelaFilePath}'");
        }

        /** @var callable(GacelaConfig):void|null $setupGacelaFileFn */
        $setupGacelaFileFn = include $gacelaFilePath;
        if (!is_callable($setupGacelaFileFn)) {
            return new self();
        }

        return self::fromCallable($setupGacelaFileFn);
    }

    /**
     * @param callable(GacelaConfig):void $setupGacelaFileFn
     */
    public static function fromCallable(callable $setupGacelaFileFn): self
    {
        $gacelaConfig = new GacelaConfig();
        $setupGacelaFileFn($gacelaConfig);

        return self::fromGacelaConfig($gacelaConfig);
    }

    public static function fromGacelaConfig(GacelaConfig $gacelaConfig): self
    {
        $build = $gacelaConfig->build();

        return (new self())
            ->setExternalServices($build['external-services'])
            ->setConfigBuilder($build['config-builder'])
            ->setSuffixTypesBuilder($build['suffix-types-builder'])
            ->setMappingInterfacesBuilder($build['mapping-interfaces-builder'])
            ->setShouldResetInMemoryCache($build['should-reset-in-memory-cache'])
            ->setFileCacheEnabled($build['file-cache-enabled'])
            ->setFileCacheDirectory($build['file-cache-directory'])
            ->setProjectNamespaces($build['project-namespaces'])
            ->setConfigKeyValues($build['config-key-values'])
            ->setAreEventListenersEnabled($build['are-event-listeners-enabled'])
            ->setGenericListeners($build['generic-listeners'])
            ->setSpecificListeners($build['specific-listeners'])
            ->setServicesToExtend($build['services-to-extend']);
    }

    public function setMappingInterfacesBuilder(MappingInterfacesBuilder $builder): self
    {
        $this->markPropertyChanged('mappingInterfacesBuilder', true);
        $this->mappingInterfacesBuilder = $builder;

        return $this;
    }

    public function setSuffixTypesBuilder(SuffixTypesBuilder $builder): self
    {
        $this->markPropertyChanged('suffixTypesBuilder', true);
        $this->suffixTypesBuilder = $builder;

        return $this;
    }

    public function setConfigBuilder(ConfigBuilder $builder): self
    {
        $this->markPropertyChanged('configBuilder', true);
        $this->configBuilder = $builder;

        return $this;
    }

    /**
     * @param callable(ConfigBuilder):void $callable
     */
    public function setConfigFn(callable $callable): self
    {
        $this->markPropertyChanged('configFn', true);
        $this->configFn = $callable;

        return $this;
    }

    public function buildConfig(ConfigBuilder $builder): ConfigBuilder
    {
        if ($this->configBuilder) {
            $builder = $this->configBuilder;
        }

        ($this->configFn)($builder);

        return $builder;
    }

    /**
     * @param callable(MappingInterfacesBuilder,array<string,mixed>):void $callable
     */
    public function setMappingInterfacesFn(callable $callable): self
    {
        $this->markPropertyChanged('mappingInterfacesFn', true);
        $this->mappingInterfacesFn = $callable;

        return $this;
    }

    /**
     * Define the mapping between interfaces and concretions, so Gacela services will auto-resolve them automatically.
     *
     * @param array<string,class-string|object|callable> $externalServices
     */
    public function buildMappingInterfaces(
        MappingInterfacesBuilder $builder,
        array $externalServices,
    ): MappingInterfacesBuilder {
        if ($this->mappingInterfacesBuilder) {
            $builder = $this->mappingInterfacesBuilder;
        }

        ($this->mappingInterfacesFn)(
            $builder,
            array_merge($this->externalServices ?? [], $externalServices)
        );

        return $builder;
    }

    /**
     * @param callable(SuffixTypesBuilder):void $callable
     */
    public function setSuffixTypesFn(callable $callable): self
    {
        $this->markPropertyChanged('suffixTypesFn', true);
        $this->suffixTypesFn = $callable;

        return $this;
    }

    /**
     * Allow overriding gacela resolvable types.
     */
    public function buildSuffixTypes(SuffixTypesBuilder $builder): SuffixTypesBuilder
    {
        if ($this->suffixTypesBuilder) {
            $builder = $this->suffixTypesBuilder;
        }

        ($this->suffixTypesFn)($builder);

        return $builder;
    }

    /**
     * @param array<string,class-string|object|callable> $array
     */
    public function setExternalServices(array $array): self
    {
        $this->markPropertyChanged('externalServices', true);
        $this->externalServices = $array;

        return $this;
    }

    /**
     * @return array<string,class-string|object|callable>
     */
    public function externalServices(): array
    {
        return (array)$this->externalServices;
    }

    public function setShouldResetInMemoryCache(?bool $flag): self
    {
        $this->markPropertyChanged('shouldResetInMemoryCache', $flag);
        $this->shouldResetInMemoryCache = $flag ?? self::DEFAULT_SHOULD_RESET_IN_MEMORY_CACHE;

        return $this;
    }

    public function shouldResetInMemoryCache(): bool
    {
        return (bool)$this->shouldResetInMemoryCache;
    }

    public function isFileCacheEnabled(): bool
    {
        return (bool)$this->fileCacheEnabled;
    }

    public function getFileCacheDirectory(): string
    {
        return (string)$this->fileCacheDirectory;
    }

    public function setFileCacheDirectory(?string $dir): self
    {
        $this->markPropertyChanged('fileCacheDirectory', $dir);
        $this->fileCacheDirectory = $dir ?? self::DEFAULT_FILE_CACHE_DIRECTORY;

        return $this;
    }

    /**
     * @param ?list<string> $list
     */
    public function setProjectNamespaces(?array $list): self
    {
        $this->markPropertyChanged('projectNamespaces', $list);
        $this->projectNamespaces = $list ?? self::DEFAULT_PROJECT_NAMESPACES;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getProjectNamespaces(): array
    {
        return (array)$this->projectNamespaces;
    }

    /**
     * @return array<string,mixed>
     */
    public function getConfigKeyValues(): array
    {
        return (array)$this->configKeyValues;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher !== null) {
            return $this->eventDispatcher;
        }

        if ($this->canCreateEventDispatcher()) {
            $this->eventDispatcher = new ConfigurableEventDispatcher();
            $this->eventDispatcher->registerGenericListeners($this->genericListeners ?? []);

            foreach ($this->specificListeners ?? [] as $event => $listeners) {
                foreach ($listeners as $callable) {
                    $this->eventDispatcher->registerSpecificListener($event, $callable);
                }
            }
        } else {
            $this->eventDispatcher = new NullEventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function combine(self $other): self
    {
        $this->overrideResetInMemoryCache($other);
        $this->overrideFileCacheSettings($other);

        $this->combineExternalServices($other);
        $this->combineProjectNamespaces($other);
        $this->combineConfigKeyValues($other);
        $this->combineEventDispatcher($other);
        $this->combineServicesToExtend($other);

        return $this;
    }

    /**
     * @return array<string,list<Closure>>
     */
    public function getServicesToExtend(): array
    {
        return (array)$this->servicesToExtend;
    }

    private function setFileCacheEnabled(?bool $flag): self
    {
        $this->markPropertyChanged('fileCacheEnabled', $flag);
        $this->fileCacheEnabled = $flag ?? self::DEFAULT_FILE_CACHE_ENABLED;

        return $this;
    }

    private function setAreEventListenersEnabled(?bool $flag): self
    {
        $this->markPropertyChanged('areEventListenersEnabled', $flag);
        $this->areEventListenersEnabled = $flag ?? self::DEFAULT_ARE_EVENT_LISTENERS_ENABLED;

        return $this;
    }

    private function combineExternalServices(self $other): void
    {
        if ($other->isPropertyChanged('externalServices')) {
            $this->externalServices = array_merge($this->externalServices ?? [], $other->externalServices());
        }
    }

    private function overrideResetInMemoryCache(self $other): void
    {
        if ($other->isPropertyChanged('shouldResetInMemoryCache')) {
            $this->shouldResetInMemoryCache = $other->shouldResetInMemoryCache();
        }
    }

    private function overrideFileCacheSettings(self $other): void
    {
        if ($other->isPropertyChanged('fileCacheEnabled')) {
            $this->fileCacheEnabled = $other->isFileCacheEnabled();
        }
        if ($other->isPropertyChanged('fileCacheDirectory')) {
            $this->fileCacheDirectory = $other->getFileCacheDirectory();
        }
    }

    private function combineProjectNamespaces(self $other): void
    {
        if ($other->isPropertyChanged('projectNamespaces')) {
            $this->projectNamespaces = array_merge($this->projectNamespaces ?? [], $other->getProjectNamespaces());
        }
    }

    private function combineConfigKeyValues(self $other): void
    {
        if ($other->isPropertyChanged('configKeyValues')) {
            $this->configKeyValues = array_merge($this->configKeyValues ?? [], $other->getConfigKeyValues());
        }
    }

    private function combineEventDispatcher(self $other): void
    {
        if ($other->canCreateEventDispatcher()) {
            if (!($this->eventDispatcher instanceof ConfigurableEventDispatcher)) {
                $this->eventDispatcher = new ConfigurableEventDispatcher();
            }
            $this->eventDispatcher->registerGenericListeners((array)$other->genericListeners);

            foreach ($other->specificListeners ?? [] as $event => $listeners) {
                foreach ($listeners as $callable) {
                    $this->eventDispatcher->registerSpecificListener($event, $callable);
                }
            }
        } else {
            $this->eventDispatcher = new NullEventDispatcher();
        }
    }

    private function combineServicesToExtend(self $other): void
    {
        if ($other->isPropertyChanged('servicesToExtend')) {
            foreach ($other->getServicesToExtend() as $serviceId => $otherServiceToExtend) {
                $this->servicesToExtend[$serviceId] ??= [];
                $this->servicesToExtend[$serviceId] = array_merge(
                    $this->servicesToExtend[$serviceId],
                    $otherServiceToExtend,
                );
            }
        }
    }

    private function canCreateEventDispatcher(): bool
    {
        return $this->areEventListenersEnabled
            && $this->hasEventListeners();
    }

    private function hasEventListeners(): bool
    {
        return !empty($this->genericListeners)
            || !empty($this->specificListeners);
    }

    /**
     * @param ?array<string,mixed> $configKeyValues
     */
    private function setConfigKeyValues(?array $configKeyValues): self
    {
        $this->markPropertyChanged('configKeyValues', $configKeyValues);
        $this->configKeyValues = $configKeyValues ?? self::DEFAULT_CONFIG_KEY_VALUES;

        return $this;
    }

    /**
     * @param ?list<callable> $listeners
     */
    private function setGenericListeners(?array $listeners): self
    {
        $this->markPropertyChanged('genericListeners', $listeners);
        $this->genericListeners = $listeners ?? self::DEFAULT_GENERIC_LISTENERS;

        return $this;
    }

    /**
     * @param ?array<string,list<Closure>> $list
     */
    private function setServicesToExtend(?array $list): self
    {
        $this->markPropertyChanged('servicesToExtend', $list);
        $this->servicesToExtend = $list ?? self::DEFAULT_SERVICES_TO_EXTEND;

        return $this;
    }

    /**
     * @param ?array<class-string,list<callable>> $listeners
     */
    private function setSpecificListeners(?array $listeners): self
    {
        $this->markPropertyChanged('specificListeners', $listeners);
        $this->specificListeners = $listeners ?? self::DEFAULT_SPECIFIC_LISTENERS;

        return $this;
    }

    private function markPropertyChanged(string $name, mixed $value): void
    {
        $this->changedProperties[$name] = ($value !== null);
    }

    private function isPropertyChanged(string $name): bool
    {
        return $this->changedProperties[$name] ?? false;
    }
}
