<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp;

use \Resp\Communicator;

class ConstantLoader {

    private static $constants = [];


    /**
     * @since 0.9.0
     */
    static function getConstants(){

        return apply_filters("resp-core--constants", self::$constants);

    }


    /**
     * @since 0.9.0
     */
    private static function defineConstants(){

        $constants = self::getConstants();

        foreach (array_keys($constants) as $const) {

            $value = get_option($const, $constants[$const]['default'] ?? false);

            define($const, $value);

        }

    }


     /**
     * @since 0.9.0
     */
    static function load($path , $define = true)
    {

        if (!file_exists($path)) {
            Communicator::critical(sprintf(__("File not found: \"%s\"" ,RESP_TEXT_DOMAIN) , $path));
            return;
        }

        $json = file_get_contents($path);

        self::$constants = json_decode($json, true);
        

        if($define)
        {
            // defining options as a constant value
            self::defineConstants();
        }


    }


}