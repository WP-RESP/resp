<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

use Resp\Tag, Resp\Core;

global $page_namespace;

Core::initPage("page-404");

Core::trigger("before-container", true);

Core::tag("div", "container", '')->eo();

Core::trigger("before-content", true);

Core::tag("div", "content", "")->eo();

Core::trigger("before-code");

Core::tag("h1", "code", Core::text(esc_html__("404", "resp"), "code", false))->e();

Core::trigger("before-title");

Core::tag("h3", "title", Core::text(esc_html__("Page Not Found", "resp"), "title", false))->e();

Core::trigger("before-message");

Core::tag(
    "p",
    "message",
    Core::text(
        esc_html__("The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.", "resp"),
        "message",
        false
    )
)->e();

Core::trigger("before-back-to-homepage");

Core::tag("a", "back-to-homepage", Core::text(
    esc_html__("Back To Homepage", "resp"),
    "back-to-homepage",
    false
))->set(["attr" => [
    "href" => get_home_url()
]])->e();

Core::trigger("after-back-to-homepage");

Tag::close("div");

Core::trigger("after-content");

Tag::close("div");

Core::trigger("after-container");

get_footer();
