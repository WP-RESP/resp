<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder as tb, Resp\Core;

defined('RESP_VERSION') or die;

defined("RESP_VERSION") or die;

class Classifier extends Component
{

    private static $aliases = [];

    private $default_classes;

    const CLASSES_DEf_NAME = "classes";

    const SPECIAL_TAGS = ["html", "body", "header", "footer", "main", "aside", "section"];

    const SPECIAL_PREFIX = ["archive", "page"];


    function __construct()
    {

        $class = get_called_class();

        add_action("resp-themebuilder-build", "$class::extractClasses", 10);
        add_filter("resp-core--tag-attributes", "$class::filterAttributes", 10, 1);
        add_filter("resp-core--tag-classes", "$class::filterClasses", 10, 1);
    }


    /**
     * @since 0.9.0
     */
    static function localizeAliases()
    {

        if (empty(self::$aliases)) {
            return;
        }

        $aliases = self::$aliases;

        add_filter("resp-localize-script", function ($dt) use ($aliases) {
            $dt["alias"] = $aliases;
            return $dt;
        });
    }



    /**
     * @since 0.9.0
     */
    static function extractClasses()
    {

        $data = tb::getDefinitions(self::CLASSES_DEf_NAME);

        $staticData = tb::getStatics(self::CLASSES_DEf_NAME);

        $themeSlug = tb::getSlug();

        foreach (array_merge_recursive($data, $staticData) as $key => $value) {

            if (
                !__resp_str_startsWith($key, "*-") &&
                !__resp_str_startsWith($key, "$themeSlug-") &&
                !in_array($key, self::SPECIAL_TAGS) &&
                !__resp_hasItemWithPrefix(self::SPECIAL_PREFIX, $key)
            ) {

                if (is_array($value) || is_string($value)) {
                    self::$aliases[$key] = is_array($value) ? $value : [$value];
                }

                continue;
            }

            $filter = "$key-classes";

            if(__resp_str_startsWith($filter , "*-")){
                $filter = str_replace("*-" , "$themeSlug-" , $filter );
            }

            add_filter($filter, function ($classes) use ($value) {
                $val = is_array($value) ? $value : [$value];

                if (is_array($classes)) {
                    $classes = __resp_array_merge($classes, $val);
                } else {
                    $classes = sprintf("%s %s", $classes, implode(" ", $val));
                }
                return $classes;
            });
        }

        self::localizeAliases();
    }


    /**
     * @since 0.9.0
     */
    static function filterClasses($data)
    {

        global $page_namespace, $section_prefix;

        $role = $data["role"];

        $smClass = "$section_prefix--$role";

        $gbClass = $role;

        Core::chkIsolation($smClass , "-");
        Core::chkIsolation($gbClass , "--");

        if (Core::option("resp_tags_default_classes")) {
            $classes = $data["class"];

            if (!is_array($classes)) {
                $classes = [$classes];
            }

            $class = __resp_array_merge(["$page_namespace--$role", $gbClass], (empty($section_prefix) ? [] : [$smClass]), $classes);
        } else {
            $class = $data["class"];
        }

        array_walk($class, function(&$item){
            $item = __resp_esc_state($item);
        });

        $class_filters = [];

        foreach(__resp_get_states() as $state){
            self::mergeState($class_filters , $role , $state , $gbClass , $smClass , $page_namespace );
        }

        foreach ($class_filters as $filter) {
            $class = apply_filters($filter, $class);
        }

        return ["class" => $class, "role" => $role];
    }

    /**
     * @since 0.9.3
     */
    private static function mergeState(&$class_filters , $role , $state , $gbClass , $smClass , $page_namespace){

        if(!empty($state)){
            $state = ":$state";
        }

        $class_filters = __resp_array_merge($class_filters, [
            "{$gbClass}{$state}-classes", 
            "$page_namespace--{$role}{$state}-classes"
        ], (empty($section_prefix) ? [] : ["$smClass{$state}-classes"]));

        if (in_array($role, self::SPECIAL_TAGS)) {
            $class_filters[] = "$role{$state}-classes";
        }

    }


    /**
     * @since 0.9.0
     */
    static function filterAttributes($data)
    {

        global $page_namespace, $section_prefix;

        $role = $data["role"];

        $atts = $data["attr"];

        if (!current_user_can("update_core")) {
            return $data;
        }

        if (!Core::option("resp_development_mode")) {
            return $data;
        }
        
        $smClass = "$section_prefix--$role";

        $gbClass = $role;

        Core::chkIsolation($smClass , "-");

        Core::chkIsolation($gbClass , "--");


        $class_filters =  __resp_array_merge(["$gbClass-classes", "$page_namespace--$role-classes"], (empty($section_prefix) ? [] : ["$smClass-classes"]));

        if (in_array($role, self::SPECIAL_TAGS)) {
            $class_filters[] = "$role-classes";
        }

        $atts = __resp_array_merge($atts, [
            "data-class-filters" => implode(" ", $class_filters)
        ]);

        return [
            "attr" => $atts,
            "role" => $role
        ];
    }
}
