<?php
/**
 * Author: Jon Garcia.
 * Date: 4/13/16
 * Time: 9:44 PM
 */

namespace App\Core;


class EventReceiver
{

    /**
     * Holds registered events
     *
     * @var array
     */
    private static $events = [];

    /**
     * Called from provider to inform about event news
     *
     * @param $eventName
     * @param $eventValue
     * @return void
     */
    public static function sendEvent(string $eventName, $eventValue)
    {
        /** @var callable $event */
        foreach (self::$events[$eventName] as $callback) {

            if (! is_array($eventValue)) {
                $eventValue = [$eventValue];
            }
            call_user_func_array($callback, $eventValue);
        }
    }

    /**
     * Is someone listening to my events?
     *
     * @param $event
     * @return bool
     */
    public static function listeningTo(string $event): bool
    {
        if (isset(self::$events[$event])) {
            return true;
        }

        return false;
    }

    /**
     * Let's the provider know you're listening to it so they can send events.
     *
     * @param $eventName
     * @param callable $closure
     */
    public static function listenTo(string $eventName, callable $closure)
    {
        self::$events[$eventName][] = $closure;
    }
}