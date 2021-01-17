<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\Tag, \Resp\FileManager as fm , \Resp\Communicator as com;

defined('RESP_VERSION') or die;

class ThemeBuilder
{

    const DATA_PARAM_INC = "includes";

    const DATA_PARAM_DEFS = "schema";

    const DATA_OPT_NAME = "resp_theme_data";

    const CONFIG_FILE_EXTENSION = "json";

    


    private static $data = [];
    private static $staticData = [];



    /**
     * @since 0.9.0
     */
    static function load($json = null)
    {

        $json_data = $json == null ? self::retrieveJsonData() : $json;

        self::$data = json_decode($json_data, true);

        self::extractStaticData(self::$data);

        do_action("resp-themebuilder-build", self::$data);

        if ($json != null && self::$data != null) {
            update_option(self::DATA_OPT_NAME, $json);
        }
    }


    /**
     * @since 0.9.0
     */
    static function addStaticData($data)
    {

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if(!$data)
        {
            return;
        }

        self::$staticData = array_merge_recursive(self::$staticData, $data);

    }


    /**
     * @since 0.9.0
     */
    static function chkForPartials(&$path){
        if (__resp_str_startsWith($path, "@partials/")) {
            $slug = self::getSlug();
            $path = str_replace("@partials/" , "@templates/$slug/partials/" ,$path);
        }
    }


    /**
     * @since 0.9.0
     */
    static function extractStaticData($data)
    {

        $data = __resp_array_item($data, self::DATA_PARAM_INC, []);

        $data = array_map(function ($path) {

            $newPath = $path;

            self::chkForPartials($newPath);

            fm::fixUndefinedExtension($newPath , "json");
            fm::useDefinedPaths($newPath , true);

            return $newPath;

        }, $data);

        foreach ($data as $path) {

            if (!file_exists($path)) {
                com::notice(sprintf(
                     /* translators: %s is replaced with "string" */
                    esc_html__( "File not Found \"%s\"", "resp") , $path ) , "error");
                continue;
            }

            self::addStaticData(file_get_contents($path));

        }
    }


    /**
     * @since 0.9.0
     */
    static function getExternalData($key = null)
    {
        if (isset($key)) {

            return __resp_array_item(self::$staticData, $key, []);

        } else {

            return self::$staticData;
            
        }
    }


    /**
     * @since 0.9.0
     */
    static function getStatics($key = null)
    {
        $data = self::$staticData;

        if (!isset($data[self::DATA_PARAM_DEFS])) {
            return [];
        }

        if (isset($key)) {
            return __resp_array_item($data[self::DATA_PARAM_DEFS], $key, []);
        }

        return $data[self::DATA_PARAM_DEFS];
    }



    /**
     * @since 0.9.0
     */
    static function getDefinitions($key = null)
    {

        $data = self::getData();

        if (!isset($data[self::DATA_PARAM_DEFS])) {
            return [];
        }

        if (isset($key)) {

            return __resp_array_item($data[self::DATA_PARAM_DEFS], $key, []);
        }

        return $data[self::DATA_PARAM_DEFS];
    }

    /**
     * @since 0.9.0
     */
    static function setDefinitions($name, $value)
    {

        self::$data[self::DATA_PARAM_DEFS][$name] = $value;

        self::update_data();
    }


    /**
     * @since 0.9.0
     */
    static function getData($key = null)
    {

        $result = [];

        $data = self::$data;

        $data = apply_filters("resp-themebuilder-data",  $data);

        if (isset($key)) {

            if (isset($data[$key])) {

                $result = apply_filters("resp-themebuilder-data-{$key}", $data[$key]);
            }
        } else if (isset($data)) {

            $result = $data;
        }

        return $result;
    }



    /**
     * @since 0.9.0
     */
    static function retrieveJsonData($direct = false)
    {

        $json = "";

        $option = get_option(self::DATA_OPT_NAME, '');

        $data = json_decode($option, true);

        if (empty($option)) {

            $default_config = get_template_directory() . "/config.json";

            if (file_exists($default_config)) {
                /*
                com::info(sprintf(
                    esc_html__("Default configuration is loaded. You can edit it <a href=\"%s\">here</a>.", "resp"),
                    menu_page_url(RESP_OPTION_GROUP, false) . "&tab=edit"
                ));
                */
                return file_get_contents($default_config);

            } else {

                com::critical(esc_html__("Unable to find \"config.json\".", "resp"));

            }
        } else {

            if ($direct) {
                return  $option;
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($json == null || $data == null) {
            com::notice(esc_html__("Unable to parse data.", "resp"), "error");
            return $option;
        }

        return $json;
    }

    /**
     * @since 0.9.0
     */
    private static function update_data()
    {
        update_option(self::DATA_OPT_NAME, json_encode(self::$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    
    /**
     * @since 0.9.0
     */
    static function getName()
    {
        return __resp_array_item(self::$data , "name" , "Resp");
    }


    /**
     * @since 0.9.0
     */
    static function getSlug()
    {
        return sanitize_title(self::getName());
    }



    /**
     * @since 0.9.0
     */
    static function getVersion()
    {
        return __resp_array_item(self::$data , "version" , RESP_VERSION);
    }
}
