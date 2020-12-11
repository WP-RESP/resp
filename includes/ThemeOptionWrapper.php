<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\Tag;

defined('RESP_VERSION') or die;

class ThemeOptionWrapper
{

    static $actionPrefix = "resp-admin--wrapper-form";

    /**
     * @since 0.9.2
     */
    static function createSubMenuTab($icon, $title, $target, $echo = true)
    {

        $item = Tag::create([
            "name" => "li",
            "class" => "tab",
            "content" => sprintf(
                '<span class="ri-%1$s"></span><a href="#%2$s" >%3$s</a>',
                $icon,
                esc_attr($target),
                $title
            )
        ]);

        if ($echo) {
            $item->e();
        }
    }

    /**
     * @since 0.9.0
     */
    static function renderForm($title, $description, $callback = null, $attr = [])
    {
        $name = sanitize_title($title);

        $apx = self::$actionPrefix;

        if (!is_null($callback)) {
            add_action("{$apx}_{$name}", $callback);
        }

        Tag::create([
            "attr" =>  array_merge(["class" => "resp-card"], $attr)
        ])->eo();

        Tag::create(["class" => "card-header"])->append(apply_filters("{$apx}_{$name}_header-items", [
            Tag::h2(esc_html__($title, "resp"))
        ]))->e();

        Tag::p(esc_html__($description, "resp"))
            ->set(["class" => "card-description"])
            ->e();

        do_action("{$apx}_{$name}");

        Tag::close();
    }
}
