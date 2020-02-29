<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp\Components;

use  Resp\Component, Resp\Core;

class ExtraScript extends Component
{

    private static $default_scripts = "";


    function __construct()
    {

        // Loads default script from the file
        self::$default_scripts = file_get_contents(__DIR__ . "/assets/default.js");

        add_action('admin_init', [$this, 'registerScriptSetting']);

        add_action("resp-edit-after-content", [$this, 'scriptEditorSection']);

        add_action("resp-localize-script",  [$this, "localizeCmOptions"], 10, 1);

        if (Core::option("resp_no_jquery")) {
            add_action("wp_enqueue_scripts", [$this, "deregisterJquery"], 10);
        } else if (!Core::isUnderConstruction()) {
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
    function localizeCmOptions($data)
    {
        if (is_admin()) {
            if (isset($_GET["tab"]) && $_GET["tab"] === 'edit') {
                if (!isset($data['codeEditor'])) {
                    $data['codeEditor'] = [];
                }
                $data['codeEditor']['scriptEditor'] = wp_enqueue_code_editor(['type' => 'text/javascript']);
            }
        }
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

        \Resp\Tag::h1(__("Script", RESP_TEXT_DOMAIN))->e();

        \Resp\Tag::p(__("The following script will be added into the document footer.", RESP_TEXT_DOMAIN))->e();

        \Resp\Tag::textareaFor("resp_script", wp_check_invalid_utf8( $script ) )->e();
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
