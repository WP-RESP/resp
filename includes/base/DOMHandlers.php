<?php

/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

namespace Resp;

use DOMDocument, DOMElement, DOMNode;
use DOMXPath;

defined('RESP_VERSION') or die;

class DOMHandlers
{

    /**
     * @since 0.9.2
     */
    static function parseContent(&$atts = [], &$content = null)
    {
        // TODO: render RESP tags
    }


    /**
     * @since 0.9.2
     */
    static function removeChildren($list){
        foreach ($list as $node) {
            $node->parentNode->removeChild($node);
        }
    }


    /**
     * @since 0.9.2
     */
    static function insertHTMLBeforeNode($target , DOMNode $node , string $html){

        $newDoc = self::createDocument($html);

        $newDoc->formatOutput = true;

        $newDocBody = $newDoc->getElementsByTagName("body")->item(0);

        foreach ($newDocBody->childNodes as $child) {

            $import = $target->ownerDocument->importNode($child, true);

            $target->insertBefore($import, $node);
            
        }

    }


    /**
     * @since 0.9.2
     */
    static function getNodeAttributes(DOMNode $node){
        
        $attributes = [];

        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
        }

        return $attributes;

    }


    /**
     * @since 0.9.2
     */
    static function innerHTML(DOMNode $element)
    {
        $innerHTML = "";
        $children  = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }


    /**
     * @since 0.9.2
     */
    static function createDocument(string $html)
    {

        $dom = new \DOMDocument();

        $dom->formatOutput = true;

        $dom->preserveWhiteSpace = false;

        if(empty($html)){
            return $dom;
        }

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $html = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $html);


        libxml_use_internal_errors(true);

        $dom->loadHTML($html, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        return $dom;
    }



    /**
     * @since 0.9.2
     */
    static function getJsonAttributes(&$attr, &$content)
    {

        $holder = "resp-attributes";

        $content = apply_filters("resp-core--config-output" , $content);

        $doc = self::createDocument($content);

        $removeList = [];

        // get attribute nodes
        $attributeNodes = $doc->getElementsByTagName($holder);
        

        $xpath = new DOMXPath($doc);


        // remove comments
        foreach ($xpath->query('//comment()') as $comment) {
            if ($comment->parentNode->nodeName != $holder) {
                continue;
            }
            $comment->parentNode->removeChild($comment);
        }


        foreach ($attributeNodes as $node) {

            if ($node->parentNode->nodeName != "body") {
                continue;
            }

            $removeList[] = $node;

            $nodeValue = self::innerHTML($node);

            $json = do_shortcode($nodeValue, true);

            $json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json);

            $data = json_decode($json, true);

            if (!is_array($data)) {

                Tag::p(json_last_error_msg())->e();

                Tag::code($json)->e();

                continue;
            }

            $attr = array_merge($attr, $data);
        }

        foreach ($removeList as $node) {
            $node->parentNode->removeChild($node);
        }

        $content = $doc->saveHTML();

        
    }
}
