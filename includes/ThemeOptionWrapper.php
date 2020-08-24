<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp;

use \Resp\Tag;

class ThemeOptionWrapper
{

    static $actionPrefix = "resp-admin--wrapper-form";


    /**
     * @since 0.9.0
     */
    static function renderForm($title, $description , $callback = null)
    {

        $name = sanitize_title($title);

        $apx = self::$actionPrefix;

        if(!is_null($callback)){
            add_action("{$apx}_{$name}", $callback);
        }

        Tag::create(["class" => "resp-notice-wrapper"])
            ->eo();

        Tag::h2(__($title , RESP_TEXT_DOMAIN))
            ->e();

        Tag::p(__($description , RESP_TEXT_DOMAIN))
            ->set(["class" => "desc"])
            ->e();

        do_action("{$apx}_{$name}");

        Tag::close();
    }

}