<?php
/**
 * Author: Jon Garcia.
 * Date: 6/3/17
 * Time: 3:34 PM
 */

namespace App\Core\Console {

    Commands::do("print --times= message= --help", function (Argv $args) {
        /** $this Console */
        if ($args->get("help")) {
            $this->info("--times is the number of times to print message");
            $this->info("the message argument is just the string to print");
            $this->info("Try this `php framebro print --times 3 'hello world'`");
            return;
        }
        $times = $args->get("times");
        if (is_null($times)) {
            $times = 1;
        }
        for ($i = 0; $i < (int) $times; $i++) {
            $this->info($args->get("message"));
        }
    })->description("Does some stuff");
}