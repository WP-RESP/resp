<?php

/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */

namespace Resp;

use Resp\Core, Resp\Tag;

class RespWalkerComment extends \Walker_Comment
{

    /** 
     * @since 0.9.0
     */
    static function generateField($name, $label, $type, $req, $commenter, $attr = [],  $html5 = true)
    {

        $selector = $name == "author" ? "comment_author" : "comment_author_$name";

        $container = Core::tag("div", "comments-form-$name-container", "", [
            "class" => "comment-form-$name"
        ]);


        $inputLabel = Core::tag("label", "comments-form-$name-label", __('Name'), [
            "attr" => [
                "for" => "$name"
            ]
        ]);

        if ($req) {
            $inputLabel->raw($label . "<span class=\"required\">*</span>");
        }

        $input = Core::tag("input", "comments-form-$name", null, [
            "id" => "$name",
            "attr" => array_merge([
                "name" => "$name",
                "type" => $html5 ? $type : "text",
                "value" => esc_attr($commenter[$selector])
            ], $attr, ($req  ? ["required" => "required"] : []))
        ]);


        return $container->append([$inputLabel, $input]);
    }


    /** 
     * @since 0.9.0
     */
    static function commentForm($args = array(), $post_id = null)
    {

        if (null === $post_id) {
            $post_id = get_the_ID();
        }

        // Exit the function when comments for the post are closed.
        if (!comments_open($post_id)) {

            // Fires after the comment form if comments are closed.
            do_action('comment_form_comments_closed');

            return;
        }

        $commenter     = wp_get_current_commenter();
        $user          = wp_get_current_user();
        $user_identity = $user->exists() ? $user->display_name : '';

        $args = wp_parse_args($args);
        if (!isset($args['format'])) {
            $args['format'] = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';
        }

        $req      = get_option('require_name_email');
        $html_req = ($req ? " required='required'" : '');
        $html5    = 'html5' === $args['format'];




        $fields = array(
            'author' => self::generateField("author", __('Name'), "text", $req, $commenter, [
                "maxlength" => 245,
                "size" => 30
            ])->toString(),
            'email'  => self::generateField("email", __('Email'), "email", $req, $commenter, [
                "maxlength" => 100,
                "size" => 30,
                "aria-describedby" => "email-notes"
            ])->toString(),
            'url' => self::generateField("url", __('Website'), "url", false, $commenter, [
                "maxlength" => 200,
                "size" => 30
            ])->toString()
        );

        if (has_action('set_comment_cookies', 'wp_set_comment_cookies') && get_option('show_comments_cookies_opt_in')) {

            $consent = empty($commenter['comment_author_email']) ? '' : ' checked="checked"';

            $cookieLabel = Core::tag("label", "comments-form-cookies-consent-label",  __('Save my name, email, and website in this browser for the next time I comment.'), [
                "attr" => [
                    "for" => "wp-comment-cookies-consent"
                ]
            ]);


            $cookieLabel->prepend(sprintf(
                '<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s />',
                $consent
            ));


            $fields['cookies'] = Core::tag("div", "comments-form-cookies-consent-container", "")
                ->append($cookieLabel)->toString();


            // Ensure that the passed fields include cookies consent.
            if (isset($args['fields']) && !isset($args['fields']['cookies'])) {
                $args['fields']['cookies'] = $fields['cookies'];
            }
        }

        $required_text = sprintf(
            /* translators: %s: Asterisk symbol (*). */
            ' ' . __('Required fields are marked %s'),
            '<span class="required">*</span>'
        );

        /**
         * Filters the default comment form fields.
         * @param string[] $fields Array of the default comment fields.
         */
        $fields = apply_filters('comment_form_default_fields', $fields);

        $defaults = array(
            'fields'               => $fields,
            'comment_field'        => '',
            'must_log_in'          => sprintf(
                '<p class="must-log-in">%s</p>',
                sprintf(
                    /* translators: %s: Login URL. */
                    __('You must be <a href="%s">logged in</a> to post a comment.'),
                    /** This filter is documented in wp-includes/link-template.php */
                    wp_login_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))
                )
            ),
            'logged_in_as'         => sprintf(
                '<p class="logged-in-as">%s</p>',
                sprintf(
                    /* translators: 1: Edit user link, 2: Accessibility text, 3: User name, 4: Logout URL. */
                    __('<a href="%1$s" aria-label="%2$s">Logged in as %3$s</a>. <a href="%4$s">Log out?</a>'),
                    get_edit_user_link(),
                    /* translators: %s: User name. */
                    esc_attr(sprintf(__('Logged in as %s. Edit your profile.'), $user_identity)),
                    $user_identity,
                    /** This filter is documented in wp-includes/link-template.php */
                    wp_logout_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))
                )
            ),
            'comment_notes_before' => sprintf(
                '<p class="comment-notes">%s%s</p>',
                sprintf(
                    '<span id="email-notes">%s</span>',
                    __('Your email address will not be published.')
                ),
                ($req ? $required_text : '')
            ),
            'comment_notes_after'  => '',
            'action'               => site_url('/wp-comments-post.php'),
            'id_form'              => 'commentform',
            'id_submit'            => 'submit',
            'class_form'           => 'comment-form',
            'class_submit'         => 'submit',
            'name_submit'          => 'submit',
            'title_reply'          => __('Leave a Reply'),
            /* translators: %s: Author of the comment being replied to. */
            'title_reply_to'       => __('Leave a Reply to %s'),
            'title_reply_before'   => '',
            'title_reply_after'    => '',
            'cancel_reply_before'  => '',
            'cancel_reply_after'   => '',
            'cancel_reply_link'    => __('Cancel reply'),
            'label_submit'         => __('Post Comment'),
            'submit_button'        => '',
            'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
            'format'               => 'xhtml',
        );

        /**
         * Filters the comment form default arguments.
         *
         * Use {@see 'comment_form_default_fields'} to filter the comment fields.
         * @param array $defaults The default comment form arguments.
         */
        $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

        // Ensure that the filtered args contain all required default values.
        $args = array_merge($defaults, $args);

        // Remove aria-describedby from the email field if there's no associated description.
        if (false === strpos($args['comment_notes_before'], 'id="email-notes"')) {
            $args['fields']['email'] = str_replace(
                ' aria-describedby="email-notes"',
                '',
                $args['fields']['email']
            );
        }

        /**
         * Fires before the comment form.
         */
        do_action('comment_form_before');

        Core::trigger("comments-form-before-container", true);

        Core::tag("div", "comments-form", "", [
            "id" => "respond"
        ])->eo();


        Core::trigger("comments-form-before-replytitle", true);

        if (!empty($args['title_reply_before'])) {
            echo $args['title_reply_before'];
        } else {
            Core::tag("h3", "comments-form-replytitle", "")->eo();
        }


        comment_form_title($args['title_reply'], $args['title_reply_to']);


        if (!empty($args['cancel_reply_before'])) {
            echo $args['cancel_reply_before'];
        } else {
            Core::tag("small", "comments-form-replycancel", "")->eo();
        }



        cancel_comment_reply_link($args['cancel_reply_link']);



        if (!empty($args['cancel_reply_after'])) {
            echo $args['cancel_reply_after'];
        } else {
            Tag::close("small");
        }




        if (!empty($args['title_reply_after'])) {
            echo $args['title_reply_after'];
        } else {
            Tag::close("h3");
        }


        Core::trigger("comments-form-after-replytitle", true);



        if (get_option('comment_registration') && !is_user_logged_in()) :

            echo $args['must_log_in'];
            /**
             * Fires after the HTML-formatted 'must log in after' message in the comment form.
             */
            do_action('comment_form_must_log_in_after');

        else :

            Core::trigger("comments-form-before-form", true);


            Core::tag("form", "comments-form", "", [
                "id" => esc_attr($args['id_form']),
                "class" => [
                    esc_attr($args['class_form'])
                ],
                "attr" => array_merge([
                    "method" => "post",
                    "action" => esc_url($args['action']),
                ], ($html5 ? ['novalidate' => true] : []))
            ])->eo();


            /**
             * Fires at the top of the comment form, inside the form tag.
             */
            do_action('comment_form_top');



            if (is_user_logged_in()) :

                /**
                 * Filters the 'logged in' message for the comment form for display.
                 *
                 * @param string $args_logged_in The logged-in-as HTML-formatted message.
                 * @param array  $commenter      An array containing the comment author's
                 *                               username, email, and URL.
                 * @param string $user_identity  If the commenter is a registered user,
                 *                               the display name, blank otherwise.
                 */
                echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity);

                /**
                 * Fires after the is_user_logged_in() check in the comment form.
                 *
                 * @param array  $commenter     An array containing the comment author's
                 *                              username, email, and URL.
                 * @param string $user_identity If the commenter is a registered user,
                 *                              the display name, blank otherwise.
                 */
                do_action('comment_form_logged_in_after', $commenter, $user_identity);

            else :

                echo $args['comment_notes_before'];

            endif;


            if (!empty($args['comment_field'])) {
                $comment_fields = [
                    'comment' => $args['comment_field']
                ];
            } else {

                $cf = Core::tag("div", "comments-form-textarea-container", "", [
                    "children" => [
                        Tag::labelFor("comment", _x('Comment', 'noun')),
                        Core::tag("textarea", "comments-form-textarea", "", [
                            "id" => "comment",
                            "attr" => [
                                "name" => "comment",
                                "cols" => 45,
                                "rows" => 8,
                                "maxlength" => 65525,
                                "required" => "required"
                            ]
                        ])
                    ]
                ]);


                $comment_fields = [
                    'comment' => $cf->toString()
                ];
            }


            // Prepare an array of all fields, including the textarea.

            $comment_fields = $comment_fields + ((array) $args['fields']);



            /**
             * Filters the comment form fields, including the textarea.
             * 
             * @param array $comment_fields The comment fields.
             */
            $comment_fields = apply_filters('comment_form_fields', $comment_fields);

            // Get an array of field names, excluding the textarea
            $comment_field_keys = array_diff(array_keys($comment_fields), array('comment'));

            // Get the first and the last field name, excluding the textarea
            $first_field = reset($comment_field_keys);
            $last_field  = end($comment_field_keys);


            Core::trigger("comments-form-before-fields", true);

            foreach ($comment_fields as $name => $field) {

                if ('comment' === $name) {

                    /**
                     * Filters the content of the comment textarea field for display.
                     * 
                     * @param string $args_comment_field The content of the comment textarea field.
                     */
                    echo apply_filters('comment_form_field_comment', $field);

                    echo $args['comment_notes_after'];
                } elseif (!is_user_logged_in()) {

                    if ($first_field === $name) {
                        /**
                         * Fires before the comment fields in the comment form, excluding the textarea.
                         */


                        do_action('comment_form_before_fields');
                    }

                    /**
                     * Filters a comment form field for display.
                     *
                     * The dynamic portion of the filter hook, `$name`, refers to the name
                     * of the comment form field. Such as 'author', 'email', or 'url'.
                     * 
                     * @param string $field The HTML-formatted output of the comment form field.
                     */
                    echo apply_filters("comment_form_field_{$name}", $field) . "\n";

                    if ($last_field === $name) {
                        /**
                         * Fires after the comment fields in the comment form, excluding the textarea.
                         * 
                         */
                        do_action('comment_form_after_fields');

                        Core::trigger("comments-form-after-fields", true);
                    }
                }
            }

            Core::trigger("comments-form-after-fields", true);

            Core::trigger("comments-form-before-submit", true);

            if (!empty($args['submit_button'])) {

                $submit_button = sprintf(
                    $args['submit_button'],
                    esc_attr($args['name_submit']),
                    esc_attr($args['id_submit']),
                    esc_attr($args['class_submit']),
                    esc_attr($args['label_submit'])
                );
            } else {

                $submit_button = Core::tag("input", "comments-form-submit", null, [
                    "id" => $args['id_submit'],
                    "class" => [$args['class_submit']],
                    "attr" => [
                        "type" => "submit",
                        "name" => $args['name_submit'],
                        "value" => $args['label_submit']
                    ]
                ])->render();
            }

            Core::trigger("comments-form-after-submit", true);


            /**
             * Filters the submit button for the comment form to display.
             * 
             * @param string $submit_button HTML markup for the submit button.
             * @param array  $args          Arguments passed to comment_form().
             */
            $submit_button = apply_filters('comment_form_submit_button', $submit_button, $args);

            $submit_field = sprintf(
                $args['submit_field'],
                $submit_button,
                get_comment_id_fields($post_id)
            );

            /**
             * Filters the submit field for the comment form to display.
             *
             * The submit field includes the submit button, hidden fields for the
             * comment form, and any wrapper markup.
             *
             * @param string $submit_field HTML markup for the submit field.
             * @param array  $args         Arguments passed to comment_form().
             */
            echo apply_filters('comment_form_submit_field', $submit_field, $args);

            /**
             * Fires at the bottom of the comment form, inside the closing </form> tag.
             *
             * @param int $post_id The post ID.
             */
            do_action('comment_form', $post_id);

            Tag::close("form");

            Core::trigger("comments-form-after-form", true);

        endif;


        Tag::close("div");

        /**
         * Fires after the comment form.
         */
        do_action('comment_form_after');

        Core::trigger("comments-form-after-container", true);
    }

    /** 
     * @since 0.9.0
     */
    static function commentsList()
    {

        $avatarSize   = __resp_tp("comments-avatar-size", 32);
        $listStyle    = __resp_tp("comments-list-style", "ul");

        Core::trigger("comments-list-before-container", true);

        Core::tag($listStyle, "comments-list", "")->eo();

        wp_list_comments([
            'walker'      => new \Resp\RespWalkerComment(),
            'style' => $listStyle,
            'avatar_size' => $avatarSize
        ]);

        Tag::close($listStyle);

        Core::trigger("comments-list-after-container", true);
    }


    /**
     * @since 0.9.0
     */
    protected function is_comment_by_post_author($comment)
    {
        return get_the_author_meta('display_name') === get_comment_author($comment);
    }


    /**
     * @since 0.9.0
     */
    protected function comment($comment, $depth, $args)
    {

        if ('div' == $args['style']) {
            $tag       = 'div';
            $add_below = 'comment';
        } else {
            $tag       = 'li';
            $add_below = 'div-comment';
        }

        $commenter = wp_get_current_commenter();

        if ($commenter['comment_author_email']) {
            $moderation_note = __('Your comment is awaiting moderation.');
        } else {
            $moderation_note = __('Your comment is awaiting moderation. This is a preview, your comment will be visible after it has been approved.');
        }

        $commentID = get_comment_ID();


        Core::tag($tag, "comments-list-item", "", [
            "id" => "comment-$commentID",
            "class" => get_comment_class($this->has_children ? 'parent' : '', $comment)
        ])->eo();



        if ('div' != $args['style']) {
            Core::tag("div", "comments-list-item-body", "", [
                "id" => "div-comment-$commentID",
                "class" => "comment-body"
            ])->eo();
        }



        Core::trigger("comments-list-item-before-avatar", true);


        if (0 != $args['avatar_size']) {
            echo get_avatar($comment, $args['avatar_size']);
        }

        Core::trigger("comments-list-item-after-avatar", true);


        Core::trigger("comments-list-item-before-details", true);

        printf(
            /* translators: %s: Comment author link. */
            __('%s <span class="says">says:</span>'),
            sprintf('<cite class="fn">%s</cite>', get_comment_author_link($comment))
        );

        if ('0' == $comment->comment_approved) {
            
            Tag::br()->e();

            Tag::create([
                "name" => "em",
                "class" => "comment-awaiting-moderation",
                "content" => $moderation_note
            ])->e();

            Tag::br()->e();

        }


        Tag::create([
            "name" => "div",
            "class" => "comment-meta commentmetadata"
        ])->eo();

        Tag::a(sprintf(__('%1$s at %2$s'), get_comment_date('', $comment), get_comment_time()), esc_url(get_comment_link($comment, $args)))->e();


        edit_comment_link(__('(Edit)'), '&nbsp;&nbsp;', '');

        Tag::close("div");


        comment_text(
            $comment,
            array_merge(
                $args,
                array(
                    'add_below' => $add_below,
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                )
            )
        );

        comment_reply_link(
            array_merge(
                $args,
                array(
                    'add_below' => $add_below,
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'before'    => '<div class="reply">',
                    'after'     => '</div>',
                )
            )
        );

        Core::trigger("comments-list-item-after-details", true);

        if ('div' != $args['style']) {

            Tag::close("div");
        }
    }





    /*
    protected function html5_comment($comment, $depth, $args)
    {

        $tag = ('div' === $args['style']) ? 'div' : 'li';

        $comment_author_url  = get_comment_author_url($comment);
        $avatar              = get_avatar($comment, $args['avatar_size']);
        $comment_timestamp = sprintf(__('%1$s at %2$s', RESP_TEXT_DOMAIN), get_comment_date('', $comment), get_comment_time());

        $commenter = wp_get_current_commenter();

        if ( $commenter['comment_author_email'] ) {
            $moderation_note = __( 'Your comment is awaiting moderation.' );
        } else {
            $moderation_note = __( 'Your comment is awaiting moderation. This is a preview, your comment will be visible after it has been approved.' );
        }


        Core::tag($tag , "comments-item-container" , "" , [
            "id" => "comment-" . get_comment_ID(),
            "class" => $this->has_children ? "parent" : ""
        ])->eo();


        Core::tag("article" , "comments-item-body" , "" , [
            "id" => "comment-" . get_comment_ID(),
            "class" => "comment-body"
        ])->eo();


        Tag::create("footer")->add_class("comment-meta")->eo();

        Tag::create("div")->add_class("comment-author vcard")->eo();

        if (0 != $args['avatar_size']) {
            if (empty($comment_author_url)) {
                echo $avatar;
            } else {
                Tag::a($avatar, $comment_author_url, ["rel" => "external nofollow"])->add_class("url")->eo();
            }
        }

        if ($this->is_comment_by_post_author($comment)) {
            Tag::span("✓")->add_class("post-author-badge")->e();
        }

        printf(
            /* translators: %s: comment author link //
            wp_kses(
                __('%s <span class="screen-reader-text says">says:</span>', RESP_TEXT_DOMAIN),
                array(
                    'span' => array(
                        'class' => array(),
                    ),
                )
            ),
            '<b class="fn">' . get_comment_author_link($comment) . '</b>'
        );

        if (!empty($comment_author_url)) {
            Tag::close("a");
        }

        Tag::close("div");

        Tag::create("div")->add_class("comment-metadata")->eo();

        Tag::a("", esc_url(get_comment_link($comment, $args)))->append(Tag::create([
            "name" => "time",
            "content" =>  $comment_timestamp,
            "attr" => [
                "datetime" => get_comment_time('c'),
                "title" =>  $comment_timestamp
            ]
        ]))->e();

        edit_comment_link(__('Edit',  RESP_TEXT_DOMAIN));

        Tag::close("div");

        if ('0' == $comment->comment_approved) {
            Tag::p(__('Your comment is awaiting moderation.', RESP_TEXT_DOMAIN))->add_class("comment-awaiting-moderation")->e();
        }
        Tag::close("footer");

        Tag::create("div")->raw(get_comment_text())->e();

        Tag::close("article");

        comment_reply_link(
            array_merge(
                $args,
                array(
                    'add_below' => 'div-comment',
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'before'    => '<div class="comment-reply">',
                    'after'     => '</div>',
                )
            )
        );
    }
    */
}
