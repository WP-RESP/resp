<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $section_prefix;

$item_categories = get_the_category();

if (empty($item_categories)) {
    return;
}

$useSectionName = __resp_tp("categories-use-section" , false );


$prefix = (!empty($section_prefix) && $useSectionName) ?  "$section_prefix-categories" : "categories";

Core::trigger("$prefix-before-container");

Core::tag("ul", "$prefix-container", '')->eo();

foreach ($item_categories as $category) {

    Core::tag("li" , "$prefix-item" , "" , [
        "class" => [urldecode("category-$category->slug")]
    ])->eo();

    Core::tag("a" , "$prefix-item-link", $category->name , [
        "attr" => [
            "href" => esc_url(get_category_link($category->term_id)),
            "rel" => "category tag"
        ]
    ])->e();

    Tag::close("li");

}

Tag::close("ul");

Core::trigger("$prefix-after-container");
