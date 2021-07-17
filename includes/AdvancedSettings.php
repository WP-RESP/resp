<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\Tag, \Resp\ConstantLoader;

defined('RESP_VERSION') or die;

class AdvancedSettings
{

    /**
     * @since 0.9.0
     */
    static function bind()
    {
        add_action("admin_init", "\Resp\AdvancedSettings::adminInit", 10);
    }


    /**
     * @since 0.9.0
     */
    static function adminInit()
    {

        $class = get_called_class();

        $options = ConstantLoader::getConstants();

        $callback = "$class::settingFieldsCallback";

        $group = RESP_OPTION_GROUP . "-settings";


        add_settings_section(
            $group,
            esc_html__("Advanced Settings", "resp"),
            "$class::settingSectionCallback",
            $group
        );


        foreach ($options as $key => $value) {
            register_setting($group, $key, ['default' => $value["default"]]);
            add_settings_field(
                $key,
                esc_html__($value["label"], "resp"),
                $callback,
                $group,
                $group,
                [
                    'label_for' => $key,
                    'default'   => $value["default"],
                    'description' => $value["description"]
                ]
            );
        }
    }


    /**
     * @since 0.9.0
     */
    static function settingSectionCallback($args)
    {
    }


    /**
     * @since 0.9.0
     */
    static function settingFieldsCallback($args)
    {

        $value = get_option($args['label_for'], $args['default']);

        if (is_bool($args['default'])) {
            Tag::checkboxFor($args['label_for'], esc_html__($args["description"], "resp"), $value)->e();
        }

        if (is_string($args['default'])) {
            Tag::labelFor($args['label_for'],  Tag::p(esc_html__($args["description"], "resp"))->render(), ["append_content" => true])->eo();
            Tag::textFor($args['label_for'], $value, ["class" => "regular-text"])->e();
            Tag::close("label");
        }
    }
}
