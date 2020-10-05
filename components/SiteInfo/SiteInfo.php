<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component;
use Resp\ThemeBuilder;

defined('RESP_VERSION') or die;

class SiteInfo extends Component
{

    function __construct()
    {
        add_shortcode('resp-today', [$this, 'todayShortcode']);

        add_shortcode('resp-info', [$this, 'infoShortcode']);
    }

    /**
     * @since 0.9.0
     */
    function todayShortcode($atts = [], $content = null)
    {

        extract(shortcode_atts(array(
            'format' => get_option('date_format'),
            'i18n' => true
        ), $atts));

        if ($i18n) {
            return date_i18n($format);
        }

        return date($format);
    }

    /**
     * @since 0.9.0
     */
    function infoShortcode($atts = [], $content = null)
    {

        $names = array_values($atts);

        if (count($names) === 0) {
            return;
        }

        if (in_array($names[0], ThemeBuilder::BLOG_INFO_PARAMS)) {
            return get_bloginfo($names[0]);
        }

    }
}
