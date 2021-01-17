<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder as tb, Resp\Tag,  Resp\Core;

defined('RESP_VERSION') or die;

class Metas extends Component
{

    const METAS_DEF_NAME = "metas";

    static $metas = [];

    function __construct()
    {
        add_action("resp-themebuilder-build", [$this, 'onDataLoaded'], 10);  
    }

    /**
     * @since 0.9.1
     */
    function onDataLoaded(){

        $action = "before-head";

        Core::chkIsolation($action , "--");

        add_action($action,  [$this, "printMetas"] , 10);
    }

    /**
     * @since 0.9.1
     */
    function printMetas(){

        $data = array_merge_recursive( 
            tb::getData(self::METAS_DEF_NAME),
            tb::getStatics(self::METAS_DEF_NAME)
        );

        if(empty($data)){
            return;
        }

        $all = $data["@all"] ?? [];

        if(is_front_page()){
            $all = array_merge($all , $data["@home"] ?? []);
        }

        if(is_single()){
            $all = array_merge($all , $data["@single"] ?? []);
        }

        if(is_attachment()){
            $all = array_merge($all , $data["@attachment"] ?? []);
        }

        if(is_page()){
            $all = array_merge($all , $data["@page"] ?? []);
        }

        array_walk($all , function(&$param , $index) { 

            if(!is_array($param)){
                return;
            }

            array_walk($param , function(&$item , $key) { 

                $item = apply_filters("resp-core--config-output" , $item);
                
            });

        });

        self::$metas = $all;

        foreach(self::$metas as $key => $meta){

            $name = $meta["wrap"] ?? "meta";

            $attr = $meta;

            if(isset($attr["wrap"])) {
                unset($attr["wrap"]);
            }

            Tag::create([
                "name" => $name,
                "attr" => $attr
            ])->e();
        }

    }
}