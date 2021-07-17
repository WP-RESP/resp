<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

defined('RESP_VERSION') or die;

class FileManager
{

    private static $paths = [];


    /**
     * @since 0.9.0
     */
    static function getDefinedPaths()
    {
        return self::$paths;
    }

    /**
     * @since 0.9.5
     */
    static function pathToUrl($path)
    {
        return str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $path);
    }

    /**
     * @since 0.9.5
     */
    static function urlToPath($path)
    {
        return str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $path);
    }

    /**
     * @since 0.9.0
     */
    static function definePath($keys, $path, $fallback = null)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            self::$paths[$key] = [
                "path" => $path,
                "fallback" => $fallback
            ];
        }
    }


    /**
     * @since 0.9.0
     */
    static function fixUndefinedExtension(&$path, $ext)
    {
        $pathInfo = pathinfo($path);
        if (!isset($pathInfo["extension"])) {
            $path = "$path/{$pathInfo['basename']}.$ext";
        }
    }


    /**
     * @since 0.9.0
     */
    static function useDefinedPaths(&$path, $fallback = false)
    {

        foreach (self::$paths as $key => $value) {

            if (!__resp_str_startsWith($path, $key)) {
                continue;
            }


            $val = $value;

            if (!is_array($val)) {
                $val = [
                    "path" => $val
                ];
            }

            if (!isset($val["path"])) {
                continue;
            }


            $temp = str_replace($key, $val["path"], $path);

            if (!file_exists($temp) && $fallback && isset($val["fallback"])) {
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
        $pathInfo = parse_url(get_template_directory_uri());

        $base = $pathInfo["scheme"] . "://" . $pathInfo["host"] . "/";

        return self::uriJoin(...array_merge([$base, $pathInfo["path"]], $args));
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
    static function getTotalSize($dir)
    {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');

        if (is_dir($dir) === true) {
            $totalSize = 0;
            $os        = strtoupper(substr(PHP_OS, 0, 3));
            // If on a Unix Host (Linux, Mac OS)
            if ($os !== 'WIN') {
                $io = popen('/usr/bin/du -sb ' . $dir, 'r');
                if ($io !== false) {
                    $totalSize = intval(fgets($io, 80));
                    pclose($io);
                    return $totalSize;
                }
            }
            // If on a Windows Host (WIN32, WINNT, Windows)
            if ($os === 'WIN' && extension_loaded('com_dotnet')) {
                $obj = new \COM('scripting.filesystemobject');
                if (is_object($obj)) {
                    $ref       = $obj->getfolder($dir);
                    $totalSize = $ref->size;
                    $obj       = null;
                    return $totalSize;
                }
            }
            // If System calls did't work, use slower PHP 5
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }
            return $totalSize;
        } else if (is_file($dir) === true) {
            return filesize($dir);
        }
    }

    /**
     * @since 0.9.0
     */
    static function convertToBytes(string $from): ?int {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from,-2));
    
        //B or no suffix
        if(is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }
    
        $exponent = array_flip($units)[$suffix] ?? null;
        if($exponent === null) {
            return null;
        }
    
        return $number * (1024 ** $exponent);
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
