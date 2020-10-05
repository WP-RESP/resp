<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\FileManager as fm;

defined('RESP_VERSION') or die;

class ThemeOptions
{

    /**
     * @since 0.9.0
     */
    static function init()
    {

        $clazz = get_called_class();

        self::registerTabs();

        self::setupAdvancedSettingsPage();

        self::setupAdminHooks();

        self::registerConfigForm();

        add_action('resp-settings-after-content',  "$clazz::settingsAfterContent");

        add_action("resp-settings-after-content_dashboard", "$clazz::renderDashboard");

        add_action( "resp-dashboard-after-content" , "$clazz::dashboardDataHolder" , PHP_INT_MAX);

        add_action( 
            "resp-admin--wrapper-form_configuration_header-items", 
            "$clazz::configBackupHolder"
        );

    }


    /**
     * @since 0.9.0
     */
    private static function setupAdvancedSettingsPage()
    {
        AdvancedSettings::bind();
    }


    /**
     * @since 0.9.2
     */
    static function configBackupHolder($items){
        
        $nonce = wp_create_nonce( "request-backup-nonce" );

        $items[] = Tag::create([
            "class" => "horizontal-flex-space"
        ]);

        $items[] = Tag::create([
                "id" => "config_backup_wrap",
                "class" => "resp-spinner-container",
                "attr" => [
                    "data-nonce" => $nonce
                ]
            ])->append([
                Tag::create(["class" => "resp-spinner"]),
                Tag::button( esc_html__("Backup" , "resp"))->set([
                    "id" => "config_backup_btn"
                ])
            ]);

        return $items;

    }


    /**
     * @since 0.9.0
     */
    private static function registerConfigForm()
    {
        ThemeConfigPlaceholder::registerForm(
            "Configuration",
            "Backup configuration before making changes.",
            false,
            "resp-settings-after-content_edit"
        );
    }

    /**
     * @since 0.9.2
     */
    static function dashboardDataHolder(){

        $nonce = wp_create_nonce('dashboard-data-nonce');

        Tag::create([
            "attr" => [
                "data-nonce" => $nonce
            ],
            "id" => "server_info_wrap",
            "class" => ["resp-spinner-container" , "busy"]
            ])->append([
                Tag::create(["class" => "resp-spinner large"]) 
            ])->e();

    }


    /**
     * @since 0.9.0
     */
    private static function setupAdminHooks()
    {

        $clazz = get_called_class();

        add_action('admin_init', "$clazz::adminInit");

        add_action('admin_menu', "$clazz::adminMenu", 0);

        add_action('admin_enqueue_scripts', "$clazz::adminEnqueueScripts");
    }


    /**
     * @since 0.9.0
     */
    private static function registerTabs()
    {

        $clazz = get_called_class();

        add_action('resp-admin--tabs',  "$clazz::respDashboardTab", 1);

        add_action('resp-admin--tabs',  "$clazz::respEditTab", 10);

        add_action('resp-admin--tabs',  "$clazz::respSettingsTab", 11);
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

        $nonce = wp_create_nonce('version-data-nonce');

        Tag::create([
            "class" => ["container-fluid"]
        ])->eo();

        Tag::create([
            "class" => ["row" , "resp-card", "product-info"]
        ])->eo();

        Tag::create(["class" => ["col-12" , "col-sm-6", "flex-center", "logo" , "p-5" , "p-sm-0"]])->eo();

        Tag::img(fm::getRespAssetsDirectoryUri("img/resp-logo.svg"))->addClass("settings-logo")->e();

        Tag::close();

        Tag::create(["class" => ["col-12" , "col-sm-6" ]])->eo();

        Tag::h3(esc_html__("Version", "resp") . " " . RESP_VERSION, [])->set([
            "attr" => ["data-nonce" => $nonce],
            "class" => ["version", "resp-spinner-container" , "busy"],
            "id" => "version_holder"
        ])->append([
            Tag::create([
                "class" => "resp-spinner"
            ])
        ])->e();

        Tag::p(esc_html__("The most flexible and powerful WordPress designing tool.", "resp"))->e();

        Tag::p(sprintf( 
            /* translators: %1$s is replaced with "string" */
            esc_html__('Please see %1$s to learn more about RESP.', 'resp'),
            sprintf(
                '<a target="_blank" href="%s">%s</a>',
                esc_url( 'https://github.com/WP-RESP/resp/wiki' ),
                esc_html__( 'Documentation', 'resp' )
            )
        ))->e();

        $ciUrl =  esc_url("https://wordpress.org/plugins/search/code+snippet+injection/");

        self::info(
            sprintf(
                /* translators: %1$s is replaced with "string" */
                esc_html__('You may need certain %1$s or tools to create templates.', 'resp'),
                sprintf(
                    '<a target="_blank" href="%1$s">%2$s</a>',
                    $ciUrl,
                    esc_html__( 'Plugins', 'resp' )
                )
            )
        )->e();
        
        /*
        Tag::p()->append([
            Tag::a(esc_html__("Install Template" , "resp") , "javascript:void(0)")->set([
                "id" => ["install_template_btn"],
                "class" => ["button" , "button-primary"]
            ])
        ])->e();
         */

        
        Tag::close();

        Tag::close();

        Tag::close();

       
        do_action("resp-dashboard-after-content");


    }


    /**
     * @since 0.9.0
     */
    static function adminMenu()
    {

        if(file_exists(fm::getRespDirectory(".nongenuine"))){
            return;
        }

        add_menu_page(
            esc_html__("Resp", "resp"),
            esc_html__("Resp", "resp"),
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

        $screen = get_current_screen();

       
        wp_enqueue_style("resp-icons",  fm::getRespAssetsDirectoryUri("css/icons.min.css"), [], RESP_VERSION,  "all");

        wp_enqueue_style("resp-admin", fm::getRespAssetsDirectoryUri("css/resp-admin.min.css"), [], RESP_VERSION, "all");


        if($screen->id != "toplevel_page_resp"){
            return;
        }


        wp_enqueue_style("bootstrap-grid",  fm::getRespAssetsDirectoryUri("css/bootstrap-grid.min.css"), [], RESP_VERSION,  "all");

        wp_enqueue_script("resp-admin", fm::getRespAssetsDirectoryUri("js/resp-admin.min.js"), ["jquery", "resp"], RESP_VERSION, true);

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

        $data['admin']['mainServer'] = RESP_MAIN_SERVER;

        return $data;
    }


    /**
     * @since 0.9.0
     */
    static function getCurrentTab()
    {
        return $_GET["tab"] ?? "dashboard";
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
            $form->set(["class" => "resp-card"]);
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
            submit_button(esc_html__("Save Changes" , "resp"));
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
            "class" => "ri-$icon"
        ]);

        $link = Tag::a(esc_html__($title, "resp"), admin_url("admin.php?page=resp&tab=$name"))
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
