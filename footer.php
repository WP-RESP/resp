<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

use Resp\Core, Resp\Tag;

global $page_namespace;

Core::trigger("main-after-content", true);

Tag::close("main");

Tag::create([
    "name" => "footer",
    "class" => ["resp-footer--container", "$page_namespace--footer-container"],
    "attr" => ["role" => "contentinfo"]
])->filter([
    "class" => ["$page_namespace--footer-container-classes", "footer-classes"]
])->eo();

Core::trigger("footer-before-content", true);

if (!__resp_master_sidebar_disabled("footer")) {

    Core::trigger("footer-before-master", true);

    if (is_active_sidebar('master-footer')) {
        dynamic_sidebar('master-footer');
    }

    Core::trigger("footer-after-master", true);
}

Core::trigger("footer-after-content", true);

Tag::close("footer");

wp_footer();

Core::trigger("body-after-content", true);

Tag::close("body");

Tag::close("html");
