/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

var RESPAdmin = function () {
    this.editors = [];
    this.com = {};
}

if (typeof wp !== "undefined" && typeof wp.i18n !== "undefined") {
    var { __, _x, _n, sprintf } = wp.i18n;
}

; (function (r, d, $) {

    r.ready(init);

    function init() {

        initAdminClass();

        initCom();

        initSubmenuTabs();

        initDashboard();

    }

    function initAdminClass(){
        window.respAdmin = new RESPAdmin();

        window.respAdmin.editors = [{
            selector: "#resp_theme_data",
            options: d.admin.editor.json
        },
        {
            selector: "#resp_script",
            options: d.admin.editor.javascript
        }];
    }

    function initCom(){
        window.respAdmin.com.ajax = function (wrap, action, callback = null, fail = null, params = {}, method = "GET") {

            const nonce = wrap.attr("data-nonce");

            wrap.addClass("busy");

            $.ajax(d.adminAjaxUrl, {
                type: method,
                data: {
                    action: action,
                    params: params,
                    _wpnonce: nonce
                },
                success: function (result) {
                    if (result.startsWith("Error::")) {
                        console.error(result.replace("Error::", ""));
                    } else if (typeof callback === "function") {
                        callback(wrap, result);
                    }
                }
            }).done(function () {
                wrap.removeClass("busy");
            }).fail(function () {
                console.error("Unable to communicate with server.");
                if (typeof fail === "function") {
                    fail(wrap, result);
                }
            });

        }
    }

    function initSubmenuTabs() {

        const tabStyle = {
            "visibility" : "hidden",
            "position" : "absolute",
            "pointer-events" : "none"
        }

        const activeTabStyle = {
            "visibility" : "visible",
            "position" : "inherit",
            "pointer-events" : "all"
        }

        $('.submenu-nav li').click(function () {
            const activeTab = $(this).find('a').attr('href');

            $('.submenu-nav li').removeClass('active');
            $(this).addClass('active');

            $('.tab-content').css(tabStyle);
            $(activeTab).css(activeTabStyle);

            return false;
        });

        $('.submenu-nav li:first-child a').trigger("click");
    }

    function initDashboard(){
        switch (d.admin.tab) {

            case "dashboard":

                setTimeout(function () {
                    window.respAdmin.com.ajax($("#version_holder"), "resp_fetch_version_data", function (wrap, result) {
                        if (result !== d.version) {
                            var href = d.admin.mainServer;
                            wrap.append($(
                                `<a target="_blank" href="${href}" class="new">` +
                                __("A New Version Is Available", "resp") +
                                "</a>"
                            ));
                        }
                    });
                }, 2000);

                setTimeout(function () {
                    window.respAdmin.com.ajax($("#server_info_wrap"), "resp_fetch_dashboard_data", function (wrap, result) {
                        wrap.html(result);
                    })
                }, 5000);

                break;

            case "edit":

                $("#config_backup_btn").click(function (event) {

                    event.preventDefault();

                    window.respAdmin.com.ajax($("#config_backup_wrap"), "resp_request_backup", function (wrap, result) {

                        const r = JSON.parse(result);

                        var link = document.createElement('a');
                        var blob = new Blob([JSON.stringify(r.data)], { type: 'text/plain' });

                        link.download = r.title + ".json";
                        link.href = window.URL.createObjectURL(blob);

                        link.click();

                    });

                });

                window.respAdmin.editors.forEach(elem => {

                    r.query(elem.selector, (e) => {
                        if (!r.hasClass(e, 'hidden')) {
                            wp.codeEditor.initialize($(e), elem.options);
                        }
                    });

                });

                break;
        }
    }

})(RESP, RESP_DATA, jQuery);