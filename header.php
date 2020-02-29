<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

use Resp\Core, Resp\Tag;

global $page_namespace;

echo "<!doctype html>";

Core::tag("html", "html", "", [
    "attr" => ["lang" => get_locale()]
])->eo();

?>

<head>

    <meta charset="<?php echo get_bloginfo("charset"); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">


    <?php

    Core::trigger("meta" , true);
    
    Tag::create("title")->eo();

    wp_title();

    Tag::close("title");

    wp_head();

    Core::trigger("head" , true);

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
