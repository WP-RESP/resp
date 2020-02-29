<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

use Resp\Core, Resp\Tag;

global $page_namespace, $section_prefix;

$item_tags = get_the_tags(get_the_ID());

if ($item_tags) {

    Core::trigger(empty($section_prefix) ? "tags-before-container" : "$section_prefix-tags-before-container");

    Core::tag("ul", empty($section_prefix) ? "tags" : "$section_prefix-tags", '')->eo();

    foreach ($item_tags as $tag) {

        Tag::create([
            "name" => "li",
            "class" => ["tag-$tag->slug"]
        ])->append(
            new Tag([
                "name" => "a",
                "content" => $tag->name,
                "attr" => [
                    "href" => esc_url(get_tag_link($tag)),
                    "rel" => "category tag"
                ]
            ])
        )->render(true);
    }

    Tag::close("ul");

    Core::trigger(empty($section_prefix) ? "tags-after-container" : "$section_prefix-tags-after-container");
}