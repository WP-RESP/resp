<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

namespace Resp\Components;

use Resp\Component;

class Searchform extends Component
{

    function __construct()
    {
        add_shortcode('resp-searchform', [$this, 'searchFormShortcode']);
    }

    /**
     * @since 0.9.0
     */
    function searchFormShortcode($atts = [], $content = null)
    {
        return get_search_form(false);
    }
}
