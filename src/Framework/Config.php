<?php

declare(strict_types=1);

namespace Gacela\Framework;

use Gacela\Framework\Config\ConfigFactory;
use Gacela\Framework\Config\ConfigInit;
use Gacela\Framework\Config\ConfigReader\EnvConfigReader;
use Gacela\Framework\Config\ConfigReader\PhpConfigReader;
use Gacela\Framework\Config\ConfigReaderInterface;
use Gacela\Framework\Exception\ConfigException;

final class Config
{
    private static ?self $instance = null;

    private string $applicationRootDir = '';

    private array $config = [];

    /** @var array<string, ConfigReaderInterface> */
    private array $configReaders;

    /** @var array<string, mixed> */
    private array $globalServices = [];

    private ?ConfigFactory $configFactory = null;

    /**
     * @param array<string, ConfigReaderInterface> $configReaders
     */
    private function __construct(array $configReaders)
    {
        $this->configReaders = $configReaders;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self([
                'php' => new PhpConfigReader(),
                'env' => new EnvConfigReader(),
            ]);
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * @param array<string, ConfigReaderInterface> $configReaders
     */
    public static function setConfigReaders(array $configReaders = []): void
    {
        self::$instance = new self($configReaders);
    }

    /**
     * @param null|mixed $default
     *
     * @throws ConfigException
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (empty($this->config)) {
            $this->init($this->getApplicationRootDir());
        }

        if ($default !== null && !$this->hasValue($key)) {
            return $default;
        }

        if (!$this->hasValue($key)) {
            throw ConfigException::keyNotFound($key, self::class);
        }

        return $this->config[$key];
    }

    /**
     * @throws ConfigException
     */
    public function init(string $applicationRootDir): void
    {
        $this->setApplicationRootDir($applicationRootDir);

        $this->config = (new ConfigInit(
            $this->getApplicationRootDir(),
            $this->getFactory()->createGacelaConfigFileFactory(),
            $this->getFactory()->createPathFinder(),
            $this->configReaders
        ))->readAll();
    }

    public function setApplicationRootDir(string $dir): self
    {
        $this->applicationRootDir = $dir;

        return $this;
    }

    /**
     * @param array<string, mixed> $globalServices
     */
    public function addGlobalServices(array $globalServices): self
    {
        $this->globalServices = $globalServices;

        return $this;
    }

    public function getApplicationRootDir(): string
    {
        if (empty($this->applicationRootDir)) {
            $this->applicationRootDir = getcwd() ?: '';
        }

        return $this->applicationRootDir;
    }

    private function hasValue(string $key): bool
    {
        return isset($this->config[$key]);
    }

    private function getFactory(): ConfigFactory
    {
        if (null === $this->configFactory) {
            $this->configFactory = new ConfigFactory($this->globalServices);
        }

        return $this->configFactory;
    }
}
