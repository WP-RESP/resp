/**
 * Apache License, Version 2.0
 * Copyright (C) 2019 Arman Afzal <arman.afzal@divanhub.com>
 * 
 * @since 0.9.0
 */
((r, d, $) => {

    r.ready(initEditors);

    function initEditors() {
        if(d.settingsTab != "edit"){
            return;
        }
        if (d.codeEditor) {
            r.query('#resp_theme_data', (e) => {
                wp.codeEditor.initialize($(e), d.codeEditor.jsonEditor);
            });
            r.query('#resp_script', (e) => {
                wp.codeEditor.initialize($(e), d.codeEditor.scriptEditor);
            });
        }
    }

})(RESP, RESP_DATA, jQuery)