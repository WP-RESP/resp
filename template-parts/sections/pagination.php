<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

global $wp_query, $page_namespace;

defined('RESP_VERSION') or die;

Resp\Core::tag("div", "paginate", '')->eo();

echo paginate_links(apply_filters("$page_namespace--paginate-params", [
    'base' => str_replace(PHP_INT_MAX, '%#%', esc_url(get_pagenum_link(PHP_INT_MAX))),
    'format' => '?paged=%#%',
    'current' => max(1, get_query_var('paged')),
    'total' => $wp_query->max_num_pages,
    'prev_text' => apply_filters("$page_namespace--paginate-prev-value", esc_html__('Previous', "resp")),
    'next_text' => apply_filters("$page_namespace--paginate-next-value", esc_html__('Next', "resp")),
]));

Resp\Tag::close("div");
