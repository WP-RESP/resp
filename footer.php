<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

use Resp\Core, Resp\Tag;

global $page_namespace;

Core::trigger("main-after-content", true);

Tag::close("main");

Core::tag("footer", "footer", "", [
    "attr" => ["role" => "contentinfo"]
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
