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
     * @return mixed
     */
    public static function sendEvent($eventName, $eventValue)
    {
        if (!is_array($eventValue)) {
            $eventValue = [$eventValue];
        }

        $callback = self::$events[$eventName];
        return call_user_func_array($callback, $eventValue);
    }

    /**
     * Is someone listening to my events?
     *
     * @param $event
     * @return bool
     */
    public static function listeningTo($event)
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
     * @return mixed
     * @throws \Exception
     */
    public static function listenTo($eventName, callable $closure)
    {
        self::$events[$eventName] = $closure;
    }
}