<?php

declare(strict_types=1);

namespace Gacela\Framework\Event\Dispatcher;

use function get_class;

final class ConfigurableEventDispatcher implements EventDispatcherInterface
{
    /** @var array<callable> */
    private array $genericListeners = [];

    /** @var array<class-string,list<callable>> */
    private array $specificListeners = [];

    public function __construct()
    {
    }

    /**
     * @param list<callable> $genericListeners
     */
    public function registerGenericListeners(array $genericListeners): void
    {
        $this->genericListeners = $genericListeners;
    }

    /**
     * @param class-string $event
     */
    public function registerSpecificListener(string $event, callable $listener): void
    {
        $this->specificListeners[$event][] = $listener;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->genericListeners as $listener) {
            $this->notifyListener($listener, $event);
        }

        foreach ($this->specificListeners[get_class($event)] ?? [] as $listener) {
            $this->notifyListener($listener, $event);
        }
    }

    private function notifyListener(callable $listener, object $event): void
    {
        $listener($event);
    }
}
