<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\ThemeBuilder , \Resp\FileManager as fm;

defined('RESP_VERSION') or die;

class Communicator
{

    const REQUEST_ACTIONS = [
        "backup"  => "bf9d3e487cf925e8b8a3589a8e04da0a",
        "version" => "7c167e2946dbe96ff1cfe3a842aec1b3",
        "news"    => "32364a4581a7f32dda8c548a1583b152"
    ];

    /**
     * @since 0.9.2
     */
    static function init(){

        $clazz = get_called_class();

        add_action( "wp_ajax_resp_fetch_version_data" , "$clazz::fetchVersionData");

        add_action( "wp_ajax_resp_fetch_dashboard_data" , "$clazz::fetchDashboardData");

        add_action( "wp_ajax_resp_request_backup" , "$clazz::downloadBackupData");

    }


     /**
     * @since 0.9.2
     */
    private static function getMainServerURL($action){

        $locale = get_user_locale();

        $msURL = RESP_MAIN_SERVER;

        $actionID = self::REQUEST_ACTIONS[$action];

        return esc_url_raw("$msURL/?_rhaction=$actionID&locale=$locale");

    }


    /**
     * @since 0.9.2
     */
    static function downloadBackupData(){

        check_ajax_referer( 'request-backup-nonce' );

        $non_ui_fields = [
            "license" , 
            "profile"
        ];
 
        $config = ThemeBuilder::getData();

        $name = sanitize_title( get_bloginfo( "name" ) ) . "_" . date("Y-m-d_H-i-s");

        $profile = $config["profile"] ?? [];

        foreach($non_ui_fields as $field ) {
            unset( $config[$field] );
        }

        if(isset($profile["name"] , $profile["token"])){
            
            // do profile backup

        }

        echo json_encode([
            "title" => $name,
            "data" => $config
        ]);

        exit;
       
    }


    /**
     * @since 0.9.2
     */
    static function fetchVersionData(){

        check_ajax_referer( 'version-data-nonce' );
 
        $response = wp_remote_get( self::getMainServerURL("version") );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {

            echo esc_js(wp_remote_retrieve_body($response));

        } else {

            echo "Error::Unable to retrieve data from WP-RESP server.";

        }

        exit;
       
    }


    /**
     * @since 0.9.2
     */
    static function fetchDashboardData(){

        check_ajax_referer( 'dashboard-data-nonce' );

        if(file_exists(fm::getRespDirectory(".noadv"))){
            echo "Error::Source integrity issue.";
            exit;
        }
        
        $response = wp_remote_get( self::getMainServerURL("news") );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {

            echo wp_remote_retrieve_body($response);

        } else {

            echo "Error::Unable to retrieve data from WP-RESP server.";

        }

        exit;

    }

    /**
     * @since 0.9.0
     */
    static function info($message)
    {
        self::notice($message);
    }

    /**
     * @since 0.9.0
     */
    static function warn($message)
    {
        self::notice($message, "warning");
    }

    /**
     * @since 0.9.0
     */
    static function critical($message)
    {
        self::notice($message, "error", false);
    }

    /**
     * @since 0.9.0
     */
    static function notice($message, $type = "info", $dismissible = true)
    {
        add_action('admin_notices', function () use ($message, $type, $dismissible) {
            $classes = ["notice", "notice-$type"];

            if ($dismissible) {
                $classes[] = "is-dismissible";
            }

            Tag::create()->addClass($classes)
                ->append(Tag::p($message))
                ->e();
        });
    }
}
