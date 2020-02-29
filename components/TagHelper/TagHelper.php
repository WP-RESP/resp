<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use Resp\Component;

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

        $this->define_shortcodes('resp-tag-attr', 'tagAttributeShortcode');

        add_shortcode('resp-tag-group', [$this, 'tagGroupShortcode']);

        add_filter('no_texturize_shortcodes', [$this, 'exemptFromWptexturize']);
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
    private function get_dynamic_atts($tag, &$content)
    {
        $atts = [];

        $matches = [];

        $level = strlen(str_replace("resp-tag", "", $tag));

        $pattern = "/\[resp-tag-attr\@{" . $level . "}(.*?)\[\/resp-tag-attr\@{" . $level . "}\]/s";

        preg_match_all($pattern, $content, $matches);

        foreach (array_values($matches[0]) as $value) {

            $json = do_shortcode($value, true);

            $atts = array_merge(json_decode($json, true), $atts);

            $content = str_replace($value, "", $content);
        }

        return $atts;
    }


    /**
     * @since 0.9.0
     */
    function tagAttributeShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            "name" => "",
            "trim" => "true"
        ], $atts));


        if (empty($name)) {
            $names = array_values($atts);

            if (count($names) === 0) {
                return;
            }

            $name = $names[0];
        }

        $value = do_shortcode($content);

        if ($trim == "true") {
            $value = ltrim(rtrim($value));
        }

        return json_encode([$name => $value], JSON_UNESCAPED_SLASHES);
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
    private function fix_attributes(&$atts)
    {

        $html_atts_prefix = ["data", "aria", "accept"];

        $default_keys = array_keys(self::$tag_defaults);

        $fixed_elements = [];

        foreach ($atts as $key => $value) {

            if (in_array($key, $default_keys)) {
                continue;
            }

            foreach ($html_atts_prefix as $attr) {

                if (__resp_str_startsWith($key, "{$attr}_")) {
                    $fixed_elements[] = $key;
                    $key = str_replace("{$attr}_", "{$attr}-", $key);
                }
            }

            $atts["attr"][$key] = $value;
        }

        foreach ($fixed_elements as $elem) {
            unset($atts[$elem]);
        }
    }


    /**
     * @since 0.9.0
     */
    function tagShortcode($atts = [], $content = null, $tag)
    {

        $dynamics = $this->get_dynamic_atts($tag, $content);

        $this->check_for_tags($content);

        $atts = array_merge(self::$tag_defaults, $dynamics, is_array($atts) ? $atts : []);

        $this->fix_attributes($atts);

        $tag = \Resp\Tag::create($atts);

        return $tag->raw($content)->render();
    }
}
