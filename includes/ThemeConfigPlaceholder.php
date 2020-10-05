<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\ThemeBuilder, \Resp\Tag, \Resp\ThemeOptionWrapper as tow , \Resp\ThemeOptions;

defined('RESP_VERSION') or die;

class ThemeConfigPlaceholder
{

    /**
     * @since 0.9.0
     */
    static function registerForm($title, $description, $hidden = false, $action = null, $wrapper = true)
    {

        $class = "\Resp\ThemeConfigPlaceholder";

        $callback = $hidden ? "$class::hiddenPlaceholderHandler" : "$class::placeholderHandler";

        if (is_null($action)) {
            if ($wrapper) {
                tow::renderForm($title, $description,  $callback);
            } else {
                call_user_func($callback);
            }
        } else {
            add_action($action, function () use ($title, $description,  $callback, $wrapper) {
                if ($wrapper) {
                    tow::renderForm($title, $description, $callback);
                } else {
                    call_user_func($callback);
                }
            });
        }
    }

    /**
     * @since 0.9.0
     */
    static function hiddenPlaceholderHandler()
    {
        $currentTab =  ThemeOptions::getCurrentTab();

        do_action( "resp-admin--before-config-placeholder_{$currentTab}" );

        self::configPlaceholder()->addClass("resp-hidden")->e();
    }

    /**
     * @since 0.9.0
     */
    static function placeholderHandler()
    {
        $currentTab =  ThemeOptions::getCurrentTab();

        do_action( "resp-admin--before-config-placeholder_{$currentTab}" );

        self::configPlaceholder()->e();
    }


    /**
     * @since 0.9.0
     */
    static function configPlaceholder()
    {

        $data = ThemeBuilder::retrieveJsonData(true);

        $placeholder = Tag::textareaFor("resp_theme_data", esc_textarea($data), ["rows" => "15"])
            ->addClass("large-text code");

        return $placeholder;
    }
}
