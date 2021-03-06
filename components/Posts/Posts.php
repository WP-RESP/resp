<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component , Resp\DOMHandlers as dom;

defined('RESP_VERSION') or die;

class Posts extends Component
{

    private static $reserved_posts = [];

    const SHORTCODES = [
        "resp-posts-query", "resp-posts-reserve",
        "resp-posts-clean", "resp-posts-pagination", "resp-attachment"
    ];

    function __construct()
    {
        foreach (self::SHORTCODES as $scode) {
            $parts = explode("-", $scode);
            $fname = array_pop($parts) . "Shortcode";
            add_shortcode($scode, [$this,  $fname]);
        }
        

        add_filter("no_texturize_shortcodes" , function($shortcodes) {
            $shortcodes[] = 'resp-posts-query';
            return $shortcodes;
        });

    
    }

    /**
     * @since 0.9.0
     */
    function paginationShortcode($atts = [], $content = null)
    {

        global $wp_query;

        $args = array(
            'format'             => '?paged=%#%',
            'show_all'           => false,
            'end_size'           => 1,
            'mid_size'           => 2,
            'prev_next'          => true,
            'prev_text'          => esc_html__('« Previous' , "resp"),
            'next_text'          => esc_html__('Next »' , "resp"),
            'type'               => 'plain',
            'add_args'           => false,
            'add_fragment'       => '',
            'before_page_number' => '',
            'after_page_number'  => ''
        );

        $args['base'] = str_replace(PHP_INT_MAX, '%#%', esc_url(get_pagenum_link(PHP_INT_MAX)));

        $args['current'] = max(1, get_query_var('paged'));

        $args['total'] = $wp_query->max_num_pages;

        return paginate_links($args);
        
    }


    /**
     * @since 0.9.0
     */
    function attachmentShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            "id" => -1,
            "size" => "thumbnail"
        ], $atts));

        if ($id < 0) {
            return;
        }

        return wp_get_attachment_image_src($id, $size);
    }

    /** 
     * @since 0.9.3
     */
    private static function advancedQueryParamsCheck(&$atts){

        $advanced_query_params = [
            "meta_query_key" => "",
            "meta_query_value" => "",
            "meta_query_compare" => "=",
            "tax_query_taxonomy" => "",
            "tax_query_field" => "slug",
            "tax_query_terms" => ""
        ];
    

        if(isset($atts["meta_query_key"])) {

            $meta_query_key = $atts["meta_query_key"];

            $meta_query_value = $atts["meta_query_value"] ?? $advanced_query_params["meta_query_value"];

            $meta_query_compare = $atts["meta_query_compare"] ?? $advanced_query_params["meta_query_compare"];

            if(!empty($meta_query_key) && !empty($meta_query_compare)){

                $atts["meta_query"] = [
                    "key" => $meta_query_key,
                    "value" => $meta_query_value,
                    "compare" => $meta_query_compare
                ];
    
            }

        }

        if(isset($atts["tax_query_taxonomy"])) {

            $tax_query_taxonomy = $atts["tax_query_taxonomy"];

            $tax_query_terms = $atts["tax_query_terms"] ?? $advanced_query_params["tax_query_terms"];

            $tax_query_field = $atts["tax_query_field"] ?? $advanced_query_params["tax_query_field"];

            if(!empty($tax_query_taxonomy) && !empty($tax_query_terms)){

                $atts["tax_query"] = [
                    "taxonomy" => $tax_query_taxonomy,
                    "field" => $tax_query_field,
                    "terms" => $tax_query_terms
                ];
    
            }

            

        }

        foreach($advanced_query_params as $key => $default){

            unset($atts[$key]);

        }

    }


    /**
     * @since 0.9.0
     */
    function queryShortcode($atts = [], $content = null , $tag)
    {

        global $wp_query, $post;

        extract(shortcode_atts([
            "ignore_html" => false,
            "container" => '',
            "class" => '',
            "not_reserved" => "false",
            "reserves" => "false",
            "group" => "global",
            "taxonomy" => null,
            "taxonomy_slug" => null,
            "paginate" => "false"
        ], $atts));

        if(!is_array($atts))
        {
            $atts = [];
        }


        // get json attributes
        dom::getJsonAttributes($atts , $content);

        if($paginate == "true"){

            if (get_query_var('paged')) {
                $paged = get_query_var('paged');
            } elseif (get_query_var('page')) {
                $paged = get_query_var('page');
            } else {
                $paged = 1;
            }

            $atts["paged"] = $paged;

        }

        if ($reserves == "true") {

            if (!isset(self::$reserved_posts[$group])) {
                return;
            }

            $data = self::$reserved_posts[$group];

            $dataSize = sizeof($data);

            if (!empty($data) && $data[0] instanceof \WP_Post) {

                $GLOBALS["respIsFirstPost"] = "true";

                $GLOBALS["respPostIndex"] = "0";
                
                $GLOBALS["respIsLastPost"] = "false";

                ob_start();

                foreach ($data as $index => $post) {

                    setup_postdata($post);

                    if (($index + 1) == $dataSize) {
                        $GLOBALS["respIsLastPost"] = "true";
                    }

                    echo do_shortcode($content, $ignore_html);

                    $GLOBALS["respIsFirstPost"] = "false";

                    $GLOBALS["respPostIndex"] = (string) (((int) $GLOBALS["respPostIndex"]) + 1);

                }

                wp_reset_postdata();

                return ob_get_clean();
            }

            $atts["post__in"] = array_merge(...array_values(self::$reserved_posts));
        } else if (isset(self::$reserved_posts[$reserves])) {
            $atts["post__in"] = self::$reserved_posts[$reserves];
        }

        if ($not_reserved == "true") {
            $atts["post__not_in"] = array_merge(...array_values(self::$reserved_posts));
        } else if (isset(self::$reserved_posts[$not_reserved])) {
            $atts["post__not_in"] = self::$reserved_posts[$not_reserved];
        }

        if (isset($taxonomy) && isset($taxonomy_slug)) {
            $atts[$taxonomy] =  $taxonomy_slug;
        }

        if(!isset($atts["posts_per_page"]))
        {
            $postsPerPage = get_option( 'posts_per_page' );
            $atts = array_merge($atts , [ 'posts_per_page' => $postsPerPage]);
        }

        self::advancedQueryParamsCheck($atts);

        $wp_query = new \WP_Query($atts);

        ob_start();

        if ($wp_query->have_posts()) {

            if (!empty($container)) {
                \Resp\Tag::create([
                    "name" => $container,
                    "class" => [$class]
                ])->eo();
            }
    
            $GLOBALS["respIsFirstPost"] = "true";
    
            $GLOBALS["respPostIndex"] = "0";

            $GLOBALS["respIsLastPost"] = "false";

            while ($wp_query->have_posts()) {

                $wp_query->the_post();

                if (($wp_query->current_post + 1) == ($wp_query->post_count)) {
                    $GLOBALS["respIsLastPost"] = "true";
                }

                echo do_shortcode($content, $ignore_html);

                $GLOBALS["respIsFirstPost"] = "false";

                $GLOBALS["respPostIndex"] = (string) (((int) $GLOBALS["respPostIndex"]) + 1);
            }

            if (!empty($container)) {
                \Resp\Tag::close($container);
            }
    
        }
        
        $result = ob_get_clean();

        wp_reset_query();

        return $result;

    }

    /**
     * @since 0.9.0
     */
    function reserveShortcode($atts = [], $content = null)
    {

        global $post;

        extract(shortcode_atts([
            "param" => "ID",
            "group" => "global"
        ], $atts));

        if ($param == "all") {
            self::checkPostGroupThenPush($group, $post);
            return;
        }

        self::checkPostGroupThenPush($group, $post->$param);
    }


    /**
     * @since 0.9.0
     */
    private static function checkPostGroupThenPush($name, $pid)
    {
        if (!isset(self::$reserved_posts[$name])) {
            self::$reserved_posts[$name] = [];
        }
        self::$reserved_posts[$name][] = $pid;
    }

    /**
     * @since 0.9.0
     */
    function cleanShortcode($atts = [], $content = null)
    {
        if (isset($atts["group"])) {
            self::$reserved_posts[$atts["group"]] = [];
            return;
        }

        self::$reserved_posts = [];
    }
}
