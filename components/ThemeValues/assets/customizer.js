/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

wp.customize.selectiveRefresh.partialConstructor.resp_value = (function( api, $ ) {
    'use strict';

    return api.selectiveRefresh.Partial.extend( {

        refresh: function() {
            const partial = this;
            const valueSetting = api( partial.params.primarySetting );
            const value = valueSetting.get();

            _.each( partial.placements(), function( placement ) {
                const container = placement.container[0];
                const partialType = container.getAttribute("data-partial-type") || "text";

                if(partialType == "richtext"){
                    const $container = $(container);
                    const shortcut = $("> .customize-partial-edit-shortcut" , $container).clone(true);
                    $container.html(value).prepend(shortcut);
                }else{
                    const textNode = [...container.childNodes].find(child => child.nodeType === Node.TEXT_NODE);
                    if( textNode ){
                        textNode.textContent = value;
                    }
                }

            } );

            return $.Deferred().resolve().promise();
        }
    } );
})( wp.customize, jQuery );