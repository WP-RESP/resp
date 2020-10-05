<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder;

defined('RESP_VERSION') or die;

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
