<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

defined('RESP_VERSION') or die;

class RichTextEditorControl extends \WP_Customize_Control
{

    public $type = 'richtext';

    function enqueue()
    {
        $js = FileManager::getRespAssetsDirectoryUri("js/richtext-control.js");
        wp_enqueue_script('resp-richtext-control-js', $js, array('jquery'), RESP_VERSION, true);
        wp_enqueue_editor();
    }

    function to_json()
    {
        parent::to_json();
        $this->json['tinymce_toolbar1'] = isset($this->input_attrs['toolbar1']) ? esc_attr($this->input_attrs['toolbar1']) : 'bold italic bullist numlist alignleft aligncenter alignright link';
        $this->json['tinymce_toolbar2'] = isset($this->input_attrs['toolbar2']) ? esc_attr($this->input_attrs['toolbar2']) : '';
        $this->json['tinymce_mediabuttons'] = isset($this->input_attrs['mediaButtons']) && ($this->input_attrs['mediaButtons'] === true) ? true : false;
    }

    function render_content()
    {
        $wrap = Tag::create([
            "name" => "div",
            "class" => "tinymce-control"
        ]);

        $wrap->append(Tag::create([
            "name" => "span",
            "class" => "customize-control-title",
            "content" => esc_html($this->label)
        ]));

        if (!empty($this->description)) {
            $wrap->append(Tag::create([
                "name" => "span",
                "class" => "customize-control-description",
                "content" => esc_html($this->description)
            ]));
        }

        $link = explode("=", $this->get_link());

        $link = array_pop($link);

        $link = trim($link, '"');

        $wrap->append(Tag::create([
            "name" => "textarea",
            "class" => ["customize-control-tinymce-editor"],
            "id" => esc_attr($this->id),
            "attr" => [
                "data-customize-setting-link" => $link
            ],
            "style" => [
                "width" => "100%",
                "padding" => "10px"
            ],
            "content" => esc_attr($this->value())
        ]));

        $wrap->e();
    }
}
