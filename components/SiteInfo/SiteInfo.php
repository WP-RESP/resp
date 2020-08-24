<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp\Components;

use  Resp\Component;

class SiteInfo extends Component
{

    private static $blog_info =  [
        "name", "description", "wpurl", "url", "admin_email", "charset", "version",
        "html_type", "text_direction", "language", "stylesheet_url", "stylesheet_directory",
        "template_url", "template_directory", "pingback_url", "atom_url", "rdf_url",
        "rss_url", "rss2_url", "comments_atom_url", "comments_rss2_url", "siteurl", "home"
    ];

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

        if (in_array($names[0], self::$blog_info)) {
            return get_bloginfo($names[0]);
        }

    }
}
