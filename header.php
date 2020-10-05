<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

use Resp\Core, Resp\Tag;

global $page_namespace;

echo "<!DOCTYPE html>";

Core::tag("html", "html", "", [
    "attr" => ["lang" => get_locale()]
])->eo();

?>

<head>

    <?php

    Core::trigger("before-head" , true);
    
    Tag::create("title")->eo();

    wp_title();

    Tag::close("title");

    wp_head();

    Core::trigger("after-head" , true);

    ?>

</head>

<?php

Core::tag("body", "body", "")->eo();

Core::trigger("body-before-content", true);

Core::tag("header", "header", "", [
    "attr" => ["role" => "banner"]
])->eo();

Core::trigger("header-before-content", true);

if (!__resp_master_sidebar_disabled("header")) {

    Core::trigger("header-before-master", true);

    if (is_active_sidebar('master-header')) {
        dynamic_sidebar('master-header');
    }

    Core::trigger("header-after-master", true);
}

Core::trigger("header-after-content", true);

Tag::close("header");

Core::tag("main", "main", "", [
    "attr" => ["role" => "main"]
])->eo();

Core::trigger("main-before-content", true);
