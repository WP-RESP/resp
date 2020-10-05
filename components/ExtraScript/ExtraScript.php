<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use \Resp\Component, \Resp\Core, \Resp\ThemeOptionWrapper as tow;

defined('RESP_VERSION') or die;

class ExtraScript extends Component
{

    private static $default_scripts = "";


    function __construct()
    {

        // Loads default script from the file
        self::$default_scripts = file_get_contents(__DIR__ . "/assets/default.js");

        add_action('admin_init', [$this, 'registerScriptSetting']);

        add_action("resp-settings-after-content_edit", [$this, 'scriptEditorSection']);

        add_action("resp-admin--editor",  [$this, "addJavascriptEditorOptions"], 10, 1);

        if (Core::option("resp_no_jquery")) {
            add_action("wp_enqueue_scripts", [$this, "deregisterJquery"], 10);
        } else if (!Core::isUnderConstructionPage()) {
            add_action('wp_enqueue_scripts', [$this,  "appendScript"]);
        }
    }



    /**
     * @since 0.9.0
     */
    function registerScriptSetting()
    {
        $option = 'resp_script';

        $group = RESP_OPTION_GROUP . "-edit";

        register_setting($group, $option, ['default' => self::$default_scripts]);
    }



    /**
     * @since 0.9.0
     */
    function addJavascriptEditorOptions($data)
    {

        $data['javascript'] = wp_enqueue_code_editor(['type' => 'text/javascript']);

        return $data;
    }



    /**
     * @since 0.9.0
     */
    function scriptEditorSection()
    {

        $script = get_option("resp_script", self::$default_scripts);

        if (empty($script)) {
            $script = self::$default_scripts;
        }

        tow::renderForm(
            "Script",
            "The following code will be added to the document footer.",
            function () use ($script) {
                \Resp\Tag::textareaFor("resp_script", wp_check_invalid_utf8($script) , ["rows" => 15])
                    ->addClass("large-text code")
                    ->e();
            }
        );
    }




    /**
     * @since 0.9.0
     */
    function deregisterJquery()
    {
        wp_deregister_script("jquery");
    }



    /**
     * @since 0.9.0
     */
    function appendScript()
    {

        $script = get_option("resp_script", self::$default_scripts);

        if (empty($script)) {
            $script = self::$default_scripts;
        }

        wp_add_inline_script("resp", $script);
    }
}
