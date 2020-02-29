<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use Resp\Component, Resp\FileManager as fm, Resp\ThemeBuilder as tb;

defined("RESP_TEXT_DOMAIN") or die;

class AssetManager extends Component
{

    const PACKAGES_DEF_NAME = "packages";

    const PATHS_DEF_NAME = "paths";

    const VIEW_FILE_EXTENSION = "html";

    const CONFIG_VERSION_PARAMS = ["v" , "ver" , "version"];

    const CONFIG_MEDIA_PARAMS = ["m" , "media"];

    const CONFIG_DEPENDENCIES_PARAMS =  ["d" , "deps" , "dependency" , "dependencies"];
    


    private static $styles = [];

    private static $scripts = [];


    function __construct()
    {
        add_action("resp-themebuilder-build", [$this, "extractPackages"]);
        add_action("resp-themebuilder-build", [$this, "extractPaths"]);
        add_action("wp_enqueue_scripts", [$this, "registerPackages"]);
        add_shortcode('resp-asset', [$this, 'assetShortcode']);
        add_shortcode("resp-template", [$this, "templateShortcode"]);
    }



    /**
     * @since 0.9.0
     */
    function extractPaths(){

        foreach(array_merge( 
            tb::getData(self::PATHS_DEF_NAME),
            tb::getExternalData(self::PATHS_DEF_NAME)
        ) as $key => $value){

            if($key !== "$" || !__resp_str_startsWith( $key , "@")){
                continue;
            }

            if(is_string($value))
            {
                
                fm::definePath($key , $value);

            } else if(isset($value["path"])) {

                fm::definePath($key , $value["path"] , __resp_array_item($value , "fallback" , null));

            }

        }

    }



    /**
     * @since 0.9.0
     */
    function extractPackages()
    {
        $data = array_merge_recursive( 
            tb::getData(self::PACKAGES_DEF_NAME),
            tb::getExternalData(self::PACKAGES_DEF_NAME)
        );

        self::$styles =  __resp_array_item($data, "styles", []);

        self::$scripts = __resp_array_item($data, "scripts", []);
    }


    /**
     * @since 0.9.0
     */
    function registerPackages()
    {
        $this->enqueueStyles();
        $this->enqueueScripts();
    }


    /**
     * @since 0.9.0
     */
    private static function getAssetsDirUri()
    {
        $assetsDir = fm::getRespContentDirectoryUri("assets");
        $base = parse_url($assetsDir, PHP_URL_PATH);
        return  $base;
    }


    /**
     * @since 0.9.0
     */
    private static function getTemplatesDirUri()
    {
        $templatesDir = fm::getRespContentDirectoryUri("templates");
        $base = parse_url($templatesDir, PHP_URL_PATH);
        return  $base;
    }



    /**
     * @since 0.9.0
     */
    private static function checkFileURI(&$src)
    {
        $param = 0;

        $hasDollarSign = __resp_str_startsWith($src, "$");

        if ($hasDollarSign || __resp_str_startsWith($src, "@assets")) {
            $path = $hasDollarSign ?  ltrim($src, "$") : str_replace("@assets", "", $src);
            $base = self::getAssetsDirUri();
            $dir =  fm::getRespContentDirectoryPath("assets");
            $param = 1;
        }

        if (__resp_str_startsWith($src, "@templates")) {
            $path = str_replace("@templates", "", $src);
            $dir =  fm::getRespContentDirectoryPath("templates");
            $base = self::getTemplatesDirUri();
            $param = 2;
        }

        if ($param == 0) {
            return;
        }

        $file = fm::pathJoin($dir, ltrim($path, "\/"));

        if (file_exists($file)) {
            $src = fm::uriJoin($base, $path);
        } else {
            switch ($param) {
                case 1:
                    $src = fm::getRespAssetsDirectoryUri($path);
                    break;
                case 2:
                    $src = fm::getRespTemplatesDirectoryUri($path);
                    break;
            }
        }
    }


    /**
     * @since 0.9.0
     */
    private function enqueueStyles()
    {

        foreach (self::$styles as $key => $value) {

            $src = __resp_array_item($value, "src", "");

            if (empty($src)) {
                continue;
            }

            if (wp_style_is($key, "enqueued")) {
                wp_dequeue_style($key);
            }

            if (wp_style_is($key, "registered")) {
                wp_deregister_style($key);
            }

            fm::fixUndefinedExtension($src , "css");

            self::checkFileURI($src);

            wp_enqueue_style(
                $key,
                $src,
                __resp_array_item($value , self::CONFIG_DEPENDENCIES_PARAMS , []),
                __resp_array_item($value , self::CONFIG_VERSION_PARAMS , tb::getVersion()),
                __resp_array_item($value , self::CONFIG_MEDIA_PARAMS , "all")
            );
        }
    }

    /**
     * @since 0.9.0
     */
    private function enqueueScripts()
    {

        foreach (self::$scripts as $key => $value) {

            $src = __resp_array_item($value, "src", "");

            if (empty($src)) {
                continue;
            }

            if (wp_script_is($key, "enqueued")) {
                wp_dequeue_script($key);
            }

            if (wp_script_is($key, "registered")) {
                wp_deregister_script($key);
            }

            fm::fixUndefinedExtension($src , "js");

            self::checkFileURI($src);

            wp_enqueue_script(
                $key,
                $src,
                __resp_array_item($value , self::CONFIG_DEPENDENCIES_PARAMS , []),
                __resp_array_item($value , self::CONFIG_VERSION_PARAMS , tb::getVersion()),
                __resp_array_item($value , "in_footer" , false)
            );
        }
    }

    /**
     * @since 0.9.0
     */
    function assetShortcode($atts = [], $content = null)
    {

        $url = __resp_array_item($atts, "url", null);

        $image = __resp_array_item($atts, "image", false);


        if (!isset($url)) {
            return;
        }


        $file =  fm::pathJoin(fm::getRespContentDirectoryPath("assets"), $url);


        if (file_exists($file)) {

            $base = self::getAssetsDirUri();

            $src = fm::uriJoin($base, $url);
        } else {

            // get file from the theme directory
            $src = fm::getRespAssetsDirectoryUri($url);
        }



        if ($image) {

            return \Resp\Tag::img($src, [
                "id" => __resp_array_item($atts, "id", ""),
                "class" => __resp_array_item($atts, "class", []),
                "width" => __resp_array_item($atts, "width", null),
                "height" => __resp_array_item($atts, "height", null),
                "alt" => __resp_array_item($atts, "alt", "")
            ])->render(false);
        }

        return $src;
    }



    /**
     * @since 0.9.0
     */
    function templateShortcode($atts = [], $content = null)
    {

        if (!isset($atts["name"])) {
            return;
        }

        $name = $atts["name"];

        if(!__resp_str_startsWith($name , "@templates/")){
            $name = "@templates/$name";
        }

        fm::fixUndefinedExtension($name, self::VIEW_FILE_EXTENSION);

        fm::useDefinedPaths($name , true);

        $info = pathinfo($name);

        if(!in_array($info["extension"] , ["html" , "htm" , "temp" , "tmp"] )){
            return;
        }

        if(!file_exists($name)){
            print_r($name);
            return;
        }

        $temp = file_get_contents($name);

        return do_shortcode($temp, false);
    }
}
