<?php
/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp;

defined('RESP_VERSION') or die;

class ThemeOptions
{

    /**
     * @since 0.9.0
     */
    static function init()
    {

        self::registerTabs();

        self::setupAdvancedSettingsPage();

        self::setupAdminHooks();

        self::registerConfigForm();

        add_action('resp-settings-after-content',  "\Resp\ThemeOptions::settingsAfterContent");

        add_action("resp-settings-after-content_dashboard", "\Resp\ThemeOptions::renderDashboard");
    }


    /**
     * @since 0.9.0
     */
    private static function setupAdvancedSettingsPage()
    {
        AdvancedSettings::bind();
    }


    /**
     * @since 0.9.0
     */
    private static function registerConfigForm()
    {
        ThemeConfigPlaceholder::registerForm(
            "Configuration",
            "Backup the configuration before making changes.",
            false,
            "resp-settings-after-content_edit"
        );
    }


    /**
     * @since 0.9.0
     */
    private static function setupAdminHooks()
    {
        add_action('admin_init', '\Resp\ThemeOptions::adminInit');

        add_action('admin_menu', '\Resp\ThemeOptions::adminMenu', 0);

        add_action('admin_enqueue_scripts', '\Resp\ThemeOptions::adminEnqueueScripts');
    }


    /**
     * @since 0.9.0
     */
    private static function registerTabs()
    {
        add_action('resp-admin--tabs',  '\Resp\ThemeOptions::respDashboardTab', 1);

        add_action('resp-admin--tabs',  '\Resp\ThemeOptions::respEditTab', 10);

        add_action('resp-admin--tabs',  '\Resp\ThemeOptions::respSettingsTab', 11);
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
        $option = 'resp_theme_data';
        $group = RESP_OPTION_GROUP . "-edit";
        register_setting($group, $option, ['default' => '']);
    }



    /**
     * @since 0.9.0
     */
    static function renderDashboard()
    {

        Tag::create([
            "class" => ["resp-notice-info", "two-column"]
        ])->eo();



        Tag::create(["class" => ["first", "flex-center", "logo"]])->eo();

        Tag::img(FileManager::getRespAssetsDirectoryUri("img/resp-logo.svg"))->addClass("settings-logo")->e();

        Tag::close();



        Tag::create(["class" => ["second", "changelog"]])->eo();



        Tag::h3(__("Version", RESP_TEXT_DOMAIN) . " " . RESP_VERSION, [
            "class" => "version"
        ])->e();



        Tag::p(__("The most flexible and powerful WordPress designing tool.", RESP_TEXT_DOMAIN))->e();

        Tag::p(sprintf(
            __('Please see <a target="_blank" href="%s">documentation</a> to learn more.', RESP_TEXT_DOMAIN),
            esc_url('https://github.com/WP-RESP/resp/wiki')
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
            'Resp\ThemeOptions::renderOptionsPage',
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
        wp_enqueue_style("resp-admin", FileManager::getRespAssetsDirectoryUri("css/resp-admin.min.css"), [], RESP_VERSION, "all");

        wp_enqueue_style("resp-font",  FileManager::getRespAssetsDirectoryUri("css/resp-font.min.css"), [], RESP_VERSION,  "all");

        wp_enqueue_script("resp-admin", FileManager::getRespAssetsDirectoryUri("js/resp-admin.min.js"), ["jquery", "resp"], RESP_VERSION, true);


        add_action("resp-localize-script", "\Resp\ThemeOptions::localizeAdminDashboardData", 10, 1);


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
    static function localizeAdminDashboardData($data)
    {

        if (!isset($data["admin"])) {
            $data["admin"] = [
                'tab' => self::getCurrentTab()
            ];
        }

        if (!isset($data['admin']['editor'])) {
            $data['admin']['editor'] = [];
        }

        $data['admin']['editor']['json'] = wp_enqueue_code_editor(['type' => 'application/json']);

        $data['admin']['editor'] = apply_filters('resp-admin--editor',  $data['admin']['editor']);

        return $data;
    }


    /**
     * @since 0.9.0
     */
    static function getCurrentTab()
    {
        return $_GET["tab"] ?: "dashboard";
    }


    /**
     * @since 0.9.0
     */
    static function renderOptionsPage()
    {

        $currentTab = self::getCurrentTab();

        settings_errors('resp_errors');

        Tag::create(["name" => "div", "class" => "wrap"])->eo();

        Tag::hr()->addClass("wp-header-end")->e();

        Tag::create(["name" => "ul", "class" => "subsubsub"])->eo();

        do_action('resp_settings_sub');

        Tag::close("ul");

        ob_start();

        do_action('resp-admin--tabs');

        $tabs = ob_get_clean();

        Tag::h2($tabs)->addClass(["nav-tab-wrapper" , "resp-nav-tab-wrapper"])->e();

        do_action('resp-settings-before-container');

        $form = Tag::form("settings", "options.php");

        if ($currentTab ==  "settings") {
            $form->set(["class" => "resp-notice-wrapper"]);
        }

        $form->eo();

        do_action("resp-settings-before-content");

        do_action("resp-settings-before-content_{$currentTab}");

        settings_fields(RESP_OPTION_GROUP . "-" . $currentTab);

        do_settings_sections(RESP_OPTION_GROUP . "-" . $currentTab);

        do_action("resp-settings-after-content_{$currentTab}");

        do_action("resp-settings-after-content");


        Tag::close("form");

        do_action('resp-settings-after-container');

        Tag::close("div");
    }

    /**
     * @since 0.9.0
     */
    static function settingsAfterContent()
    {
        $currentTab = self::getCurrentTab();

        if (in_array($currentTab, ["settings", "edit"])) {
            submit_button("Save Changes");
        }
    }


    /**
     * @since 0.9.0
     */
    static function addTab($title, $icon)
    {

        $currentTab = self::getCurrentTab();

        $name = sanitize_title($title);

        $icon = Tag::create("span")->set([
            "class" => "respicon-$icon"
        ]);

        $link = Tag::a(__($title, RESP_TEXT_DOMAIN), admin_url("admin.php?page=resp&tab=$name"))
            ->set(["append_content" => true])
            ->addClass("nav-tab");

        if ($currentTab == $name) {
            $link->addClass('nav-tab-active');
        }

        $link->append($icon)
            ->e();
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
        self::addTab("Dashboard", "monitor");
    }
}
