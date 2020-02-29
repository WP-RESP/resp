<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use  Resp\Component, Resp\FileManager, Resp\Tag, Resp\ThemeBuilder;

class PostMeta extends Component
{

    private static $post_data =  [
        "ID", "post_author", "post_name", "post_type", "post_title", "post_date", "post_date_gmt",
        "post_content", "post_excerpt", "post_status", "comment_status", "ping_status",
        "post_password", "post_parent", "post_parent", "post_modified", "post_modified_gmt",
        "comment_count", "menu_order", "guid"
    ];

    function __construct()
    {
        add_shortcode('resp-meta', [$this, 'metaShortcode']);
        add_shortcode('resp-date', [$this, 'dateShortcode']);

        $GLOBALS["respNextPostNotFound"] = "false";
        $GLOBALS["respPrevPostNotFound"] = "false";
    }

    /**
     * @since 0.9.0
     */
    function dateShortcode($atts = [], $content = null)
    {

        $format = isset($atts['format']) ? $atts['format'] : get_option('date_format');

        return get_the_date($format);
    }


    /**
     * @since 0.9.0
     */
    function metaShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            "name" => "",
            "id"   => null,
            "do_shortcode" => true,
            "ignore_html" => false
        ], $atts));



        if (empty($name)) {

            if (empty($atts)) {
                return;
            } else {
                $name = array_values($atts)[0];
                $atts["name"] = $name;
            }
        }


        if ($do_shortcode == "1" || $do_shortcode == 1 || $do_shortcode == "true") {
            $do_shortcode == true;
        }

        if (!isset($id)) {
            $id = get_the_ID();
        }

        // preventing from loop
        if ($do_shortcode) {
            $frontpage_id = get_option('page_on_front');
            if ($id == $frontpage_id) {
                $do_shortcode = false;
            }
        }

        return $this->getMeta($atts, $name, $id, $do_shortcode, $ignore_html);
    }


    /**
     * @since 0.9.0
     */
    private function getMeta($atts, $name, $id, $do_shortcode, $ignore_html)
    {

        if (in_array($name, self::$post_data)) {

            $post = get_post($id);

            if ($post) {
                if ($do_shortcode) {
                    return do_shortcode($post->$name, $ignore_html);
                }
                return $post->$name;
            }

            return;

        }

        if ($name === "excerpt") {
            return get_the_excerpt($id);
        }

        if ($name === "comments_number") {
            return get_comments_number($id);
        }

        if (in_array($name, ["url", "permalink"])) {
            return get_permalink($id);
        }

        if (in_array($name, ["thumbnail_url",  "thumbnail"])) {

            return $this->getImage($atts);
        }

        if (in_array($name, ["cat",  "category", "categories"])) {
            return $this->get_categories($atts);
        }

        if (in_array($name, ["next",  "next_post"])) {
            return $this->getNextPost($id, $atts, $do_shortcode, $ignore_html);
        }

        if (in_array($name, ["prev",  "prev_post"])) {
            return $this->getPrevPost($id, $atts, $do_shortcode, $ignore_html);
        }

        $result = get_post_meta($id, $name, true);

        if ($do_shortcode) {
            return do_shortcode($result, $ignore_html);
        }

        return $result;
    }

    
    /**
     * @since 0.9.0
     */
    private function getNextPost($id, $atts, $do_shortcode, $ignore_html)
    {

        extract(shortcode_atts([
            "param" => "",
            "in_same_term" => false,
            "excluded_terms" => "",
            "taxonomy" => "category"
        ], $atts));

        $post = get_next_post($in_same_term, $excluded_terms, $taxonomy);

        if (!isset($post->ID) || $post->ID == $id) {
            $GLOBALS["respNextPostNotFound"] = "true";
            return "";
        }

        if (empty($param)) {
            return "";
        }

        return $this->getMeta($atts, $param, $post->ID, $do_shortcode, $ignore_html);
    }


    /**
     * @since 0.9.0
     */
    private function getPrevPost($id, $atts, $do_shortcode, $ignore_html)
    {

        extract(shortcode_atts([
            "param" => "",
            "in_same_term" => false,
            "excluded_terms" => "",
            "taxonomy" => "category"
        ], $atts));

        $post = get_previous_post($in_same_term, $excluded_terms, $taxonomy);

        if (!isset($post->ID) || $post->ID == $id) {
            $GLOBALS["respPrevPostNotFound"] = "true";
            return "";
        }

        if (empty($param)) {
            return "";
        }

        return $this->getMeta($atts, $param, $post->ID, $do_shortcode, $ignore_html);
    }


    /**
     * @since 0.9.0
     */
    private function getImage($atts)
    {
        extract(shortcode_atts([
            "name" => "thumbnail_url",
            "id"   => null,
            "size" => "thumbnail",
            "class" => "",
            "alt" => ""
        ], $atts));

        if ($name === "thumbnail_url") {
            return get_the_post_thumbnail_url($id, $size);
        }

        if ($name === "thumbnail") {
            return \Resp\Tag::img(
                get_the_post_thumbnail_url($id, $size)
            )->addClass($class)->render();
        }
    }


    /**
     * @since 0.9.0
     */
    private function get_categories($atts)
    {
        extract(shortcode_atts([
            "id"   => null,
            "container" => "ul",
            "class" => "",
            "item" => "li",
            "itemClass" => "",
            "linkedItem" => true,
            "include_children" => true
        ], $atts));

        $item_categories = get_the_category($id);

        if (!$item_categories) {
            return;
        }

        ob_start();

        if (!empty($container)) {
            \Resp\Tag::create([
                "name" => $container,
                "class" => $class
            ])->eo();
        }

        foreach ($item_categories as $category) {

            $item_body = \Resp\Tag::create([
                "name" => $item,
                "class" => ["category-$category->slug"]
            ]);

            $href = esc_url(get_category_link($category->term_id));

            $rel = "category tag";

            if ($item == "a") {
                $item_body->set([
                    "content" => $category->name,
                    "attr" => [
                        "href" => $href,
                        "rel" => $rel
                    ]
                ]);
            } else if ($linkedItem) {
                $item_body->append(\Resp\Tag::create([
                    "name" => "a",
                    "content" => $category->name,
                    "attr" => [
                        "href" => $href,
                        "rel" => $rel
                    ]
                ]));
            } else {
                $item_body->set(["content" => $category->name]);
            }

            $item_body->e();
        }

        if (!empty($container)) {
            \Resp\Tag::close($container);
        }

        return ob_get_clean();
    }
}
