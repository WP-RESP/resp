<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder, Resp\Tag;
use Resp\RespWalkerNavMenu;

defined('RESP_VERSION') or die;

class ListMenu extends Component
{

    const MENUS_DEF_NAME = "menus";

    private static $menus = [];

    private static $currentItem;

    function __construct()
    {
        add_shortcode('resp-listmenu', [$this, 'listmenuShortcode']);
        add_shortcode('resp-menu-items', [$this, 'menuItemsShortcode']);
        add_shortcode('resp-menu-item', [$this, 'itemShortcode']);
        add_action("resp-themebuilder-build", [$this, 'extractMenus'], 10);
        add_action('after_setup_theme', [$this, 'registerCustomNavMenus'], 20);
    }

    /**
     * @since 0.9.0
     */
    function extractMenus()
    {
        $data = ThemeBuilder::getDefinitions(self::MENUS_DEF_NAME);

        foreach ($data as $key => $value) {
            self::$menus[$key] = $value;
        }
    }


    /**
     * @since 0.9.0
     */
    function registerCustomNavMenus()
    {
        if (empty(self::$menus)) {
            return;
        }

        $menus = self::$menus;

        array_walk($menus, function(&$item , $key){

            if(is_array($item)){
                $item = esc_html__($item["description"] , "resp") ?? $key;
            }

        });

        register_nav_menus($menus);
    }


    /**
     * @since 0.9.0
     */
    function itemShortcode($atts = [], $content = null)
    {
        $names = array_values($atts);

        if (empty($names)) {
            return;
        }

        return self::getCurrentItemParam($names[0]);
    }


    /**
     * @since 0.9.0
     */
    private static function getCurrentItemParam($param)
    {
        if (!isset(self::$currentItem)) {
            return "";
        }
        switch ($param) {
            case "url":
                return self::$currentItem->url;
            case "slug":
                return sanitize_title(self::$currentItem->title);
            case "title":
                return self::$currentItem->title;
            default:
                return "";
        }
    }


    /**
     * @since 0.9.0
     */
    private static function getMenuStructure($context, $items, $parent = null, $level = 1, $item = null)
    {

        $result = [];


        if (empty($items)) {
            return [];
        }


        if ($item == null) {
            $item = $items[0];
        }


        $neighbours = array_values(array_filter($items, function ($element) use ($item) {
            return $element->menu_item_parent == $item->menu_item_parent;
        }));


        for ($i = 0; $i < sizeof($neighbours); $i++) {

            $menu = $neighbours[$i];

            $children = array_values(array_filter($items, function ($element) use ($menu) {
                return $element->menu_item_parent == $menu->ID;
            }));

            $result[] = [
                "context" => $context,
                "menu" => $menu,
                "parent" => $parent,
                "children" => (empty($children) ? [] :  self::getMenuStructure($context, $items, $menu, $level + 1, $children[0])),
                "lastItem" => !isset($neighbours[$i + 1]),
                "level" => $level
            ];
        }

        return $result;
    }


    /**
     * @since 0.9.0
     */
    private static function renderMenuItem($menu, $content, $ignore_html)
    {
        self::$currentItem = $menu["menu"];

        $GLOBALS['respMenuHasSubmenu'] = !empty($menu["children"]);
        $GLOBALS['respMenuIsSubmenu'] = isset($menu["parent"]);
        $GLOBALS['respMenuLevel'] = $menu["level"];
        $GLOBALS['respIsLastSubmenuItem'] = isset($menu["parent"]) && $menu["lastItem"];

        \Resp\Core::doAction("resp--" . $menu["context"] . "-before-item", $menu);

        echo do_shortcode($content, $ignore_html);

        \Resp\Core::doAction("resp--" . $menu["context"] . "-after-item", $menu);

        if (!empty($menu["children"])) {

            \Resp\Core::doAction("resp--" . $menu["context"] . "-before-submenu", $menu);

            foreach ($menu["children"] as $submenu) {
                self::renderMenuItem($submenu, $content, $ignore_html);
            }

            \Resp\Core::doAction("resp--" . $menu["context"] . "-after-submenu", $menu);
        }
    }



    /**
     * @since 0.9.0
     */
    function menuItemsShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            'menu' => '',
            'context' => '',
            'ignore_html' => false
        ], $atts));

        $menuItems = wp_get_nav_menu_items($menu);

        if (empty($context)) {
            $context = sanitize_title($menu);
        }

        $items = self::getMenuStructure($context, $menuItems);


        if (empty($items)) {
            return;
        }


        ob_start();


        foreach ($items as $item) {
            self::renderMenuItem($item, $content, $ignore_html);
        }

        unset($GLOBALS['respMenuHasSubmenu']);
        unset($GLOBALS['respMenuIsSubmenu']);
        unset($GLOBALS['respMenuLevel']);
        unset($GLOBALS['respIsLastSubmenuItem']);

        return ob_get_clean();
    }


    /**
     * @since 0.9.0
     */
    function listmenuShortcode($atts = [], $content = null)
    {

        $menuLoc = $atts["menu"] ?? ($atts["theme_location"] ?? "");

        if(!has_nav_menu($menuLoc)){
            return;
        }

        $confParams = [
            'container' ,
            'container_class' ,
            'container_id' ,
            'menu_class' ,
            'menu_id' ,
            'before' ,
            'after' ,
            'link_before',
            'link_after'
        ];

        $defParams = [
            'menu' => '',
            'container' => "div",
            'container_class' => "",
            'container_id' => '',
            'menu_class' => "" ,
            'menu_id' => '',
            'echo' => true,
            'fallback_cb' => 'wp_page_menu',
            'before' => '',
            'after' => '',
            'link_before' => '',
            'link_after' => '',
            'depth' => 0,
            'walker' => '',
            'theme_location' => ''
        ];

        if(!empty($menuLoc)){

            array_walk($defParams , function(&$item , $key) use ($confParams , $menuLoc){

                if(in_array($key , $confParams)){

                    $item = self::$menus[$menuLoc][$key] ?? $item;

                } 
    
            });

        }


        extract(shortcode_atts($defParams , $atts));


        $m = sanitize_title($menu ?: $menu_id);


        $nav = wp_nav_menu(array(
            'menu' => $menu,
            'container' => $container,
            'container_class' => apply_filters("resp--listmenu-$m-container-classes", $container_class),
            'container_id' => $container_id,
            'menu_class' => apply_filters("resp--listmenu-$m-classes",  $menu_class),
            'menu_id' => $menu_id,
            'echo' => false,
            'fallback_cb' => $fallback_cb,
            'before' => $before,
            'after' => $after,
            'link_before' => $link_before,
            'link_after' => $link_after,
            'depth' => $depth,
            'walker' => new RespWalkerNavMenu,
            'theme_location' => $theme_location
        ));

        return $nav;
    }
}
