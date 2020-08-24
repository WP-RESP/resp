<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp;

use \Resp\Tag, \Resp\ConstantLoader;

class AdvancedSettings
{

    /**
     * @since 0.9.0
     */
    static function bind()
    {
        add_action("admin_init", "\Resp\AdvancedSettings::adminInit");
    }


    /**
     * @since 0.9.0
     */
    static function adminInit()
    {

        $class = "Resp\AdvancedSettings";

        $options = ConstantLoader::getConstants();


        $callback = "$class::settingFieldsCallback";


        $group = RESP_OPTION_GROUP . "-settings";


        add_settings_section(
            $group,
            __("Advanced Settings"),
            "$class::settingSectionCallback",
            $group
        );


        foreach ($options as $key => $value) {
            register_setting($group, $key, ['default' => $value["default"]]);
            add_settings_field(
                $key,
                __($value["label"], RESP_TEXT_DOMAIN),
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
            Tag::checkboxFor($args['label_for'], __($args["description"], RESP_TEXT_DOMAIN), $value)->e();
        }

        if (is_string($args['default'])) {
            Tag::labelFor($args['label_for'],  __($args["description"], RESP_TEXT_DOMAIN), ["append_content" => true])->eo();
            Tag::textFor($args['label_for'], $value, ["class" => "regular-text"])->e();
            Tag::close("label");
        }
    }
}
