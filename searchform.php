<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
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
    "placeholder" => Core::text(__("Search...", RESP_TEXT_DOMAIN), "searchform-placeholder", false)
])->e();

Core::trigger("searchform-after-input", true);

Core::tag("button", "searchform-submit", "", [
   "attr" => [ "type" => "submit"]
])->render(true, false);

Core::text(__("Go", RESP_TEXT_DOMAIN), "searchform-submit");

Core::trigger("searchform-after-submit", true);

Tag::close("button");

Core::trigger("searchform-after-contents", true);

Tag::close("div"); 

Core::trigger("searchform-after-container", true);

Tag::close("form"); 
