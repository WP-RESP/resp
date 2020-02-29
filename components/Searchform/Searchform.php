<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
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
