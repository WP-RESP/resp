<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use Resp\Core;

defined('RESP_VERSION') or die;

class RespWalkerNavMenu extends \Walker_Nav_Menu
{
    public $tree_type = ['post_type', 'taxonomy', 'custom'];

    public $db_fields = ['parent' => 'menu_item_parent', 'id' => 'db_id'];

    /** 
     * @since 0.9.3
     */
    private static function checkSpacing(&$t, &$n, $args = null )
    {
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
    }

    /** 
     * @since 0.9.3
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {

        $t = '';

        $n = '';

        self::checkSpacing($t, $n, $args);

        $indent = str_repeat($t, $depth);

        $classes = ['sub-menu'];

        $class_names = join(' ', apply_filters('nav_menu_submenu_css_class', $classes, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $output .= "{$n}{$indent}<ul$class_names>{$n}";
    }

    /** 
     * @since 0.9.3
     */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {

        $t = '';

        $n = '';

        self::checkSpacing($t, $n, $args);

        $indent  = str_repeat($t, $depth);

        $output .= "$indent</ul>{$n}";
    }

    /** 
     * @since 0.9.3
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {

        $t = '';

        $n = '';

        self::checkSpacing($t, $n, $args);

        $args = apply_filters('nav_menu_item_args', $args, $item, $depth);

        $ns = 'menu-item';
        
        if(isset($args->menu)){
            $ns = $args->menu->slug;
        } 

        $indent = ($depth) ? str_repeat($t, $depth) : '';

        $classes   = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $classes[] = "$ns--item-{$item->ID}";
    
        $isCurrent = in_array('current-menu-item', $classes);

        $id = apply_filters('nav_menu_item_id', "$ns-item-" . $item->ID, $item, $args, $depth);
        
        $itemElem = Core::tag("li", $isCurrent ? "$ns-item:active" : "$ns-item" , '' , [
            "id" => esc_attr($id),
            "class" => apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth)
        ]);

        ob_start();

        Core::trigger("$ns-item-before" , true);

        echo $indent . $itemElem->render(false , false);

        $atts           = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        if ('_blank' === $item->target && empty($item->xfn)) {
            $atts['rel'] = 'noopener noreferrer';
        } else {
            $atts['rel'] = $item->xfn;
        }
        $atts['href']         = !empty($item->url) ? $item->url : '';
        $atts['aria-current'] = $item->current ? 'page' : '';


        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $title = apply_filters('the_title', $item->title, $item->ID);

        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $linkElem = Core::tag(
            "a",
            $isCurrent ? "$ns-item-link:active" : "$ns-item-link" , 
            $args->link_before . $title . $args->link_after,
            ["attr" => $atts]
        );

        Core::trigger("$ns-item-link-before" , true);

        $item_output  = $args->before;
        $item_output .= $linkElem->render(false);
        $item_output .= $args->after;

        echo apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);

        Core::trigger("$ns-item-link-after" , true);

        $output .= ob_get_clean();
    }

    /** 
     * @since 0.9.3
     */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {

        $t = '';

        $n = '';

        $ns = 'menu-item';
        
        if(isset($args->menu)){
            $ns = $args->menu->slug;
        } 

        self::checkSpacing($t, $n, $args);

        ob_start();

        echo "</li>{$n}";

        Core::trigger("$ns-item-after" , true);

        $output .= ob_get_clean();

    }
}
