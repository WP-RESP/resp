<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
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
       _e('Leave a comment', "resp");
    } elseif ($commentsNumber == 1) {
        printf(
             /* translators: %s is replaced with "string" */
            _x('One reply on &ldquo;%s&rdquo;', 'comments title', "resp"), 
            esc_html(get_the_title()));
    } else {
        printf(
            _nx(
                /* translators: %1$s is replaced with "string", %2$s is replaced with "string" */
                '%1$s reply on &ldquo;%2$s&rdquo;',
                '%1$s replies on &ldquo;%2$s&rdquo;',
                $commentsNumber,
                'comments title',
                "resp"
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
        'next_text' => esc_html__('Newer Comments', "resp") . ' <span aria-hidden="true">&rarr;</span>',
        'prev_text' => '<span aria-hidden="true">&larr;</span> ' . esc_html__('Older Comments', "resp"),
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

    Core::tag("p", "comments-closed", esc_html__('Comments are closed.', "resp"))->e();
    
}


if( $listPosition == "after-form" && $commentsNumber > 0){

    RespWalkerComment::commentsList();

}
