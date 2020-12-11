<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Core, Resp\Component, Resp\Tag, Resp\ThemeBuilder as tb;

defined('RESP_VERSION') or die;

class ThemeValues extends Component
{

    const VALUES_DEF_NAME = "values";

    const VALUES_DEF_PROPS = [
        "sections", "panels"
    ];

    const CONTROL_OPTIONAL_PARAMS = [
        "description", "priority", "input_attrs"
    ];

    const VALUE_PARAMS = [
        "section", "type", "shortcode", "description",
        "customizable", "action", "constant",
        "container", "id", "class", "attr", "as", "to", "priority",
        "list", "limit", "args"
    ];

    const TYPE_CONTROL_PAIR = [
        "image" => "\WP_Customize_Image_Control",
        "imageSrc" => "\WP_Customize_Image_Control",
        "code" => "\WP_Customize_Code_Editor_Control",
        "media" => "\WP_Customize_Media_Control",
        "richtext" => "\Resp\RichTextEditorControl"
    ];

    private static $values = [];

    function __construct()
    {
        $limit = 10;

        add_action("resp-themebuilder-build", [$this, 'extractValues'], 10);
        add_action('customize_register', [$this, 'customizeRegister']);

        //add_action('customize_save_after',  [$this,  'customizeSave']);

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], PHP_INT_MAX);
        add_shortcode('resp-value', [$this, 'valueShortcode']);
        add_shortcode('resp-number', [$this, 'numberShortcode']);

        for ($i = 0; $i <  $limit; $i++) {
            $name = 'resp-if' .  str_repeat("@", $i);
            add_shortcode($name, [$this, 'ifShortcode']);
        }
    }


    /**
     * @since 0.9.0
     */
    function getValue($key, $default = "")
    {
        return __resp_array_item(self::$values, $key, $default);
    }



    /**
     * @since 0.9.0
     */
    function customizeSave()
    {
        $data = tb::getDefinitions(self::VALUES_DEF_NAME);

        foreach ($data as $key => $value) {

            if (in_array($key, self::VALUES_DEF_PROPS)) {
                continue;
            }

            if (!is_array($value)) {
                $data[$key] = $value;
            } else if (!isset($value["value"])) {
                continue;
            }

            if (!isset($data[$key])) {
                $val = get_theme_mod($key, $value["value"]);
                $data[$key] = [
                    "label" => __resp_array_item($value, "label", $key),
                    "value" => $val,
                    "type" => "text"
                ];
            }

            foreach (self::VALUE_PARAMS as $param) {
                if (isset($value[$param])) {
                    $data[$key][$param] = $value[$param];
                }
            }
        }

        if (!empty($data)) {
            tb::setDefinitions(self::VALUES_DEF_NAME, $data);
        }
    }


    /**
     * @since 0.9.0
     */
    function enqueueScripts()
    {
        if (!is_customize_preview()) {
            return;
        }

        wp_enqueue_script("resp-cmp-value", $this->getAssetsUri("customizer.js"), ["jquery"], RESP_VERSION, true);
    }


    /**
     * @since 0.9.0
     */
    private static function isImage($mod)
    {
        return (isset($mod["type"]) && $mod["type"] == "image");
    }



    /**
     * @since 0.9.0
     */
    function customizeRegister($wp_customize)
    {

        $data = array_merge_recursive(
            tb::getDefinitions(self::VALUES_DEF_NAME),
            tb::getStatics(self::VALUES_DEF_NAME)
        );

        $exclude = __resp_array_item($data, "exclude", []);

        $sections = __resp_array_item($data, "sections", []);

        $panels = __resp_array_item($data, "panels", []);

        $wp_customize->add_section("resp_customize_values_section", [
            "title"      => esc_html__('Values', "resp"),
            "priority"   => 30,
        ]);

        foreach ($panels as $key => $value) {
            $panel_args = [
                "title"      => esc_html__(__resp_array_item($value, "title", $key), "resp"),
                "description" => esc_html__(__resp_array_item($value, "description", ""), "resp"),
                "priority"   => __resp_array_item($value, "priority", 30)
            ];
            $wp_customize->add_panel($key, array_merge($panel_args, $value));
        }

        foreach ($sections as $key => $value) {
            $section_args = [
                "title"      => esc_html__(__resp_array_item($value, "title", $key), "resp"),
                "description" => esc_html__(__resp_array_item($value, "description", ""), "resp"),
                "priority"   => __resp_array_item($value, "priority", 30)
            ];
            $wp_customize->add_section($key, array_merge($section_args, $value));
        }

        foreach ($data as $key => $value) {

            if (in_array($key, self::VALUES_DEF_PROPS)) {
                continue;
            }

            if (in_array($key, $exclude)) {
                continue;
            }

            if (!__resp_array_item($value, "customizable", false)) {
                continue;
            }

            $has_container = isset($value["container"]);

            $section = __resp_array_item($value, "section", "resp_customize_values_section");

            $wp_customize->add_setting($key, [
                'default'   => $value["value"],
                'transport' => $has_container ? 'postMessage' : 'refresh',
            ]);

            $args = [
                'label'     => __resp_array_item($value, "label", $key),
                'section'   => $section,
                'settings'  => $key
            ];

            foreach (self::CONTROL_OPTIONAL_PARAMS as $oparam) {
                if (isset($value[$oparam])) {
                    $args[$oparam] = $value[$oparam];
                }
            }

            if (isset($value["args"])) {
                $args = array_merge($args, $value["args"]);
            }

            if (isset(self::TYPE_CONTROL_PAIR[$value["type"]])) {
                $name = self::TYPE_CONTROL_PAIR[$value["type"]];
                $wp_customize->add_control(new $name($wp_customize,   $key,  $args));
            } else {
                $args["type"] = $value["type"];
                $wp_customize->add_control(new \WP_Customize_Control($wp_customize,   $key,  $args));
            }

            $scode = __resp_array_item($value, "shortcode", false);

            if ($has_container) {

                if (isset($value["id"])) {
                    $selector = "#" . $value["id"];
                } else {
                    $selector = "#" . $key;
                }

                $options =  [
                    'selector' => $selector
                ];

                if (!$scode) {
                    $options["type"] = "resp_value";
                    $options["container_inclusive"] = false;
                    $options["render_callback"] = "__return_false";
                }

                $wp_customize->selective_refresh->add_partial($key, $options);
            }
        }
    }

    /**
     * @since 0.9.0
     */
    function extractValues()
    {

        $theme_slug = tb::getSlug();

        $data = tb::getDefinitions(self::VALUES_DEF_NAME);

        $staticData = tb::getStatics(self::VALUES_DEF_NAME);

        $allData = array_merge_recursive($data, $staticData);


        foreach ($allData as $key => $value) {

            if (in_array($key, self::VALUES_DEF_PROPS)) {
                continue;
            }


            /* 
            if(Core::option("resp_isolation")){
                // restricts name of the keys
                if (!__resp_str_startsWith($key, "resp-") && !__resp_str_startsWith($key, "$theme_slug-")) {
                    continue;
                }
            }
            */




            if (!is_array($value)) {
                $value = [
                    "value" =>  $value,
                    "action" => true
                ];

                self::$values[$key] = $value;
            }



            if (!isset($value["value"])) {
                continue;
            }



            $val = $value["value"];

            $scode = __resp_array_item($value, "shortcode", false);




            if (!isset(self::$values[$key])) {
                self::$values[$key] = [
                    "label" => __resp_array_item($value, "label", $key),
                    "value" => $val,
                    "type" => "text"
                ];
            }



            foreach (self::VALUE_PARAMS as $param) {

                if (isset($value[$param])) {

                    self::$values[$key][$param] = $value[$param];
                }
            }


            self::checkContainer($value, $val);


            if (isset($value["action"])) {

                $action = $value["action"];

                if (is_bool($action) &&  $action === true) {
                    $action = [$key];
                } else if (!is_array($action)) {
                    $action = [$action];
                }

                array_walk($action, function (&$actItem, $actName) use ($theme_slug) {
                    $actItem = "$actItem-value";

                    if (__resp_str_startsWith($actItem, "*-")) {
                        $actItem = str_replace("*-", "$theme_slug-", $actItem);
                    }
                });

                foreach ($action as $a) {
                    add_filter($a, function ($old_value) use ($key, $value, $val, $scode) {

                        $val = get_theme_mod($key, $val);

                        self::checkContainer($value, $val, $key);

                        $val =  $scode ? do_shortcode($val) : $val;

                        return $old_value . $val;
                    }, (int) __resp_array_item($value, "priority", 10));
                }
            }

            if (isset($value["constant"]) && $value["constant"] === true) {
                define($key, $scode ? do_shortcode($val) : $val);
            }
        }
    }




    /**
     * @since 0.9.0
     */
    private static function checkVariable($name, $type, $operator, $value)
    {


        if ($type == "const") {
            $currentValue = constant($name);
        }

        if ($type == "var") {
            $currentValue = $GLOBALS[$name];
        }

        if ($value === true) {
            $value = 'true';
        }

        if ($value === false) {
            $value = 'false';
        }

        if ($operator == "is") {
            return $value == $currentValue;
        }

        if ($operator == "not") {
            return $value != $currentValue;
        }

        return false;
    }


    /**
     * @since 0.9.0
     */
    function ifShortcode($atts = [], $content = null)
    {

        if (!isset($atts["const"]) && !isset($atts["var"])) {
            return;
        }

        if (!isset($atts["is"]) && !isset($atts["not"])) {
            return;
        }

        if (isset($atts["const"]) && !defined($atts["const"])) {
            return;
        }

        if (isset($atts["var"]) && !isset($GLOBALS[$atts["var"]])) {
            return;
        }

        $operator = isset($atts["is"]) ? "is" : "not";

        $type = isset($atts["const"]) ? "const"  : "var";

        if (self::checkVariable($atts[$type], $type, $operator,  $atts[$operator])) {
            return do_shortcode($content);
        }
    }


    /**
     * @since 0.9.0
     */
    function numberShortcode($atts = [], $content = null)
    {
        $params = array_values($atts);

        return number_format_i18n($params[0], $params[1]);
    }



    /**
     * @since 0.9.0
     */
    private static function checkContainer($mod, &$value, $id_fallback = "")
    {
        if (self::isImage($mod)) {

            $value = Tag::img($value, __resp_array_item($mod, "attr", []))
                ->addClass(__resp_array_item($mod, "class", []))
                ->render();
        } else if (isset($mod["container"]) && !empty($value)) {

            $attr = __resp_array_item($mod, "attr", []);

            if(is_array($attr)){
                array_walk($attr, function (&$item, $key) {
                    $item = apply_filters("resp-core--config-output", $item);
                });
            }

            $value = Tag::create([
                "name" => $mod["container"],
                "class" =>  __resp_array_item($mod, "class", []),
                "id" =>  __resp_array_item($mod, "id",  $id_fallback),
                "attr" => $attr,
                "content" => $value
            ])->render();
        }
    }


    /**
     * @since 0.9.0
     */
    private static function retrieveValue(&$output, $mod_name)
    {

        if (!isset(self::$values[$mod_name])) {
            /* translators: %s is replaced with "string" */
            __resp_error(sprintf(esc_html__("Value is not defined: %s", "resp"), $mod_name));
            return;
        }

        $mod = self::$values[$mod_name];

        $output = get_theme_mod($mod_name, $mod["value"]);

        /*
        if(__resp_array_item($mod , "constant" , false) === true){
            $output = constant($mod_name);
        } else {
            $output = get_theme_mod($mod_name, $mod["value"]);
        }

        if ($output != $mod["value"] && !is_customize_preview()) {

             value is changed in the editor
             set_theme_mod($mod_name, $mod["value"]);

             $output = $mod["value"];
        }
        */

        if (isset($mod["shortcode"]) && $mod["shortcode"] === true) {
            $output = do_shortcode($output, false);
        }

        $output = apply_filters("resp-core--config-output", $output);
    }


    /**
     * @since 0.9.0
     */
    private static function convert(&$value, $as, $to, $content,  $ignore_html)
    {

        global $post;

        if ("ID" === strtoupper($as)) {
            if ("ATTACHMENT_URL" === strtoupper($to)) {
                $value = wp_get_attachment_url($value);
            }
            if ("POST" === strtoupper($to)) {
                $post = get_post($value);
                if (isset($post)) {
                    setup_postdata($post);
                    $value = do_shortcode($content, $ignore_html);
                }
                wp_reset_postdata();
            }
        }
    }


    /**
     * @since 0.9.0
     */
    private static function hasConversion($mod_name)
    {
        $mod = (self::getInstance("ThemeValues"))->getValue($mod_name);

        return isset($mod["as"]) && isset($mod["to"]);
    }


    /**
     * @since 0.9.0
     */
    function valueShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts([
            "name" => null,
            "to" => null,
            "as" => null,
            "var" => null,
            "const" => null,
            "is" => null,
            "not" => null,
            "ignore_html" => false
        ], $atts));

        if (isset($var) || isset($const)) {

            $type = isset($const) ? "const"  : "var";

            if (isset($is) || isset($not)) {

                $operator = isset($is) ? "is" : "not";

                if (self::checkVariable($atts[$type], $type, $operator,  $atts[$operator])) {
                    return do_shortcode($content);
                } else {
                    return;
                }
            } else {

                if ($type == "const") {
                    return constant($atts[$type]);
                }

                return $GLOBALS[$atts[$type]];
            }
        }

        if (!isset($name)) {
            return;
        }

        $value = null;

        $states = __resp_get_states();

        $realName = __resp_esc_state($name);


        // get the value
        foreach ($states as $state) {
            if (!empty($state)) {
                $state = ":$state";
            }
            self::retrieveValue($value, "{$realName}{$state}");
        }


        if (empty($value)) {
            return;
        }


        if (isset($to) && isset($as)) {
            self::convert($value, $as, $to, $content,  $ignore_html);
        } else if (self::hasConversion($name)) {
            self::convert($value, self::$values[$name]["as"], self::$values[$name]["to"], $content,  $ignore_html);
        }

        // check for container
        self::checkContainer(self::$values[$name], $value, is_customize_preview() ? $name : "");

        return $value;
    }
}
