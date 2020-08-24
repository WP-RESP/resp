<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp\Components;

use Resp\Component, Resp\Tag, Resp\ThemeBuilder as tb, Resp\ThemeOptions;

class ColorPalette extends Component
{

    const COLORS_DEF_PROPS = [
        "exclude"
    ]; 

    const COLORS_DEF_NAME = "colors";

    const DEFAULT_SET = [
        [
            "selector" => ".$-text-color",
            "params" => [
                "color" => "$"
            ]
        ],
        [
            "selector" => ".$-bg-color",
            "params" => [
                "background-color" => "$"
            ]
        ],
        [
            "selector" => ".$-border",
            "params" => [
                "border" => "1px solid $"
            ]
        ]
    ];

    private static $colors = [];


    function __construct()
    {
        add_action('wp_head', [$this, 'themeColorsCSS']);
        add_action('customize_register', [$this, 'customizeRegister']);
        
        //add_action('customize_save_after',  [$this,  'theme_colors_customize_saved']);

        add_action("resp-themebuilder-build", [$this, 'extractColors'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], PHP_INT_MAX);
        add_shortcode('resp-color', [$this, 'color_shortcode']);
        add_filter("resp-localize-script", [$this, 'customizeColorsData'], 10, 1);


        if (ThemeOptions::is_dashboard()) {
            add_action("resp-dashboard-after-content", [$this, "dashboardView"]);
            add_action("resp-dashboard-enqueue-scripts", [$this, "dashboardScripts"]);
        }
    }


    /**
     * @since 0.9.0
     */
    function theme_colors_customize_saved()
    {

        $data = tb::getDefinitions(self::COLORS_DEF_NAME);

        foreach ($data as $key => $value) {

            if (!is_array($value)) {
                $value = ["value" =>  $value];
            }

            if (!isset($value["value"])) {
                continue;
            }

            $val = get_theme_mod("resp-color-$key", $value["value"]);

            $data[$key] = [
                "label" => isset($value["label"]) ? $value["label"] : $key,
                "value" => $val
            ];

            if (isset($value["description"])) {

                $data[$key]["description"] = $value["description"];
            }

            if (isset($value["styles"])) {
                $data[$key]["styles"] = $value["styles"];
            }
        }

        if (!empty($data)) {
            tb::setDefinitions(self::COLORS_DEF_NAME, $data);
        }
    }

    /**
     * @since 0.9.0
     */
    function customizeColorsData($data)
    {
        if (!is_customize_preview()) {
            return $data;
        }

        $data["colors"] = self::$colors;

        return $data;
    }


    /**
     * @since 0.9.0
     */
    function enqueueScripts()
    {
        if (!is_customize_preview()) {
            return;
        }
        wp_enqueue_script("resp-cmp-color", $this->getAssetsUri("customizer.js"), ["jquery"], RESP_VERSION, true);
    }


    /**
     * @since 0.9.0
     */
    private function getAsCssData($convSelector = true, $convParams = true)
    {
        $result = [];

        foreach (self::$colors as $name => $prop) {

            if (!isset($prop["styles"])) {
                continue;
            }

            foreach ($prop["styles"] as $style) {

                if (!isset($style["selector"]) || !isset($style["params"])) {
                    continue;
                }


                // Get Parameters
                $params = $style["params"];

                if ($convParams) {
                    foreach ($params as $key => $value) {
                        if (in_array($key, ["content"])) {
                            continue;
                        }
                        $params[$key] = str_replace("$", $this->getColor($name), $value);
                    }
                }


                // Get Selectors
                $selector = $style["selector"];

                if (is_array($selector)) {

                    foreach ($selector as $s) {

                        if ($convSelector) {
                            $s = str_replace("$", sanitize_title($name), $s);
                        }

                        $result[$s] = __resp_array_item($result, $s, []);

                        $result[$s] = array_merge($result[$s], $params);
                    }
                } else {

                    if ($convSelector) {
                        $selector = str_replace("$", sanitize_title($name), $selector);
                    }

                    $result[$selector] = __resp_array_item($result, $selector, []);

                    $result[$selector] = array_merge($result[$selector], $params);
                }
            }
        }

        return $result;
    }

    /**
     * @since 0.9.0
     */
    function themeColorsCSS()
    {
        Tag::style(Tag::css($this->getAsCssData()))->set([
            "id" => "resp-colors"
        ])->e();
    }

    /**
     * @since 0.9.0
     */
    function dashboardScripts()
    {
        wp_enqueue_style("resp-color-palette", $this->getAssetsUri("color-palette.min.css"), ['resp-admin'], RESP_VERSION, "all");
    }


    /**
     * @since 0.9.0
     */
    function dashboardView()
    {
        if (empty(self::$colors)) {
            return;
        }

        Tag::create([
            "class" => ["resp-notice-info", "two-column"]
        ])->eo();

        Tag::create(["class" => ["first"]])->eo();

        ThemeOptions::info(
            __("To customize colors, go to <b>Appearance</b> &gt; <b>Themes</b> page. On this page, find the active theme (<b>RESP</b> in our case) and click on the <b>Customize</b> button next to its title, there you can change colors.", RESP_TEXT_DOMAIN)
        )->e();

        Tag::close();


        Tag::create(["class" => ["second" , "flex-center" ,  "color-palette"]])->eo();

        foreach (self::$colors as $key => $value) {

            $val = $value["value"];

            Tag::create([
                "class" => "color",
                "attr" => [
                    "style" => [
                        "background-color" => $val
                    ]
                ]
            ])
            //->append(Tag::span("$key: <b>$val</b>"))
            ->e();
            
        }

        Tag::close();

        Tag::close();
    }


    /**
     * @since 0.9.0
     */
    function customizeRegister($wp_customize)
    {

        foreach (self::$colors as $name => $prop) {

            $setting = "resp-color-$name";

            $transport = __resp_array_item($prop, "transport", "postMessage");

            $wp_customize->add_setting($setting, [
                'default'   => __resp_array_item($prop, "value", "unset"),
                'transport' => $transport,
            ]);

            $args = [
                'label'     => $prop["label"],
                'section'   => 'colors',
                'settings'   => $setting
            ];

            if (isset($prop["description"])) {
                $args["description"] = $prop["description"];
            }

            $wp_customize->add_control(
                new \WP_Customize_Color_Control($wp_customize, $name, $args)
            );
        }
    }

    /**
     * @since 0.9.0
     */
    function extractColors()
    {

        $data = array_merge_recursive(
            tb::getDefinitions(self::COLORS_DEF_NAME),
            tb::getStatics(self::COLORS_DEF_NAME)
        );

        $exclude = __resp_array_item($data , "exclude" , []);

        foreach ($data as $key => $value) {

            if (in_array($key, self::COLORS_DEF_PROPS)) {
                continue;
            }

            if(in_array($key , $exclude)){
                continue;
            }

            if (!is_array($value)) {
                $value = ["value" =>  $value];
            }

            self::$colors[$key] = [
                "label" => isset($value["label"]) ? $value["label"] : $key,
                "value" => __resp_array_item($value, "value", "unset")
            ];

            $mod_name = "resp-color-$key";
            self::$colors[$key]["value"] = get_theme_mod($mod_name, self::$colors[$key]["value"]);

            foreach (["description", "transport", "styles"] as $prop) {
                if (isset($value[$prop])) {
                    self::$colors[$key][$prop] = $value[$prop];
                }
            }

        }


        

    }

    /** 
     * @since 0.9.0
     */
    private function getColor($name)
    {

        $mod_name = "resp-color-$name";

        if (!isset(self::$colors[$name])) {
            return "unset";
        }

        $scode = __resp_array_item(self::$colors[$name], "shortcode", false) == true;

        $value =  self::$colors[$name]["value"];

        $color = get_theme_mod($mod_name, $value);

        /*
        if ($color != $value && !is_customize_preview()) {

            color is changed in the editor
            set_theme_mod($mod_name, $value);

            return $scode ?  do_shortcode($value) : $value;
        }
        */

        return $scode ? do_shortcode($color) : $color;
    }

    /**
     * the color shortcode callback
     * @since 0.9.0
     */
    function color_shortcode($atts = [], $content = null)
    {
        $names = array_values($atts);

        if (count($names) === 0) {
            return;
        }

        return $this->getColor($names[0]);
    }
}
