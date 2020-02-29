<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

use Resp\Core;

global $page_namespace;

Core::initPage("under-construction");

Core::trigger("before-container");

Core::tag("div", "container", '')->eo();

Core::trigger("before-content");

Core::tag("h1", "title", apply_filters("$page_namespace--title", __("Under Construction", RESP_TEXT_DOMAIN)))->e();

Core::tag("p", "message", apply_filters("$page_namespace--message", __("Sorry, We are running some updates. We'll be back soon.", RESP_TEXT_DOMAIN)))->e();

Core::trigger("after-content");

Resp\Tag::close("div");

Core::trigger("after-container");
