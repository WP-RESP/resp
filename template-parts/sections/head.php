<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

global $page_namespace;

$GLOBALS["respIs404"] = is_404();

$GLOBALS["respIsHome"] = is_home();

get_header();

if (defined("$page_namespace--custom")) {
    do_action("custom_$page_namespace");
    get_footer();
}
