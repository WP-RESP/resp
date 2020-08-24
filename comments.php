<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

use \Resp\Core, \Resp\Tag , Resp\RespWalkerComment;

global $page_namespace;


if (post_password_required()) {
    return;
}


$commentsNumber  = get_comment_pages_count();

$showTitle          =  __resp_tp("comments-show-title", false);

// after-form and before-form are supported
$listPosition       = __resp_tp("comments-list-position", "after-form");


if ($showTitle) {

    Core::trigger("comments-before-title", true);

    Core::tag("h2", "comments-title", "")->eo();

    if (!have_comments()) {
       _e('Leave a comment', RESP_TEXT_DOMAIN);
    } elseif ($commentsNumber == 1) {
        printf(_x('One reply on &ldquo;%s&rdquo;', 'comments title', RESP_TEXT_DOMAIN), esc_html(get_the_title()));
    } else {
        printf(
            _nx(
                '%1$s reply on &ldquo;%2$s&rdquo;',
                '%1$s replies on &ldquo;%2$s&rdquo;',
                $commentsNumber,
                'comments title',
                RESP_TEXT_DOMAIN
            ),
            number_format_i18n($commentsNumber),
            esc_html(get_the_title())
        );
    }

    Tag::close("h2");

    Core::trigger("comments-after-title", true);
}





if( $listPosition == "before-form" && $commentsNumber > 0){

    RespWalkerComment::commentsList();

}




$comment_pagination = paginate_comments_links(
    array(
        'echo'      => false,
        'end_size'  => 0,
        'mid_size'  => 0,
        'next_text' => __('Newer Comments', RESP_TEXT_DOMAIN) . ' <span aria-hidden="true">&rarr;</span>',
        'prev_text' => '<span aria-hidden="true">&larr;</span> ' . __('Older Comments', RESP_TEXT_DOMAIN),
    )
);

if ($comment_pagination) {

    Core::tag("nav", "comments-pagination", "")->eo();

    echo wp_kses_post($comment_pagination);

    Tag::close("nav");
}




if (comments_open() || pings_open()) {

    RespWalkerComment::commentForm();
    
} elseif (is_single()) {

    Core::tag("p", "comments-closed", __('Comments are closed.', RESP_TEXT_DOMAIN))->e();
    
}


if( $listPosition == "after-form" && $commentsNumber > 0){

    RespWalkerComment::commentsList();

}
