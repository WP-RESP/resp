<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp;

class Communicator
{

    /**
     * @since 0.9.0
     */
    static function info($message)
    {
        self::notice($message);
    }

    /**
     * @since 0.9.0
     */
    static function warn($message)
    {
        self::notice($message, "warning");
    }

    /**
     * @since 0.9.0
     */
    static function critical($message)
    {
        self::notice($message, "error", false);
    }

    /**
     * @since 0.9.0
     */
    static function notice($message, $type = "info", $dismissible = true)
    {
        add_action('admin_notices', function () use ($message, $type, $dismissible) {
            $classes = ["notice", "notice-$type"];

            if ($dismissible) {
                $classes[] = "is-dismissible";
            }

            Tag::create()->addClass($classes)
                ->append(Tag::p($message))
                ->e();
        });
    }
}
