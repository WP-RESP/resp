/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */
var __spreadArrays = (this && this.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};
var RESP_DATA = RESP_DATA || {};
var LogType;
(function (LogType) {
    LogType[LogType["Info"] = 0] = "Info";
    LogType[LogType["Error"] = 1] = "Error";
})(LogType || (LogType = {}));
var RESP = /** @class */ (function () {
    function RESP() {
    }
    /**
     * @since 0.9.0
     */
    RESP.init = function () {
        if (this.noJquery() && this.debug()) {
            this.log("Default jQuery library is not loaded", LogType.Info);
        }
    };
    /**
     * @since 0.9.0
     */
    RESP.ready = function () {
        var callback = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            callback[_i] = arguments[_i];
        }
        if (document.readyState != 'loading') {
            callback.forEach(function (c) {
                c();
            });
        }
        else {
            callback.forEach(function (c) {
                document.addEventListener('DOMContentLoaded', c);
            });
        }
    };
    /**
     * @since 0.9.0
     */
    RESP.noJquery = function () {
        return RESP_DATA.noJquery === '1';
    };
    /**
     * @since 0.9.0
     */
    RESP.debug = function () {
        return RESP_DATA.developmentMode === '1';
    };
    /**
     * @since 0.9.0
     */
    RESP.log = function (m, t) {
        var p = [];
        for (var _i = 2; _i < arguments.length; _i++) {
            p[_i - 2] = arguments[_i];
        }
        if (!this.debug()) {
            return;
        }
        if (t === LogType.Info) {
            console.log.apply(console, __spreadArrays([m], p));
        }
        if (t === LogType.Error) {
            console.error.apply(console, __spreadArrays([m], p));
        }
    };
    /**
     * @since 0.9.0
     */
    RESP.registerExtension = function (n, o) {
        this.extensions.push({
            name: n,
            instance: o
        });
        this.log("Extension '%s' is registered.", LogType.Info, n);
    };
    /**
     * @since 0.9.0
     */
    RESP.query = function (s, c) {
        if (c === void 0) { c = null; }
        var elem = Array.prototype.slice.call(document.querySelectorAll(s));
        if (c !== null) {
            elem.forEach(function (e, i) { return c(e, i); });
        }
        return elem;
    };
    /**
     * Remove a single class, multiple classes, or all classes from each element in the set of matched elements.
     * @since 0.9.0
     */
    RESP.removeClass = function (s, c) {
        if (c === void 0) { c = null; }
        var r = RESP, elem;
        if (typeof (s) === "string") {
            elem = r.query(s);
        }
        else {
            elem = [s];
        }
        if (c == null) {
            elem.forEach(function (i) {
                while (i.classList.length > 0) {
                    i.classList.remove(i.classList.item(0));
                }
            });
        }
        else {
            var cs_1 = typeof (c) === "string" ? [c] : c;
            elem.forEach(function (i) {
                cs_1.forEach(function (k) {
                    i.classList.remove(k);
                });
            });
        }
    };
    /**
     * Determine whether any of the matched elements are assigned the given class
     * @since 0.9.1
     */
    RESP.hasClass = function (e, c) {
        var r = RESP, result = true;
        if (typeof c === "string") {
            c = [c];
        }
        if (typeof e === "string") {
            e = r.query(e)[0];
        }
        return c.every(function (r) { return e.classList.contains(r); });
    };
    /**
     * Adds the specified class(es) to each element in the set of matched elements
     * @since 0.9.0
     */
    RESP.addClass = function (s, c, f) {
        if (f === void 0) { f = false; }
        var r = RESP, elem;
        if (typeof (s) === "string") {
            elem = r.query(s);
        }
        else if (s instanceof Element) {
            elem = [s];
        }
        else {
            elem = s;
        }
        elem.forEach(function (i) {
            if (f) {
                while (i.classList.length > 0) {
                    i.classList.remove(i.classList.item(0));
                }
            }
            var cs = typeof (c) === "string" ? [c] : c;
            cs.forEach(function (k) {
                if (!(k in i.classList)) {
                    i.classList.add(k);
                }
            });
        });
        return elem;
    };
    RESP.extensions = [];
    return RESP;
}());
RESP.init();
