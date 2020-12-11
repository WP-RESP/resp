<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component, Resp\ThemeBuilder as tb;

defined('RESP_VERSION') or die;

class UserInfo extends Component
{

    function __construct()
    {
        $class = get_called_class();

        add_filter("resp-core--config-output", "$class::checkInfoParams");
    }

    /**
     * @since 0.9.3
     */
    static function checkInfoParams($output)
    {

        if (!is_user_logged_in() || !is_string($output) || empty($output)) {
            return $output;
        }

        $user = wp_get_current_user();

        $meta = get_user_meta($user->ID);

        foreach (array_merge(
            array_keys($meta),
            ["id", "avatar", "email"]
        ) as $info) {

            $keyword = "@user:$info";

            if (strpos($output, $keyword) > -1) {
                $value = self::getUserInfo($user, $info, $meta);
                $output = str_replace($keyword, implode(",", $value), $output);
            }

        }

        return $output;
    }

    /**
     * @since 0.9.3
     */
    private static function getUserInfo($user, $info, $meta)
    {

        switch ($info) {
            case "id":
                $value = [$user->ID];
                break;
            case "email":
                $value = [$user->user_email];
                break;
            case "avatar":
                $value = [esc_url(get_avatar_url($user->user_email))];
                break;
            default:
                $value = $meta[$info] ?? [];
        }

        return $value;
    }
}
