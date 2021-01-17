<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $post;

$page_namespace_complete = "$page_namespace-{$post->post_name}";

$is_front_page = is_front_page();

// "after-post-title" and "before-post-content" are supported
$thumbnailPos  = __resp_tp("thumbnail-position", "before-post-content");

if (!$is_front_page) {

    Core::trigger("before-container", true);

    Core::tag("div", "container", "")->addClass(urldecode($post->post_name))
        ->filter([
            "class" => "$page_namespace_complete--container-classes"
        ])->eo();

    Core::trigger("before-content", true);

    Core::tag("article", "content", "")
        ->filter([
            "class" => "$page_namespace_complete--article-classes"
        ])->eo();
}


while (have_posts()) {

    the_post();

    if (!$is_front_page) {

        if($thumbnailPos == "before-post-content")
        {
            Core::postThumbnail();
        }

        if (!is_page()) {
            get_template_part("template-parts/sections/categories");
        }

        Core::trigger("before-posttitle", true);

        Core::tag("h1", "title", get_the_title())
            ->filter([
                "content" => ["$page_namespace--title-value"],
                "name" => ["$page_namespace--element-name"],
                "class" => "$page_namespace_complete--title-classes"
            ], get_the_ID())
            ->e();

        Core::trigger("after-posttitle", true);

        if($thumbnailPos == "after-post-title")
        {
            Core::postThumbnail();
        }

    }



    Core::trigger("before-postcontent", true);

    if(is_attachment()){

        Core::tag("h2", "caption", wp_get_attachment_caption())
            ->filter([
                "content" => ["$page_namespace--caption-value"],
                "class" => "$page_namespace_complete--caption-classes"
            ], get_the_ID())
            ->e();

    }
    

    the_content();

    Core::trigger("after-postcontent", true);

    if (!$is_front_page) {

        if (!is_page()) {
            get_template_part("template-parts/sections/tags");
        }

        get_template_part("template-parts/sections/comments");
    }
}

if (!$is_front_page) {

    Tag::close("article");

    Core::trigger("after-content", true);

    if (comments_open() || get_comments_number()) {

        $noComments = __resp_tp("no-comments" , false);

        if(!$noComments){
            comments_template();
        }
        
    }

    // closing the container
    Tag::close("div");

    Core::trigger("after-container", true);
}

get_footer();
