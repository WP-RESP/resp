<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $section_prefix;

$item_tags = get_the_tags(get_the_ID());

$useSectionName = __resp_tp("tags-use-section" , false );

$prefix = (!empty($section_prefix) && $useSectionName) ?  "$section_prefix-tags" : "tags";

Core::trigger("$prefix-before-container");

if ($item_tags) {
    
    Core::tag("ul", "$prefix-container", '')->eo();

    foreach ($item_tags as $tag) {

        Core::tag("li" , "$prefix-item" , "" , [
            "class" => [urldecode("tag-$tag->slug")]
        ])->eo();

        Core::tag("a" , "$prefix-item-link", $tag->name , [
            "attr" => [
                "href" => esc_url(get_tag_link($tag)),
                "rel" => "category tag"
            ]
        ])->e();    

        Tag::close("li");
    }

    Tag::close("ul");

}

Core::trigger("$prefix-after-container");
