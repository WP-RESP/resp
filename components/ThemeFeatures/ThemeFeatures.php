<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder;

defined("RESP_VERSION") or die;

class ThemeFeatures extends Component
{
    const DATA_PARAM_FEATURES = "features";

    function __construct()
    {
        add_action("resp-themebuilder-build", [$this, "extractFeatures"], 10);
    }



    /**
     * @since 0.9.0
     */
    function extractFeatures()
    {
        $data = ThemeBuilder::getData(self::DATA_PARAM_FEATURES);

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                add_theme_support($key);
            } else {
                add_theme_support($key, is_array($value) ? $value : [$value]);
            }
        }
    }
}
