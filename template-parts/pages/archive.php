<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $section_prefix;


$section_prefix = "archive";



$wrapElement =  __resp_tp("wrap-element", "div");

$itemElement =  __resp_tp("item-element", "article");

$showThumbnail = __resp_tp("item-thumbnail", true);

$thumbnailSize  = __resp_tp("item-thumbnail-size", "large");

// "image" , "lazy" , "background" and "link-background" are supported 
$thumbnailMode  = __resp_tp("item-thumbnail-mode", "image");

$thumbnailAttr  = __resp_tp("item-thumbnail-attr",  []);



// override settings
if (is_array($showThumbnail)) {
    $thumbnailSize = __resp_array_item($showThumbnail, "size", $thumbnailSize);
    $thumbnailMode  = __resp_array_item($showThumbnail, "mode", $thumbnailMode);
    $thumbnailAttr  = __resp_array_item($showThumbnail, "attr",  $thumbnailAttr);
}



Core::thumbnailCheck($thumbnailAttr, get_the_ID());



if (empty($page_namespace)) {

    $tax = get_queried_object();

    $description = term_description($tax->ID);

    if (isset($tax) && isset($tax->slug)) {
        $page_namespace = urldecode("archive-$tax->slug");
    } else {
        $page_namespace = "unknown";
    }
}

$page_namespace = apply_filters("resp-archive--page-namespace", $page_namespace);

Core::initPage($page_namespace);

Core::trigger("before-container", true);

Core::tag("div", "container", '')->eo();

Core::trigger("before-content", true);

Core::tag($wrapElement, "content", '')->eo();

Core::trigger("before-title", true);

if (!have_posts() && isset($_REQUEST['s'])) {

    Core::tag("h2", "title",  sprintf(__("No results are available for \"%s\"", RESP_TEXT_DOMAIN), $_REQUEST['s']))->e();
} else {

    if (isset($tax)) {
        Core::tag("h2", "title",  __($tax->name))->e();
    } else if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
        Core::tag("h2", "title",  sprintf(__("Results for \"%s\"", RESP_TEXT_DOMAIN), $_REQUEST['s']))->e();
    }
}

Core::trigger("after-title", true);

if (!empty($description)) {
    Core::trigger("before-description", true);
    Core::tag("h4", "description",  __(wp_strip_all_tags($description)))->e();
    Core::trigger("after-description", true);
}


while (have_posts()) {

    the_post();

    Core::postList($itemElement, $showThumbnail, $thumbnailMode, $thumbnailSize , $thumbnailAttr);
}


Tag::close($wrapElement);

Core::trigger("after-content", true);

get_template_part("template-parts/sections/pagination");

Tag::close("div");

Core::trigger("after-container", true);

get_footer();