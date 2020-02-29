<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

$format = get_post_format();

Resp\Core::initPage("single" . ($format ? "-$format" : ""));

get_template_part("template-parts/pages/single");
