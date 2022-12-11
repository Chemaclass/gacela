<?php

declare(strict_types=1);

namespace Gacela\Framework\Config;

use Gacela\Framework\Bootstrap\SetupGacelaInterface;
use Gacela\Framework\Event\Dispatcher\EventDispatcherInterface;
use Gacela\Framework\Exception\ConfigException;
use RuntimeException;

use function array_key_exists;

final class Config implements ConfigInterface
{
    private static ?self $instance = null;

    private static ?EventDispatcherInterface $eventDispatcher = null;

    private ?ConfigFactory $configFactory = null;

    private ?string $appRootDir = null;

    /** @var array<string,mixed> */
    private array $config = [];

    private function __construct(
        private SetupGacelaInterface $setup,
    ) {
    }

    public static function createWithSetup(SetupGacelaInterface $setup): self
    {
        self::$instance = new self($setup);

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('You have to call createWithSetup() first.');
        }

        return self::$instance;
    }

    /**
     * @internal
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
        self::$eventDispatcher = null;
    }

    public static function getEventDispatcher(): EventDispatcherInterface
    {
        if (self::$eventDispatcher === null) {
            self::$eventDispatcher = self::getInstance()
                ->getSetupGacela()
                ->getEventDispatcher();
        }

        return self::$eventDispatcher;
    }

    /**
     * @throws ConfigException
     */
    public function get(string $key, mixed $default = self::DEFAULT_CONFIG_VALUE): mixed
    {
        if (empty($this->config)) {
            $this->init();
        }

        if ($default !== self::DEFAULT_CONFIG_VALUE && !$this->hasKey($key)) {
            return $default;
        }

        if (!$this->hasKey($key)) {
            throw ConfigException::keyNotFound($key, self::class);
        }

        return $this->config[$key];
    }

    /**
     * Force loading all config values in memory.
     *
     * @throws ConfigException
     */
    public function init(): void
    {
        $this->configFactory = null;
        $this->config = $this->loadAllConfigValues();
        $this->config = array_merge($this->config, $this->getSetupGacela()->getConfigKeyValues());
    }

    public function setAppRootDir(string $dir): self
    {
        $this->appRootDir = rtrim($dir, DIRECTORY_SEPARATOR);

        if (empty($this->appRootDir)) {
            $this->appRootDir = getcwd() ?: ''; // @codeCoverageIgnore
        }

        return $this;
    }

    public function getAppRootDir(): string
    {
        return $this->appRootDir ?? getcwd() ?: '';
    }

    public function getCacheDir(): string
    {
        return $this->getAppRootDir()
            . DIRECTORY_SEPARATOR
            . ltrim($this->getSetupGacela()->getFileCacheDirectory(), DIRECTORY_SEPARATOR);
    }

    /**
     * @internal
     */
    public function getFactory(): ConfigFactory
    {
        if ($this->configFactory === null) {
            $this->configFactory = new ConfigFactory(
                $this->getAppRootDir(),
                $this->getSetupGacela(),
            );
        }

        return $this->configFactory;
    }

    public function getSetupGacela(): SetupGacelaInterface
    {
        return $this->setup;
    }

    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * @return array<string,mixed>
     */
    private function loadAllConfigValues(): array
    {
        return $this->getFactory()
            ->createConfigLoader()
            ->loadAll();
    }
}
