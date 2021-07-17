<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

defined('RESP_VERSION') or die;

class Tag
{

    private $params = [
        "name" =>  "div",
        "class" =>  [],
        "id" =>     "",
        "children" => [],
        "attr" =>     [],
        "style" =>    [],
        "content" =>  "",
        "body" =>     true,
        "append_content" => false,
        "do_shortcode" => false
    ];

    function __construct($args = [])
    {
        $this->set($args);
    }

    /** 
     * @since 0.9.0
     */
    static function create($args = [])
    {

        if (is_array($args)) {
            return new Tag($args);
        }

        return new Tag(["name" => $args]);
    }



    /**
     * @since 0.9.0
     */
    static function close($name = "div", $echo = true)
    {

        $output = "</$name>";

        if ($echo) {

            echo $output;

            return __CLASS__;
        }

        return $output;
    }

    /**
     * @since 0.9.0
     */
    static function comment($comment)
    {
        echo "<!-- " . esc_html($comment) . " -->";
        return __CLASS__;
    }

    /**
     * @since 0.9.0
     */
    function addClass($value)
    {
        $classes = $this->get("class");
        return $this->set(["class" => array_merge($classes, is_array($value) ? $value : [$value])]);
    }

    /**
     * @since 0.9.0
     */
    function e()
    {
        $this->render(true);
    }


    /**
     * @since 0.9.0
     */
    function eo()
    {
        $this->render(true, false);
    }

    /**
     * @since 0.9.0
     */
    function o(){

        $this->render(false, false);

    }


    /**
     * @since 0.9.0
     */
    function set($args)
    {
        
        $this->params = array_merge($this->params, $args);

        return $this;
    }

    /**
     * @since 0.9.0
     */
    function get($prop)
    {
        return $this->params[$prop];
    }

    /**
     * @since 0.9.0
     */
    function filter($args, $meta = null)
    {

        $new_parms = [];

        foreach ($args as $key => $value) {

            $param = $this->get($key);

            if (!is_array($value)) {

                $new_parms[$key] = apply_filters($value, $param, $meta);

                continue;
            }

            $new_parms[$key] = $param;

            foreach ($value as $filter) {

                $new_parms[$key] = apply_filters($filter, $new_parms[$key], $meta);
            }
        }

        $this->set($new_parms);

        return $this;
    }


    /**
     * @since 0.9.0
     */
    function render($echo = false, $close = true)
    {

        $name = $this->get("name");

        $attr = $this->getAttributes();

        $middle = implode(" ", array_map(function ($item) use ($attr) {

            if (is_bool($attr[$item]) && $attr[$item] == true) {
                return $item;
            }

            return "$item=\"" . esc_attr($attr[$item]) . "\"";

        }, array_keys($attr)));


        $body = $this->get("body");

        $end = !$body ? "/>" : ('>' . $this->getInnerHtml() . ($close ? '</' . $name . '>' : ''));

        $output = "<$name $middle $end";

        if ($echo) {
            echo $output;
        }

        return $output;
    }



    /**
     * @since 0.9.0
     */
    function toString(){

        return $this->render();

    }


    /**
     * @since 0.9.0
     */
    private function getAttributes()
    {

        $class = $this->get("class");

        $styles = $this->get("style");

        $attr = $this->get("attr");

        if(!isset($attr["id"])){
            $attr["id"] = $this->get("id");
        }

        $class_inline = is_array($class) ? implode(" ", $class) : $class;

        // check for extra classes

        if (isset($attr["class"])) {

            $class = $attr["class"];

            $class_meta = is_array($class) ? implode(" ", $class) : $class;

            $class_inline = implode(" ", [$class_inline,  $class_meta]);
        }

        $attr["class"] = $class_inline;


        if (isset($attr["style"]) && is_array($attr["style"])) {
            $styles = array_merge($styles, $attr["style"]);
        }

        if (is_array($styles)) {
            $attr["style"] = implode(";", array_map(function ($item) use ($styles) {

                return "$item: $styles[$item]";
            }, array_keys($styles)));
        } else {
            $attr["style"] = $styles;
        }

        $attr = array_filter($attr, function ($item) {
            return !empty($item);
        });

        foreach ($attr as $key => $value) {
            if (is_scalar($value) && '' !== $value && false !== $value) {
                $attr[$key] = ('href' === $key) ? esc_url($value) : esc_attr($value);
            }
        }

        return $attr;
    }

    /**
     * @since 0.9.0
     */
    function required()
    {

        $this->attr("required", true);

        return $this;
    }

    /**
     * @since 0.9.0
     */
    function data($name, $value = null)
    {

        $name = str_replace("data-", "", $name);

        if ($value == null) {
            return $this->attr("data-$name");
        }

        $this->attr("data-$name", $value);

        return $this;
    }

    /**
     * @since 0.9.0
     */
    function attr($name, $value = null)
    {

        $attr = $this->get("attr");

        if ($value == null) {
            return isset($attr[$name]) ? $attr[$name] : '';
        }

        $attr[$name] = $value;

        $this->set(["attr" => $attr]);

        return $this;
    }


    /**
     * @since 0.9.0
     */
    function getInnerHtml()
    {

        $do_shortcode = $this->get("do_shortcode");

        $append_content = $this->get("append_content");

        $content = $this->get("content");

        if ($do_shortcode) {
            $content = do_shortcode($content);
        }

        $children = $this->get("children");

        if (empty($children)) {
            return $content;
        }

        $output = $append_content ? "" : $content;

        foreach ($children as $child) {
            $output .=  $child->render();
        }

        $output .= $append_content ? $content : "";

        return $output;
    }

    /**
     * @since 0.9.0
     */
    function prepend($elems){

        if(is_string($elems))
        {
            return $this->raw($elems . $this->get("content"));
        }

        $children = $this->get("children");

        if (!is_array($elems)) {
            $elems = [$elems];
        }

        $this->set(["children" => array_merge($elems , $children)]);

        return $this;

    }


    /**
     * @since 0.9.0
     */
    function append($elems)
    {

        if(is_string($elems))
        {
            return $this->raw($this->get("content") . $elems);
        }

        $children = $this->get("children");

        if (!is_array($elems)) {
            $elems = [$elems];
        }

        $this->set(["children" => array_merge($children, $elems)]);

        return $this;
    }


    /**
     * @since 0.9.0
     */
    function raw($html)
    {
        $this->set(["content" => $html]);
        return $this;
    }

    /**
     * @since 0.9.0
     */
    static function css($styles)
    {

        $result = implode(" ", array_map(function ($key) use ($styles) {

            return "$key{" . implode(";", array_map(function ($item_key) use ($styles, $key) {

                return "$item_key:" . $styles[$key][$item_key];
            }, array_keys($styles[$key])))  . "}";
        }, array_keys($styles)));

        return $result;
    }


    /**
     * @since 0.9.0
     */
    static function style($css = null)
    {

        $content = "";

        if (is_array($css)) {
            $content = implode(" ", $css);
        } else {
            $content = $css;
        }

        return Tag::create([
            "name" => "style",
            "content" => $content
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function labelFor($id, $text, $atts = [])
    {
        return new Tag([
            "name" => "label",
            "content" => $text,
            "attr" => array_merge(["for"  => $id], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function img($src, $atts = [])
    {
        return new Tag([
            "name" => "img",
            "body" => false,
            "attr" => array_merge(["src"  => esc_url( $src )], $atts)
        ]);
    }


    /**
     * @since 0.9.2
     */
    static function button($text = "", $atts = [])
    {
        return self::create([
            "name" => "button",
            "content" => $text,
            "attr" => $atts
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function inputFor($id, $type = "text", $value = "", $atts = [])
    {
        return self::create([
            "name" => "input",
            "id"  => $id,
            "attr" => array_merge([
                "type" => $type,
                "value" => $value,
                "name" => $id
            ], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function fileFor($id, $atts = [])
    {
        return self::inputFor($id, "file", null, $atts);
    }



    /**
     * @since 0.9.0
     */
    static function textFor($id, $value = "", $atts = [])
    {
        return self::inputFor($id, "text", $value, $atts);
    }

    /**
     * @since 0.9.0
     */
    static function emailFor($id, $value = "", $atts = [])
    {
        return self::inputFor($id, "email", $value, $atts);
    }

    /**
     * @since 0.9.0
     */
    static function textareaFor($id, $value = "", $atts = [])
    {
        return self::create([
            "name" => "textarea",
            "id"  => $id,
            "content" => $value,
            "attr" => array_merge([
                "name" => $id
            ], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function submit($text = "Submit", $atts = [])
    {
        return self::create([
            "name" => "input",
            "body" => false,
            "attr" => array_merge([
                "type"  => "submit",
                "value" => $text
            ], $atts)
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function br()
    {
        return self::create([
            "name" => "br",
            "body" => false
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function tr()
    {
        return self::create([
            "name" => "tr"
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function p($text = "", $atts = [])
    {
        return self::create([
            "name" => "p",
            "content" => $text,
            "attr" => $atts
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function h1($text, $atts = [])
    {
        return self::create(["name" => "h1",  "content" => $text, "attr" => $atts]);
    }


    /**
     * @since 0.9.0
     */
     static function h2($text, $atts = [])
    {
        return self::create(["name" => "h2",  "content" => $text,  "attr" => $atts]);
    }


    /**
     * @since 0.9.0
     */
     static function h3($text, $atts = [])
    {
        return self::create(["name" => "h3",  "content" => $text,  "attr" => $atts]);
    }

    /**
     * @since 0.9.0
     */
    static function hr()
    {
        return self::create(["name" => "hr",  "body" => false]);
    }

    /**
     * @since 0.9.0
     */
    static function a($text, $href, $atts = [])
    {
        return self::create([
            "name" => "a",
            "content" => $text,
            "attr" => array_merge(["href" => esc_url( $href )], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function form($id, $action = null, $method = "post", $atts = [])
    {
        return self::create([
            "name" => "form",
            "id" => $id,
            "attr" => array_merge([
                "action" => $action,
                "method" => $method
            ], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function pre($text =  null, $atts = [])
    {
        return self::create([
            "name" => "pre",
            "content" => $text,
            "attr" => $atts
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function code($text, $atts = [])
    {
        return self::create([
            "name" => "code",
            "content" => $text,
            "attr" => $atts
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function span($text = "", $atts = [])
    {
        return self::create([
            "name" => "span",
            "content" => $text,
            "attr" => $atts
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function hiddenFor($id, $value = "", $atts = [])
    {
        return self::create([
            "name" => "input",
            "id"  => $id,
            "body" => false,
            "attr" => array_merge([
                "type" => "hidden",
                "value" => $value,
                "name" => $id
            ], $atts)
        ]);
    }

    /**
     * @since 0.9.0
     */
    static function radioFor($name, $label, $value, $current, $atts = [])
    {
        $radio = self::create([
            "name" => "input",
            "attr" => array_merge([
                "type" => "radio",
                "value" => $value,
                "name" => $name
            ], $atts)
        ]);

        if ($value == $current) {
            $radio->attr("checked", true);
        }

        return self::create([
            "name" => "label",
            "content" => $label,
            "append_content" => true
        ])->append($radio);
    }

    /**
     * @since 0.9.0
     */
    static function checkboxFor($id, $label, $value , $atts = [])
    {

        $chkbox = self::create([
            "name" => "input",
            "id" => $id,
            "attr" => array_merge([
                "type" => "checkbox",
                "value" => "true",
                "name" => $id
            ], $atts)
        ]);

        if ( $value == "true" ) {
            $chkbox->attr("checked", true);
        }

        if(empty($label)){
            return $chkbox;
        }

        return self::create([
            "name" => "label",
            "content" => $label,
            "append_content" => true
        ])->append($chkbox);
    }

    /**
     * @since 0.9.3
     */
    static function selectFor($id, $value , $options = [], $atts = [])
    {

        $select = self::create([
            "name" => "select",
            "id" => $id,
            "attr" => array_merge([
                "name" => $id
            ], $atts)
        ]);

        foreach($options as $key => $param){

            $val = $param["value"] ?? $key;

            $attr = [
                "value" =>  $val
            ];

            if($val == $value){
                $attr["selected"] = "selected";
            }

            $lbl = is_array($param) ? ($param["label"] ?? "---") : $param;

            $select->append(Tag::create([
                "name" => "option",
                "attr" => array_merge_recursive($attr , $param["attr"] ?? []),
                "content" => $lbl
            ]));

        }


        return $select;

    }


    /**
     * @since 0.9.0
     */
    function appendContent()
    {
        $this->set(["append_content" => true]);
        return $this;
    }


    /**
     * @since 0.9.0
     */
    static function dashicons($class)
    {
        return Tag::span("")->set(["class" => ["dashicons", $class]]);
    }


    /**
     * @since 0.9.0
     */
    static function notice($message, $type = "info", $dismissible = true)
    {
        $classes = ["notice", "notice-$type"];

        if ($dismissible) {
            $classes[] = "is-dismissible";
        }

        return Tag::create("div")->addClass($classes)->append(Tag::p($message));
    }
    
}
