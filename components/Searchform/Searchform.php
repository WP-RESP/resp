<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp\Components;

use Resp\Component;

defined('RESP_VERSION') or die;

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
