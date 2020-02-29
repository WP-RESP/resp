<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp;

defined("RESP_TEXT_DOMAIN") or die;

class FileManager
{

    private static $paths = [];


     /**
     * @since 0.9.0
     */
    static function getDefinedPaths(){
       return self::$paths;
    }


    /**
     * @since 0.9.0
     */
    static function definePath($keys , $path , $fallback = null){
        if(!is_array($keys)){
            $keys = [$keys];
        }
        foreach($keys as $key){
            self::$paths[$key] = [
                "path" => $path,
                "fallback" => $fallback
            ];
        }
    }


    /**
     * @since 0.9.0
     */
    static function fixUndefinedExtension(&$path , $ext)
    {
        $pathInfo = pathinfo($path);
        if(!isset($pathInfo["extension"])){
            $path = "$path/{$pathInfo['basename']}.$ext";
        }
    }


    /**
     * @since 0.9.0
     */
    static function useDefinedPaths(&$path , $fallback = false){

        foreach(self::$paths as $key => $value)
        {

            if(!__resp_str_startsWith($path , $key)){
                continue;
            }


            $val = $value;

            if(!is_array($val)){
                $val = [
                    "path" => $val
                ];
            }

            if(!isset($val["path"]))
            {
                continue;
            }
            

            $temp = str_replace($key, $val["path"], $path);

            if(!file_exists($temp) && $fallback && isset($val["fallback"])){
                $temp = str_replace($key, $val["fallback"], $path);
            }

            $path = $temp;
        }
    }



    /**
     * @since 0.9.0
     */
    static function getRespContentDirectoryPath($name, $public = false,  $mkdir = false)
    {

        $bid = get_current_blog_id();

        $path = path_join(WP_CONTENT_DIR, "resp-{$name}");

        if (!$public) {
            $path = path_join($path, $bid);
        }

        if ($mkdir && !file_exists($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }


    /**
     * @since 0.9.0
     */
    static function getRespContentDirectoryUri($name, $root = false)
    {

        $url = self::uriJoin(WP_CONTENT_URL, "resp-{$name}");

        if (!$root) {
            $bid = get_current_blog_id();
            $url = self::uriJoin($url, $bid);
        }

        return $url;
    }


    /**
     * @since 0.9.0
     */
    static function getRespAssetsDirectoryUri($path = "")
    {
        return self::getRespDirectoryUri("assets", $path);
    }


     /**
     * @since 0.9.0
     */
    static function getRespTemplatesDirectoryUri($path = "")
    {
        return self::getRespDirectoryUri("templates", $path);
    }



    /**
     * @since 0.9.0
     */
    static function getRespComponentsDirectoryUri($path = "")
    {
        return self::getRespDirectoryUri("components", $path);
    }


    /**
     * @since 0.9.0
     */
    static function getRespAddonsDirectoryUri($path = "")
    {
        return self::getRespDirectoryUri("addons", $path);
    }


    /**
     * @since 0.9.0
     */
    static function getRespDirectoryUri(...$args)
    {
        $base = parse_url(get_template_directory_uri(), PHP_URL_PATH);

        return self::uriJoin(...array_merge([$base], $args));
    }


    /**
     * @since 0.9.0
     */
    static function getRespDirectory(...$args)
    {
        return self::pathJoin(...array_merge([get_template_directory()], $args));
    }


    /**
     * @since 0.9.0
     */
    static function uriJoin(...$args)
    {
        $result = "";

        foreach ($args as $path) {
            if (empty($result)) {
                $result = $path;
            } else {
                $result =  rtrim($result, '/') . '/' . ltrim($path, '/');
            }
        }

        return $result;
    }


    /**
     * @since 0.9.0
     */
    static function pathJoin(...$args)
    {
        $result = "";

        foreach ($args as $path) {
            $result = path_join($result, $path);
        }

        return $result;
    }
}
