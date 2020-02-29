<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use  Resp\Component, Resp\Core, Resp\Tag, Resp\ThemeBuilder;

defined("RESP_TEXT_DOMAIN") or die;

class Sidebar extends Component
{

    const SIDEBARS_DEF_NAME = "sidebars";

    private static $sidebars = [];




    function __construct()
    {

        add_shortcode('resp-sidebar', [$this, 'sidebarShortcode']);

        add_action("resp-themebuilder-build", [$this, 'extractSidebars'], 10);

        add_action('after_setup_theme', [$this, 'registerCustomSidebars'], 20);
    }


    /**
     * @since 0.9.0
     */
    function registerCustomSidebars()
    {


        register_sidebar([
            'id' => 'master-header',
            'name' => __('Master header', RESP_TEXT_DOMAIN),
            'description' => __('This section appears on the top of all pages.', RESP_TEXT_DOMAIN),
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3 class="resp-sidebar-title-master-header">',
            'after_title' => '</h3>'
        ]);


        register_sidebar(array(
            'id' => 'master-footer',
            'name' => __('Master footer', RESP_TEXT_DOMAIN),
            'description' => __('This section appears on the bottom of all pages.', RESP_TEXT_DOMAIN),
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3 class="resp-sidebar-title-master-footer">',
            'after_title' => '</h3>'
        ));

        if (empty(self::$sidebars)) {
            return;
        }

        foreach (self::$sidebars as $key => $value) {

            $name = isset($value["name"]) ? $value["name"] : $key;

            $args = [
                'name' => $name,
                'id' => $key
            ];

            foreach ([
                "description", "before_title", "after_title",
                "before_widget", "after_widget", "class"
            ] as $param) {
                if (isset($value[$param])) {
                    $args[$param] = $value[$param];
                }
            }

            register_sidebar($args);
        }
    }


    /**
     * @since 0.9.0
     */
    function extractSidebars()
    {

        $data = array_merge_recursive(
             ThemeBuilder::getDefinitions(self::SIDEBARS_DEF_NAME),
             ThemeBuilder::getStatics(self::SIDEBARS_DEF_NAME)
        );

        foreach ($data as $key => $value) {


            self::$sidebars[$key] = $value;

            $container = __resp_array_item($value, "container", "aside");

            $list = __resp_array_item($value, "list", false);

            $name = __resp_array_item($value, "name", $key);

            $role = __resp_array_item($value, "role", sanitize_title($name));


            if (isset($value["action"])) {
                add_action($value["action"], function () use ($key, $container, $role, $list) {

                    if (is_active_sidebar($key)) {

                        Core::trigger("{$role}-before-container", true);

                        Core::tag($container, "{$role}-container", "")->eo();

                        if ($list) {

                            Core::trigger("{$role}-before-content", true);

                            Core::tag("ul", "{$role}-content", '')->eo();
                        }

                        dynamic_sidebar($key);

                        if ($list) {

                            Tag::close("ul");

                            Core::trigger("{$role}-after-content", true);
                        }

                        Tag::close($container);

                        Core::trigger("{$role}-after-container", true);
                    }
                });
            }
        }
    }



    /**
     * @since 0.9.0
     */
    function sidebarShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            'name' => '',
            'blog' => get_current_blog_id()
        ], $atts));

        if (empty($name)) {
            return;
        }

        switch_to_blog($blog);

        ob_start();

        dynamic_sidebar($name);

        $sidebar = ob_get_contents();

        ob_end_clean();

        restore_current_blog();

        return $sidebar;
    }
}