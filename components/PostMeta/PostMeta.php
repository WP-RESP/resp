<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\FileManager, Resp\Tag, Resp\ThemeBuilder;
use Resp\DOMHandlers;

defined('RESP_VERSION') or die;

class PostMeta extends Component
{

    private static $next_post = null;

    private static $prev_post = null;

    private static $post_data =  [
        "ID", "post_author", "post_name", "post_type", "post_title", "post_date", "post_date_gmt",
        "post_content", "post_excerpt", "post_status", "comment_status", "ping_status",
        "post_password", "post_parent", "post_parent", "post_modified", "post_modified_gmt",
        "comment_count", "menu_order", "guid"
    ];

    function __construct()
    {

        $class = get_called_class();

        add_filter("resp-core--config-output", "$class::replacePostParams");

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

        DOMHandlers::getJsonAttributes($atts, $content);

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

        $post = null;

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

        if ($name === "taxonomy") {
            if (isset($atts["terms"])) {
                return self::getTerms($id, $atts["terms"], $atts);
            }
        }

        if (__resp_str_startsWith($name, "@author:") && !is_null($post)) {
            return get_the_author_meta(str_replace("@author:", "", $name), $post->post_author);
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
            return $this->getCategories($atts);
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

        if (empty(self::$next_post)) {
            self::$next_post = get_next_post($in_same_term, $excluded_terms, $taxonomy);
        }

        if (!isset(self::$next_post->ID) || self::$next_post->ID == $id) {
            $GLOBALS["respNextPostNotFound"] = "true";
            return "";
        }

        if (empty($param)) {
            return "";
        }

        $atts["id"] = self::$next_post->ID;
        $atts["name"] = $param;

        return $this->getMeta($atts, $param, self::$next_post->ID, $do_shortcode, $ignore_html);
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


        if (empty(self::$prev_post)) {
            self::$prev_post = get_previous_post($in_same_term, $excluded_terms, $taxonomy);
        }

        if (!isset(self::$prev_post->ID) || self::$prev_post->ID == $id) {
            $GLOBALS["respPrevPostNotFound"] = "true";
            return "";
        }

        if (empty($param)) {
            return "";
        }

        $atts["id"] = self::$prev_post->ID;
        $atts["name"] = $param;

        return $this->getMeta($atts, $param, self::$prev_post->ID, $do_shortcode, $ignore_html);
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
    private static function getTerms($id, $term, $atts)
    {

        extract(shortcode_atts([
            "container" => "ul",
            "class" => "",
            "item" => "li",
            "itemClass" => "",
            "linkedItem" => true,
            "include_children" => true
        ], $atts));

        $terms = wp_get_post_terms($id, $term);


        if (!$terms) {
            return;
        }

        ob_start();

        if (!empty($container)) {
            \Resp\Tag::create([
                "name" => $container,
                "class" => $class
            ])->eo();
        }

        foreach ($terms as $t) {

            $item_body = \Resp\Tag::create([
                "name" => $item,
                "class" => ["term-$t->slug"]
            ]);

            $href = esc_url(get_term_link($t->term_id));

            $rel = "category tag";

            if ($item == "a") {
                $item_body->set([
                    "content" => $t->name,
                    "attr" => [
                        "href" => $href,
                        "rel" => $rel
                    ]
                ]);
            } else if ($linkedItem) {
                $item_body->append(\Resp\Tag::create([
                    "name" => "a",
                    "content" => $t->name,
                    "attr" => [
                        "href" => $href,
                        "rel" => $rel
                    ]
                ]));
            } else {
                $item_body->set(["content" => $t->name]);
            }

            $item_body->e();
        }

        if (!empty($container)) {
            \Resp\Tag::close($container);
        }

        return ob_get_clean();
    }


    /**
     * @since 0.9.0
     */
    private function getCategories($atts)
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

    /**
     * @since 0.9.3
     */
    static function replacePostParams($output)
    {

        global $post;

        if (!isset($post)) {
            return $output;
        }

        foreach (array_merge(self::$post_data, [
            "id", 
            "permalink" , 
            "thumbnail",
            "image"
            ]) as $param) {

            $keyword = "@post:$param";

            if(strpos($output , $keyword) < 0){
                continue;
            }

            switch ($param) {
                case "id":
                    $value = $post->ID;
                    break;
                case "permalink":
                    $value = get_permalink($post->ID);
                    break;
                case "thumbnail" :
                    $value = get_the_post_thumbnail_url( $post->ID );
                    break;
                case "image" :
                    $value = get_the_post_thumbnail_url( $post->ID , "full");
                    break;
                default:
                    $value = $post->$param;
            }

            if (in_array($param, ["guid", "permalink"])) {
                $value = esc_url($value);
            }

            $output = str_replace($keyword, $value, $output);
        }

        return $output;
    }
}
