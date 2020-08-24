/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */

; (function(r, d , $){

    var RESPAdminAPI = function(){
        var apis = [];
    };

    RESPAdminAPI.prototype.editors = [
        { 
            selector : "#resp_theme_data" ,
            options : d.admin.editor.json
        },
        {
            selector : "#resp_script" ,
            options : d.admin.editor.javascript
        }
    ];

    RESPAdminAPI.prototype.api = function(n , c){
        this.apis.push({
            name: n,
            callback: c
        });
    }

    r.ready(init);

    function init(){
       
        window.respAdminApi = new RESPAdminAPI();
        
        window.respAdminApi.editors.forEach(elem => {
            
            r.query(elem.selector , (e) => {
                if(!r.hasClass(e , 'hidden')){
                    wp.codeEditor.initialize($(e), elem.options);
                }
            });

        });

    }

})(RESP, RESP_DATA ,jQuery);