/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

wp.customize.selectiveRefresh.partialConstructor.resp_value = (function( api, $ ) {
    'use strict';

    return api.selectiveRefresh.Partial.extend( {

        refresh: function() {
            var partial = this, valueSetting;

            valueSetting = api( partial.params.primarySetting );
            _.each( partial.placements(), function( placement ) {
                var context = placement.container.context;
                var shortcut = $(context.childNodes[0]).clone(true);
                $(context).html(valueSetting.get()).prepend(shortcut);
            } );

            return $.Deferred().resolve().promise();
        }
    } );
})( wp.customize, jQuery );