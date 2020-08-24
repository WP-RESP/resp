<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $section_prefix;

$section_prefix = "latest-posts";



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



$defaultPostsPerPage = get_option( 'posts_per_page' );

$query = new WP_Query([
    'post_type' =>  __resp_tp("post-type", 'post'),
    'posts_per_page' =>  __resp_tp("posts-per-page", $defaultPostsPerPage)
]);



Core::trigger("before-container", true);

Core::tag("div", "container", '')->eo();

Core::trigger("before-content", true);

Core::tag($wrapElement, "content", '')->eo();



if ($query->have_posts()) {

    while ($query->have_posts()) {

        $query->the_post();

        Core::postList($itemElement , $showThumbnail , $thumbnailMode , $thumbnailSize , $thumbnailAttr);

    }

    wp_reset_postdata();
}

Tag::close($wrapElement);

Core::trigger("after-content", true);

Tag::close("div");

Core::trigger("after-container", true);
