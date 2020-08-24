<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

global $wp_query, $page_namespace;

Resp\Core::tag("div", "paginate", '')->eo();

echo paginate_links(apply_filters("$page_namespace--paginate-params", [
    'base' => str_replace(PHP_INT_MAX, '%#%', esc_url(get_pagenum_link(PHP_INT_MAX))),
    'format' => '?paged=%#%',
    'current' => max(1, get_query_var('paged')),
    'total' => $wp_query->max_num_pages,
    'prev_text' => apply_filters("$page_namespace--paginate-prev-value", __('Previous', RESP_TEXT_DOMAIN)),
    'next_text' => apply_filters("$page_namespace--paginate-next-value", __('Next', RESP_TEXT_DOMAIN)),
]));

Resp\Tag::close("div");
