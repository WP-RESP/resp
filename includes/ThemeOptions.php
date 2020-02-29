<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp;

defined('RESP_VERSION') or die;

class ThemeOptions
{

    private static $options = [];

    static function init()
    {

        self::loadOptions();

        add_action('admin_init', '\Resp\ThemeOptions::adminInit');

        add_action('admin_menu', '\Resp\ThemeOptions::adminMenu', 0);

        add_action('admin_enqueue_scripts', '\Resp\ThemeOptions::adminEnqueueScripts');

        add_action('resp-settings-after-content',  "\Resp\ThemeOptions::settings_after_content");

        add_action("resp-settings-after-content", "\Resp\ThemeOptions::renderDashboard");

        add_action('resp_settings_tab',  '\Resp\ThemeOptions::respDashboardTab', 1);

        add_action('resp_settings_tab',  '\Resp\ThemeOptions::respSettingsTab', 11);

        add_action('resp_settings_tab',  '\Resp\ThemeOptions::respEditTab', 10);
        
    }


    /**
     * @since 0.9.0
     */
    private static function loadOptions()
    {

        $file = get_template_directory() . "/options.json";

        if (!file_exists($file)) {
            wp_die("File not found: \"%s\"", "Error", $file);
        }

        $json = file_get_contents($file);

        self::$options = apply_filters("resp-core--options", json_decode($json, true));

        // defining options as a constant parameter
        foreach (array_keys(self::$options) as $option) {
            $value = get_option($option, self::$options[$option]['default']);
            define($option, $value);
        }
    }


    /**
     * @since 0.9.0
     */
    static function is_dashboard()
    {

        if (!isset($_GET["page"]) || $_GET["page"] !== "resp") {
            return false;
        }

        if (!isset($_GET["tab"])) {
            return true;
        }

        return isset($_GET["tab"]) && ($_GET["tab"] == 'dashboard' || $_GET["tab"] == '');
    }


    /**
     * @since 0.9.0
     */
    static function adminInit()
    {

        $callback = 'Resp\ThemeOptions::settingFieldsCallback';


        $group = RESP_OPTION_GROUP . "-settings";


        add_settings_section(
            $group,
            __("Advanced Settings"),
            'Resp\ThemeOptions::settingSectionCallback',
            $group
        );


        foreach (self::$options as $key => $value) {
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


        $option = 'resp_theme_data';
        $group = RESP_OPTION_GROUP . "-edit";
        register_setting($group, $option, ['default' => '']);
    }

    /**
     * @since 0.9.0
     */
    static function renderDashboard()
    {
        if (self::is_dashboard()) {

           
            Tag::create([
                "class" => ["resp-notice-info", "two-column"]
            ])->eo();



            Tag::create(["class" => ["first" , "flex-center" , "logo"]])->eo();

            Tag::img(FileManager::getRespAssetsDirectoryUri("img/resp-logo.svg"))->addClass("settings-logo")->e();

            Tag::close();



            Tag::create(["class" => ["second" , "changelog"]])->eo();



            Tag::h3(__("Version", RESP_TEXT_DOMAIN) . " " . RESP_VERSION, [
                "class" => "version"
            ])->e();



            Tag::p(__("The most flexible and powerful WordPress designing tool.", RESP_TEXT_DOMAIN))->e();


            
            Tag::p(sprintf(
                __('Please see <a target="_blank" href="%s">documentation</a> to learn more.', RESP_TEXT_DOMAIN),
                esc_url('https://github.com/Rmanaf/resp/blob/master/README.md')
            ))->e();




            if (!is_plugin_active("code-injection/wp-code-injection.php") && current_user_can("update_core")) {

                $ciUrl =  esc_url("https://wordpress.org/plugins/code-injection/");

                self::info(
                    sprintf(__("You may need <a href=\"%s\" target=\"_blank\">Code Injection</a> plugin to create templates.", RESP_TEXT_DOMAIN), $ciUrl)
                )->e();
            }



            Tag::close();

            Tag::close();



            do_action("resp-dashboard-after-content");
        }
    }


    /**
     * @since 0.9.0
     */
    static function adminMenu()
    {

        add_menu_page(
            __("Resp", RESP_TEXT_DOMAIN),
            __("Resp", RESP_TEXT_DOMAIN),
            "update_core",
            RESP_OPTION_GROUP,
            'Resp\ThemeOptions::options_page_html',
            "dashicons-admin-generic"
        );
    }


    /**
     * @since 0.9.0
     */
    static function description($text)
    {
        return Tag::p("&nbsp;{$text}")->set([
            "class" => "description"
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function info($text)
    {
        return Tag::p("&nbsp;$text")->set([
            "class" => "description",
            "append_content" => true
        ])->append(Tag::dashicons("dashicons-info"));
    }


    /**
     * @since 0.9.0
     */
    static function warning($text)
    {

        Tag::p($text)->set([
            "class" => "description",
            "append_content" => true
        ])->append(Tag::dashicons("dashicons-warning"))->e();
    }


    /**
     * @since 0.9.0
     */
    static function adminEnqueueScripts()
    {
        wp_enqueue_style("resp-admin", FileManager::getRespAssetsDirectoryUri("css/resp-admin.css"), [], RESP_VERSION, "all");

        wp_enqueue_style("resp-font",  FileManager::getRespAssetsDirectoryUri("css/resp-font.css"), [], RESP_VERSION,  "all");

        wp_enqueue_script("resp-admin", FileManager::getRespAssetsDirectoryUri("js/resp-admin.js"), ["jquery", "resp"], RESP_VERSION, true);


        add_action("resp-localize-script", "\Resp\ThemeOptions::localize_cm_settings", 10, 1);
        

        if (self::is_dashboard()) {
            do_action("resp-dashboard-enqueue-scripts");
        }

        if (isset($_GET["tab"]) && $_GET["tab"] === 'edit') {
            wp_enqueue_style('wp-codemirror');
        }

        Core::enqueueRespScripts();
    }

    /**
     * @since 0.9.0
     */
    static function localize_cm_settings($data)
    {

        $data["settingsTab"] = isset($_GET["tab"]) ? $_GET["tab"] : '';

        if (!isset($data['codeEditor'])) {
            $data['codeEditor'] = [];
        }

        $data['codeEditor']['jsonEditor'] = wp_enqueue_code_editor(['type' => 'application/json']);

        return $data;
    }


    /**
     * @since 0.9.0
     */
    static function options_page_html()
    {
        global $_RESP_ACTIVE_TAB;

        $_RESP_ACTIVE_TAB = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

        settings_errors('resp_errors');

        Tag::create(["name" => "div", "class" => "wrap"])->eo();

        Tag::hr()->addClass("wp-header-end")->e();

        Tag::create(["name" => "ul", "class" => "subsubsub"])->eo();

        do_action('resp_settings_sub');

        Tag::close("ul");

        ob_start();

        do_action('resp_settings_tab');

        $tabs = ob_get_clean();

        Tag::h2($tabs)->addClass("nav-tab-wrapper")->e();

        do_action('resp-settings-before-container');

        Tag::form("settings", "options.php")->eo();

        do_action('resp-settings-before-content');

        settings_fields(RESP_OPTION_GROUP . "-" . $_RESP_ACTIVE_TAB);

        do_settings_sections(RESP_OPTION_GROUP . "-" . $_RESP_ACTIVE_TAB);

        do_action('resp-settings-after-content');

        Tag::close("form");

        do_action('resp-settings-after-container');

        Tag::close("div");
    }

    /**
     * @since 0.9.0
     */
    static function settings_after_content()
    {
        global $_RESP_ACTIVE_TAB;

        $saveBtn = $_RESP_ACTIVE_TAB == "settings" || $_RESP_ACTIVE_TAB == "edit";

        if ($_RESP_ACTIVE_TAB == "edit") {
            self::theme_configuration();
        }

        if ($saveBtn) {
            submit_button("Save Changes");
        }
    }


    /**
     * @since 0.9.0
     */
    private static function theme_configuration()
    {
        global $_RESP_ACTIVE_TAB;

        if ($_RESP_ACTIVE_TAB == "edit") {

            do_action("resp-edit-before-content");

            Tag::create(["name" => "div", "id" => "jsoneditor"])->e();

            Tag::h1(__("Configuration", RESP_TEXT_DOMAIN))->e();

            Tag::p(__("Backup the configuration before making changes.", RESP_TEXT_DOMAIN))->e();

            Tag::textareaFor("resp_theme_data", esc_textarea(ThemeBuilder::retrieveJsonData(true)), ["rows" => "15"])->addClass("large-text code")->e();

            Tag::create("div")->addClass("resp-editor-backup-parent")->eo();

            Tag::close("div");

            do_action("resp-edit-after-content");
        }
    }


    /**
     * @since 0.9.0
     */
    static function addTab($title, $icon, $is_default_tab = false)
    {
        global $_RESP_ACTIVE_TAB;

        $name = sanitize_title($title);

        $is_active = $_RESP_ACTIVE_TAB == $name;

        if ($is_default_tab) {
            $is_active = $_RESP_ACTIVE_TAB == $name || '';
        }

        $class = $is_active ? 'nav-tab-active' : '';

        $href = admin_url("admin.php?page=resp&tab=$name");

        $title = __($title, RESP_TEXT_DOMAIN);

        echo "<a class=\"nav-tab $class\" href=\"$href\"><span class=\"respicon-$icon\"></span>$title</a>";
    }

    /**
     * @since 0.9.0
     */
    static function respEditTab()
    {
        if (!Core::option("resp_development_mode")) {
            return;
        }

        self::addTab("Edit", "edit");
    }



    /**
     * @since 0.9.0
     */
    static function respSettingsTab()
    {
        self::addTab("Settings", "settings");
    }

    /**
     * @since 0.9.0
     */
    static function respDashboardTab()
    {
        self::addTab("Dashboard", "monitor", true);
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
