<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */


defined('ABSPATH') or die;

/** 
 * @since 0.9.0
 */
function __resp_init()
{

    define("RESP_MAIN_SERVER", "https://wp-resp.com");
    define("RESP_OPTION_GROUP", "resp");
    define("RESP_VERSION", "0.9.2");

    foreach ([
        "base/DOMHandlers" ,
        "base/FileManager" , 
        "base/Tag", 
        "base/Component",  
        "ThemeBuilder", 
        "walkers/RespWalkerComment",
        "ConstantLoader",
        "AdvancedSettings",
        "ThemeOptionWrapper",
        "ThemeConfigPlaceholder",
        "ThemeOptions", 
        "Communicator",
        "Core"
        ] as $source) {
            $path = __DIR__ .  "/includes/{$source}.php";

            if(file_exists($path)){
                require_once $path;
            }
            
    }

    \Resp\Core::run();

}


function __resp_array_merge(...$array){

    $result = [];

    foreach($array as $a){
        $result = array_merge($result , $a);
    }

    return $result;

}


/**
 * @since 0.9.0
 */
function __resp_get_cmp_instance($component)
{
    return \Resp\Core::getComponent($component);
}


/**
 * @since 0.9.0
 */
function __resp_array_item($array, $item, $default = null)
{
    if (!isset($array)) {
        return $default;
    }

    if(!is_array($item))
    {
        $item = [$item];
    }

    $result = $default;

    foreach($item as $i){
        if(isset($array[$i]))
        {
            $result = $array[$i];
        }
    }

    return $result;
}



/**
 * @since 0.9.0
 */
function __resp_str_startsWith($haystack, $needle)
{

    $length = strlen($needle);

    return (substr($haystack, 0, $length) === $needle);
}



/**
 * @since 0.9.0
 */
function __resp_db_error()
{
    global $wpdb;

    if (defined("WP_DEBUG") && WP_DEBUG && !empty($wpdb->last_error)) {
        echo "<pre><code>{$wpdb->last_error}</code></pre>";
    }
}




/**
 * @since 0.9.0
 */
function __resp__endsWith($haystack, $needle)
{
    $length = strlen($needle);

    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}


/**
 * @since 0.9.0
 */
function __resp_hasItemWithPrefix($array, $prefix)
{

    return !empty(array_filter($array, function ($item) use ($prefix) {

        return __resp_str_startsWith($prefix, $item);
    }));
}

/**
 * @since 0.9.0
 */
function __resp_master_sidebar_disabled($name)
{
    global $page_namespace;

    $nosidebar = isset($_REQUEST['no{$name}']) || __resp_tp("no-{$name}" , false);

    $nomaster = isset($_REQUEST['nomaster']) || __resp_tp("no-master" , false);

    return apply_filters( "resp--master-sidebar-disabled" , $nomaster || $nosidebar );
}


/**
 * @since 0.9.0
 */
function __resp_error($message)
{
    if (defined("WP_DEBUG") && WP_DEBUG) {
        \Resp\Tag::p($message)->e();
        \Resp\Tag::hr();
    }
}

/**
 * @since 0.9.0
 */
function __resp_tp($name , $default)
{
    return \Resp\Core::getThemeParameter($name , $default);
}


/**
 * @since 0.9.0
 */
function __resp_wp_notice($message, $type = "info", $dismissible = true)
{
    \Resp\Communicator::notice($message , $type , $dismissible);
}

/**
 * @since 0.9.2
 */
function __resp_register_parser($tag , $shortcode , $callback ){

    add_filter( "resp-core--parsers" , function($parsers) use ($tag , $shortcode , $callback ){

        $parsers[] = [
            "tag" => $tag,
            "shortcode" => $shortcode,
            "callback" => $callback
        ];

        return $parsers;

    } );
}

__resp_init();
