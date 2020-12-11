<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component;

defined('RESP_VERSION') or die;

class SiteInfo extends Component
{

    const BLOG_INFO_PARAMS =  [
        "name", "description", "wpurl", "url", "admin_email", "charset", "version",
        "html_type", "text_direction", "language", "stylesheet_url", "stylesheet_directory",
        "template_url", "template_directory", "pingback_url", "atom_url", "rdf_url",
        "rss_url", "rss2_url", "comments_atom_url", "comments_rss2_url", "siteurl", "home"
    ];

    function __construct()
    {
        $class = get_called_class();

        add_filter("resp-core--config-output", "$class::checkInfoParams");

        add_shortcode('resp-today', "$class::todayShortcode");
        add_shortcode('resp-info', "$class::infoShortcode");
    }

    /**
     * @since 0.9.0
     */
    static function todayShortcode($atts = [], $content = null)
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
    static function infoShortcode($atts = [], $content = null)
    {

        $names = array_values($atts);

        if (count($names) === 0) {
            return;
        }

        $param = $names[0];

        if (in_array($param, self::BLOG_INFO_PARAMS)) {
            $output = get_bloginfo($param);

            self::checkForEscURL($param, $output);

            return $output;
        }
    }

    /**
     * @since 0.9.3
     */
    static function checkInfoParams($output)
    {
        self::replaceBlogInfo($output);
        return $output;
    }

    /**
     * @since 0.9.3
     */
    static function replaceBlogInfo(&$text)
    {

        if (!is_string($text)) {
            return;
        }

        if (empty($text)) {
            return;
        }

        foreach (self::BLOG_INFO_PARAMS as $info) {

            $keyword = "@blog:$info";

            if (strpos($text, $keyword) > -1) {

                $value = get_bloginfo($info);
                self::checkForEscURL($info, $value);
                $text = str_replace($keyword, $value, $text);
            }
        }
    }

    /**
     * @since 0.9.3
     */
    private static function checkForEscURL($param, &$value)
    {
        if (in_array($param, [
            "wpurl", "url", "stylesheet_url", "template_url", "pingback_url",
            "atom_url", "rdf_url", "rss_url", "rss2_url", "comments_atom_url", "comments_rss2_url",
            "siteurl", "home"
        ])) {
            $value = esc_url($value);
        }
    }
}
