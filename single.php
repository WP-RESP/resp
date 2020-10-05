<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

$format = get_post_format();

Resp\Core::initPage("single" . ($format ? "-$format" : ""));

get_template_part("template-parts/pages/single");
