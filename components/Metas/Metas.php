<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder, Resp\Tag;

class Metas extends Component
{

    const METAS_DEF_NAME = "metas";

    static $metas = [];

    function __construct()
    {
        
        add_action("resp-themebuilder-build", [$this, 'init'], 10);
       
    }

    /**
     * @since 0.9.1
     */
    function init(){

        $slug = ThemeBuilder::getSlug();

        $data = ThemeBuilder::getData(self::METAS_DEF_NAME);

        array_walk($data , function(&$item , $key) use ($data) { 
        
            $value = $data[$key];

            ThemeBuilder::replaceBlogInfo($value);

            $item = $value;
            
        });

        self::$metas = $data;

        add_action("$slug--before-head",  [$this, "printMetas"] , 10);

    }

    /**
     * @since 0.9.1
     */
    function printMetas(){
       
        foreach(self::$metas as $meta){

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