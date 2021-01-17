<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use \Resp\FileManager as fm, 
    \Resp\ConstantLoader as cl , 
    \Resp\ThemeOptions as to, 
    \Resp\Communicator as com;

defined('RESP_VERSION') or die;

class Core
{


    const DATA_THEME_PARAMS = "parameters";

    private static $instance;

    private static $components = [];


    function __construct()
    {

        do_action("resp-core--pre-init");

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        add_action('after_setup_theme', [$this, 'customThemeSetup']);

        add_action('customize_register', [$this, 'installControls']);

        add_action('template_redirect', [$this, 'underConstructionHandler'], 10);
        
        fm::definePath(
            "@templates",
            fm::getRespContentDirectoryPath("templates"),
            fm::getRespDirectory("templates")
        );

        fm::definePath(
            ["$", "@assets"],
            fm::getRespContentDirectoryPath("assets"),
            fm::getRespDirectory("assets")
        );

        cl::load(path_join(get_template_directory(), "options.json"));

        to::init();

        com::init();

        self::installComponents();
        
        do_action('resp-core--post-init');
    }


    /**
     * @since 0.9.0
     */
    function installControls($wp_customize)
    {
        $ctrlDir = fm::getRespDirectory("includes/controls");
        foreach (glob("$ctrlDir/*") as $file) {
            require_once $file;
        }
    }

    /**
     * @since 0.9.0
     */
    static function getInstance()
    {
        return self::$instance;
    }


    /**
     * @since 0.9.0
     */
    static function isInitialized()
    {
        return !!self::$instance;
    }


    /**
     * @since 0.9.0
     */
    static function getComponents()
    {
        return self::$components;
    }


    /**
     * @since 0.9.0
     */
    static function registerComponent($name, $object)
    {
        self::$components[$name] = $object;
    }


    /**
     * @since 0.9.0
     */
    static function getComponent($name)
    {

        return self::$components[$name];
    }


    /**
     * @since 0.9.0
     */
    function underConstructionHandler()
    {

        global $page_namespace;

        if (!self::option("resp_maintenance_mode")) {
            return;
        }

        if (is_admin() || $GLOBALS['pagenow'] == "wp-login.php") {
            return;
        }

        if (in_array(http_response_code(), [301, 302, 303, 307, 308])) {
            return;
        }

        $isSuperUser = current_user_can("update_core");

        if ((!$isSuperUser) || ($isSuperUser && isset($_GET["anonymous"]))) {

            if (!is_front_page()) {
                exit(wp_redirect(home_url()));
            }

            if (!defined("$page_namespace--no-master")) {
                define("$page_namespace--no-master", true);
            }
            
            get_template_part("template-parts/pages/under-construction");

            get_footer();

            exit;

        }
    }


    /**
     * @since 0.9.0
     */
    static function getThemeParameter($name, $default = "")
    {

        global $page_namespace, $section_prefix;

        $params = array_merge_recursive(
            ThemeBuilder::getData(self::DATA_THEME_PARAMS),
            ThemeBuilder::getExternalData(self::DATA_THEME_PARAMS)
        );

        $temp = [];

        $slug = ThemeBuilder::getSlug();

        $isolated = self::option("resp_isolation");

        array_walk($params, function ($item, $key) use (&$temp , $slug) {
            if(__resp_str_startsWith($key ,"*-"))
            {
                $newKey = str_replace("*-" , "$slug-" , $key );
                $temp[$newKey] = $item;
            } else {
                $temp[$key] = $item;
            }
        });



        $params = $temp;

        $pageNS =  __resp_array_item($params, "$page_namespace--$name",  null);

        $sectionNS = __resp_array_item($params, $isolated ? "$section_prefix--$name" : "$slug-$section_prefix--$name",  null);

        $globalNS =  __resp_array_item($params, $isolated ? $name : "$slug--$name",  null);

        if (isset($globalNS)) {
            return $globalNS;
        }

        if (isset($sectionNS)) {
            return $sectionNS;
        }

        if (isset($pageNS)) {
            return $pageNS;
        }


        return $default;
    }


    /**
     * @since 0.9.0
     */
    private static function installComponents()
    {

        $cmp_dir = fm::getRespDirectory("components");

        foreach (glob("$cmp_dir/*", GLOB_ONLYDIR) as $component) {

            $name = basename($component);

            $path = fm::pathJoin($cmp_dir, $name, "{$name}.php");

            if (file_exists($path)) {

                require_once $path;

                $cmp_class = "\\Resp\\Components\\" . $name;

                if (method_exists($cmp_class, "register")) {
                    call_user_func($cmp_class . '::register');
                }
            }
        }
    }


    /**
     * @since 0.9.0
     */
    static function run()
    {

        if (self::isInitialized()) {
            return;
        }

        self::$instance = new self();
    }


    /**
     * @since 0.9.0
     */
    function customThemeSetup()
    {

        load_theme_textdomain("resp", get_template_directory() . '/languages');

        add_theme_support("customize-selective-refresh-widgets");

        ThemeBuilder::load();

        /*
        $respMaintenanceMode = self::option("resp_maintenance_mode");

        if ($respMaintenanceMode && is_admin() && current_user_can("update_core")) {
            com::notice(esc_html__("This blog is under construction.",   "resp"),  "warning");
        }
        */
    }

    /** 
     * @since 0.9.0
     */
    static function option($name)
    {
        if (defined($name)) {
            return ((bool) constant($name)) === true;
        }
        return false;
    }


    /**
     * @since 0.9.0
     */
    function enqueueScripts()
    {

        $no_jquery = self::option("resp_no_jquery");

        if (!$no_jquery) {
            wp_enqueue_script("jquery");
        }

        self::enqueueRespScripts();
    }


    /**
     * @since 0.9.0
     */
    static function enqueueRespScripts()
    {

        $respMaintenanceMode = self::option("resp_maintenance_mode");

        $devmod = self::option("resp_development_mode");

        $no_jquery = self::option("resp_no_jquery");

        $resp_api = self::option("resp_api_modules");

        $data = [
            "home" => home_url(),
            "maintenanceMode" => $respMaintenanceMode,
            "developmentMode" => WP_DEBUG || $devmod,
            "noJquery" => $no_jquery,
            "version" => RESP_VERSION
        ];

        if (is_user_logged_in()) {
            $data["adminAjaxUrl"] =  admin_url('admin-ajax.php');
            $data["isCustomizePreview"] = is_customize_preview();
            $data["i18n"] = [];
        }

        if ($resp_api) {
            wp_enqueue_script("custom-elements-es5-adapter", fm::getRespAssetsDirectoryUri("js/custom-elements-es5-adapter.js"), [], "2.4.3", false);
            wp_enqueue_script("resp-api", fm::getRespAssetsDirectoryUri("js/resp-api.min.js"), ['custom-elements-es5-adapter', 'resp'], RESP_VERSION, true);
        }

        wp_enqueue_script("resp", fm::getRespAssetsDirectoryUri("js/resp.min.js"), [], RESP_VERSION, true);

        wp_localize_script("resp", "RESP_DATA", apply_filters("resp-localize-script", $data));
    }



    /**
     * @since 0.9.0
     */
    static function isUnderConstructionPage()
    {
        $respMaintenanceMode = self::option("resp_maintenance_mode");
        return $respMaintenanceMode && ((!current_user_can("administrator") && !is_customize_preview()) || isset($_GET["anonymous"]));
    }

    /**
     * @since 0.9.0
     */
    static function chkIsolation(&$param, $sep)
    {

        $slug = ThemeBuilder::getSlug();

        if (self::option("resp_isolation")) {
            $param = $slug . $sep . $param;
        }
    }


    /**
     * @since 0.9.0
     */
    static function initPage($namespace)
    {
        global $page_namespace;

        $underConstruction = self::isUnderConstructionPage();

        if (is_front_page()) {

            $showOnFront = get_option( "show_on_front" );

            $page_namespace  = "index";

            if($showOnFront == "page"){
                $page_namespace  = "index-page";
            }

        } else {
            $page_namespace = $namespace;
        }

        if($underConstruction){
            $page_namespace = $namespace;
            add_filter( "resp--master-sidebar-disabled", "__return_true");
        }

        self::chkIsolation($page_namespace, "-");

        get_template_part("template-parts/sections/head");
    }




    /**
     * @since 0.9.0
     */
    static function applyFilters($filters, $value)
    {

        foreach ($filters as $f) {
            $value = apply_filters($f, $value);
        }

        return $value;
    }


    /**
     * @since 0.9.0
     */
    static function doAction($hook, $meta = null)
    {

        $states = __resp_get_states();

        if (current_user_can("update_core") && self::option("resp_development_mode")) {
            
            foreach($states as $state){

                if(!empty($state)){
                    $state = ":$state";
                }

                Tag::comment("{$hook}{$state}");
            }

        }

        foreach($states as $state){

            if(!empty($state)){
                $state = ":$state";
            }

            do_action("{$hook}{$state}", $meta);

            echo apply_filters("{$hook}{$state}-value", "");

        }

    }



    /**
     * @since 0.9.0
     */
    static function trigger($action, $public = false, $meta = null)
    {

        global $page_namespace, $section_prefix;

        self::doAction("$page_namespace--$action", $meta);

        if ($public) {

            $isolated = self::option("resp_isolation");

            $theme_slug = ThemeBuilder::getSlug();

            if (!empty($section_prefix)) {
                self::doAction(($isolated  ?  "$theme_slug-" : "") . "$section_prefix--$action", $meta);
            }

            self::doAction(($isolated  ?  "$theme_slug--" : "") . $action, $meta);
        }
    }


    /**
     * @since 0.9.0
     */
    static function text($value, $role, $echo = true)
    {
        global $page_namespace;

        $isolated = self::option("resp_isolation");

        $slug =  ThemeBuilder::getSlug();

        $value = apply_filters("$page_namespace--$role-value", '') ?: $value;

        $value = apply_filters($isolated ? "$role-value" : "$slug--$role-value", '') ?: $value;

        if ($echo) {
            echo $value;
        } else {
            return $value;
        }
    }


    /**
     * @since 0.9.0
     */
    static function tag($name, $role, $content = null, $atts = [])
    {
        return Tag::create([
            "id" => __resp_array_item($atts, "id", ""),
            "name" => $name,
            "class" => apply_filters("resp-core--tag-classes", ["class" => __resp_array_item($atts, "class", []), "role" => $role])["class"],
            "body" => isset($content),
            "content" => isset($content) ? $content : "",
            "attr" => apply_filters("resp-core--tag-attributes", ["attr" => __resp_array_item($atts, "attr", []), "role" => $role])["attr"],
            "children" => __resp_array_item($atts, "children", [])
        ]);
    }


    /**
     * @since 0.9.0
     */
    static function index()
    {
        
        self::initPage("index");

        if(!__resp_tp("no-feed" , false))
        {
            get_template_part("template-parts/pages/posts");
        }

        get_footer();
    }


    /**
     * @since 0.9.0
     */
    static function thumbnailCheck(&$attr, $pid)
    {

        $defaultSizes = get_intermediate_image_sizes();

        foreach ($defaultSizes as $size) {
            $sizeName = "@" . $size;

            array_walk($attr, function (&$item, $key) use ($sizeName, $size, $pid) {
                if (is_string($item) && strpos($item, $sizeName) > -1) {
                    $url = get_the_post_thumbnail_url($pid, $size);
                    $item = str_replace($sizeName, $url, $item);
                }
            });
        }
    }


    /**
     * @since 0.9.0
     */
    static function postThumbnail()
    {

        global $page_namespace;

        $showThumbnail = __resp_tp("thumbnail", true);

        $thumbnailContainer = __resp_tp("thumbnail-container", "figure");

        $thumbnailSize  = __resp_tp("thumbnail-size", "large");

        $thumbnailMode  = __resp_tp("thumbnail-mode", "image");

        $thumbnailAttr  = __resp_tp("thumbnail-attr",  []);

        $thumbnailImageAttr  = __resp_tp("thumbnail-image-attr",  []);


        // override settings
        if (is_array($showThumbnail)) {
            $thumbnailContainer = __resp_array_item($showThumbnail, "container", $thumbnailContainer);
            $thumbnailSize = __resp_array_item($showThumbnail, "size", $thumbnailSize);
            $thumbnailMode  = __resp_array_item($showThumbnail, "mode", $thumbnailMode);
            $thumbnailAttr  = __resp_array_item($showThumbnail, "attr",  $thumbnailAttr);
            $thumbnailImageAttr  = __resp_array_item($showThumbnail, "image-attr",  $thumbnailImageAttr);
        }


        self::thumbnailCheck($thumbnailAttr, get_the_ID());

        self::thumbnailCheck($thumbnailImageAttr, get_the_ID());
        
        if (($showThumbnail && has_post_thumbnail()) || is_attachment()) {
        
            if(is_attachment()){
                $thumbnail = apply_filters("$page_namespace--thumbnail-image", wp_get_attachment_image_src( get_the_ID(), $thumbnailSize)[0] );
            }else{
                $thumbnail = apply_filters("$page_namespace--thumbnail-image", get_the_post_thumbnail_url( get_the_ID(), $thumbnailSize) );
            }
            
            if ($thumbnailContainer) {
                self::trigger("thumbnail-before-container", true);

                if ($thumbnailMode == "background") {
                    $thumbnailAttr["style"] = [
                        "background-image" => "url($thumbnail)"
                    ];
                }

                self::tag($thumbnailContainer, "thumbnail-container", "", [
                    "attr" => $thumbnailAttr
                ])->eo();
            }

            if ($thumbnailMode == "image") {

                self::trigger("thumbnail-before-image", true);

                if(is_attachment()){
                    $thumbnailId = get_the_ID();
                }else{
                    $thumbnailId = get_post_thumbnail_id();
                }

                $thumbnailAlt = get_post_meta($thumbnailId, '_wp_attachment_image_alt', true);

                if (empty($thumbnailAlt)) {
                    $thumbnailAlt = get_the_title();
                }

                $thumbnailImageAttr["attr"]["alt"] = $thumbnailAlt;
                $thumbnailImageAttr["attr"]["src"] = $thumbnail;

                self::tag("img", "thumbnail-image", null, $thumbnailImageAttr)->e();

                self::trigger("thumbnail-after-image", true);
            }


            if ($thumbnailContainer) {

                Tag::close($thumbnailContainer);

                self::trigger("thumbnail-after-container", true);
            }
        }
    }


    /**
     * @since 0.9.0
     */
    static function postList($itemElement = "article", $showThumbnail = true, $thumbnailMode = "image", $thumbnailSize = "large", $thumbnailAttr = [])
    {

        global $page_namespace;



        $itemTags = get_the_tags(get_the_ID());


        if (is_array($itemTags)) {
            $itemTags = array_map(function ($tag) {
                return urldecode("tag-$tag->slug");
            }, $itemTags);
        } else {
            $itemTags = [];
        }


        $item = self::tag($itemElement,  "item",  '',  ["class" => $itemTags]);



        $hasThumb = has_post_thumbnail();


        if ($showThumbnail && $hasThumb) {

            $itemThumbnail = apply_filters("$page_namespace--item-thumbnail", get_the_post_thumbnail_url(get_the_ID(), $thumbnailSize));

            if ($thumbnailMode == "background") {
                $item->set([
                    "style" => [
                        "background-image" => "url('$itemThumbnail')"
                    ]
                ]);
            }
        }


        self::trigger("item-before-container", true, get_the_ID());


        $item->eo();


        self::trigger("item-before-content", true, get_the_ID());


        get_template_part("template-parts/sections/categories");


        // Render the item thumbnail
        if ($showThumbnail && $hasThumb) {

            self::tag("div", "item-thumbnail", '')->filter([
                "class" => "item-thumbnail-classes"
            ])->eo();

            $thumbLink = Tag::a(null, get_the_permalink(), [
                "title" => get_the_title(),
            ]);

            if ($thumbnailMode == "link-background") {
                $thumbLink->set([
                    "style" => [
                        "background-image" => "url('$itemThumbnail')"
                    ]
                ]);
            }

            // Image tag
            if ($thumbnailMode == "image" || $thumbnailMode == "lazy") {

                $image = Tag::img(null, [
                    "alt" => get_the_title()
                ]);

                if ($thumbnailMode == "lazy") {
                    $image->data("src", $itemThumbnail);
                } else {
                    $image->attr("src", $itemThumbnail);
                }

                if (!empty($thumbnailAttr)) {

                    self::thumbnailCheck($thumbnailAttr, get_the_ID());

                    $imgAttr = array_merge($image->get("attr"),  $thumbnailAttr);

                    $image->set(["attr" => $imgAttr]);
                }

                $thumbLink->append($image);
            }

            $thumbLink->e();

            Tag::close("div");
        }

        self::tag("div", "item-body", '')->eo();


        self::trigger("item-before-title", true, get_the_ID());



        self::tag("a", "item-title", get_the_title(), [
            "attr" => ["href" => get_the_permalink()]
        ])->filter([
            "name" => ["resp--item-title-element", "$page_namespace--item-title-element"],
            "attr" => ["resp--item-title-element-attributes"],
            "content" => ["resp--item-title", "$page_namespace--item-title"]
        ], get_the_ID())->e();


        self::trigger("item-after-title", true, get_the_ID());


        self::trigger("item-before-excerpt", true, get_the_ID());


        self::tag("p", "item-excerpt", get_the_excerpt())->filter([
            "name" => ["resp--item-excerpt-element-name", "$page_namespace--item-excerpt-element-name"],
            "attr" => ["resp--item-excerpt-element-attributes"],
            "content" => ["resp--item-excerpt", "$page_namespace--item-excerpt"]
        ], get_the_ID())->render(true);


        self::trigger("item-after-excerpt", true, get_the_ID());


        Tag::close("div");

        get_template_part("template-parts/sections/tags");


        self::trigger("item-after-content", true, get_the_ID());

        Tag::close($itemElement);


        self::trigger("item-after-container", true, get_the_ID());
    }
}
