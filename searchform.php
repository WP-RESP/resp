<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

use Resp\Core, Resp\Tag;

Core::tag("form", "searchform", "", [
    "attr" => [
        "id" => "searchform",
        "role" => "search",
        "action" => "/",
        "method" => "get"
    ]
])->eo();

Core::trigger("searchform-before-container", true);

Core::tag("div", "searchform-container", "")->eo(); 

Core::trigger("searchform-before-contents", true);

Core::trigger("searchform-before-input", true);

Core::tag("input", "searchform-input", null, [
    "id" => "s",
    "name" => "s",
    "type" => "text",
    "placeholder" => Core::text(esc_html__("Search...", "resp"), "searchform-placeholder", false)
])->e();

Core::trigger("searchform-after-input", true);

Core::tag("button", "searchform-submit", "", [
   "attr" => [ "type" => "submit"]
])->render(true, false);

Core::text(esc_html__("Go", "resp"), "searchform-submit");

Core::trigger("searchform-after-submit", true);

Tag::close("button");

Core::trigger("searchform-after-contents", true);

Tag::close("div"); 

Core::trigger("searchform-after-container", true);

Tag::close("form"); 
