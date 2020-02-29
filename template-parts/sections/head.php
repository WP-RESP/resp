<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

global $page_namespace;

get_header();

if (defined("$page_namespace--custom")) {

    do_action("custom_$page_namespace");

    get_footer();

}
