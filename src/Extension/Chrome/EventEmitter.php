<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

class EventEmitter
{
    /** @var \Closure[][] */
    private $closures = [];

    public function emit(string $eventName, $data)
    {
        if (!array_key_exists($eventName, $this->closures)) {
            return;
        }

        foreach ($this->closures[$eventName] as $closure) {
            $closure($data);
        }
    }

    public function on(string $eventName, \Closure $callback): string
    {
        if (!array_key_exists($eventName, $this->closures)) {
            $this->closures[$eventName] = [];
        }

        $id = spl_object_hash($callback);

        $this->closures[$eventName][$id] = $callback;

        return $id;
    }

    public function onOneTime(string $eventName, \Closure $callback)
    {
        $eventId = $this->on($eventName, function ($event) use ($callback, &$eventId) {
            if ($callback($event)) {
                $this->remove($eventId);
            }
        });
    }

    public function remove($event)
    {
        if ($event instanceof \Closure) {
            $event = spl_object_hash($event);
        }

        foreach ($this->closures as $eventName => $closures) {
            if (array_key_exists($event, $closures)) {
                unset($this->closures[$eventName][$event]);
            }
        }
    }
}
