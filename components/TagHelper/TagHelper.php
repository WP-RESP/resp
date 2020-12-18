<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component , Resp\DOMHandlers as dom;
use Resp\Tag;

defined('RESP_VERSION') or die;

class TagHelper extends Component
{

    private $shortcodes = [];

    private static $groups = [];

    private static $tag_defaults = [
        "name" => "div",
        "class" => [],
        "id" => "",
        "content" => "",
        "style" => [],
        "body" => true,
        "append_content" => false,
        "do_shortcode" => false
    ];


    function __construct()
    {
        $this->define_shortcodes('resp-tag', 'tagShortcode');

        add_shortcode('resp-tag-group', [$this, 'tagGroupShortcode']);

        add_filter('no_texturize_shortcodes', [$this, 'exemptFromWptexturize']);

        __resp_register_parser( 
            "resp-tag" ,  
            "resp-tag" , 
            [$this , "tagShortcode"]
        );
        
    }


    /**
     * @since 0.9.0
     */
    private function define_shortcodes($prefix, $method,  $limit = 10)
    {
        for ($i = 0; $i <  $limit; $i++) {
            $name = $prefix .  str_repeat("@", $i);
            add_shortcode($name, [$this, $method]);
            $this->shortcodes[] = $name;
        }
    }


    /**
     * @since 0.9.0
     */
    function exemptFromWptexturize($shortcodes)
    {
        $shortcodes = array_merge($this->shortcodes, $shortcodes, ["resp-tag-group"]);
        return $shortcodes;
    }


    /**
     * @since 0.9.0
     */
    function tagGroupShortcode($atts = [], $content = null)
    {
        if (!isset($atts["name"])) {
            return;
        }

        $name = $atts["name"];

        $ignore_html = isset($atts["ignore_html"]) ? $atts["ignore_html"] : false;

        if (empty($content)) {
            return self::$groups[$name];
        } else {
            self::$groups[$name] = do_shortcode($content, $ignore_html);
        }

        return;
    }




    /**
     * @since 0.9.0
     */
    private function check_for_tags(&$content)
    {

        // check for nested shortcode

        $nested_tags_pattern = "/(\[resp-tag)(?=[\*\s\]])/";

        if (preg_match($nested_tags_pattern, $content)) {
            $content = do_shortcode($content);
        }
    }


    /**
     * @since 0.9.0
     */
    function tagShortcode($atts = [], $content = null, $tag)
    {

        if(!is_array($atts)){
            $atts  = [];
        }

        if(!isset($atts["name"]) && !empty($atts) ){

            $name = array_values($atts)[0];
            $atts["name"] = $name;

        }

        dom::getJsonAttributes($atts , $content);

        if(!empty($content) && empty($atts["content"] ?? "")){
            $atts["content"] = $content;
        }

    
        $result = Tag::create($atts);

        return $result->render();
    }
}
