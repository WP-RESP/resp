/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 Arman Afzal <rmanaf.com>
 */
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var RESP_DATA = RESP_DATA || {};
var RespElement = /** @class */ (function (_super) {
    __extends(RespElement, _super);
    function RespElement() {
        var _this = _super.call(this) || this;
        _this.autoRendering = (_this.getAttribute("auto-rendering") || "true") === "true";
        _this.postData = RespAPI.thePost;
        return _this;
    }
    Object.defineProperty(RespElement.prototype, "trace", {
        get: function () {
            return (this.getAttribute("trace") || "false") === "true";
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(RespElement.prototype, "encapsulate", {
        get: function () {
            return (this.getAttribute("encapsulate") || "false") === "true";
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(RespElement.prototype, "keepContainer", {
        get: function () {
            return (this.getAttribute("keep-container") || "false") === "true";
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(RespElement.prototype, "postData", {
        get: function () {
            return this._postData;
        },
        set: function (data) {
            this._postData = data;
            this.dispatchCustomEvent("onChange");
        },
        enumerable: false,
        configurable: true
    });
    RespElement.prototype.dispatchCustomEvent = function (event, meta) {
        if (meta === void 0) { meta = null; }
        var e = new CustomEvent(event, {
            detail: {
                "data": meta
            }
        });
        this.dispatchEvent(e);
    };
    RespElement.prototype.connectedCallback = function () {
        this.dispatchCustomEvent("onConnected");
        if (this.autoRendering) {
            this.render();
            this.dispatchCustomEvent("onRendered");
        }
    };
    return RespElement;
}(HTMLElement));
var RespPosts = /** @class */ (function (_super) {
    __extends(RespPosts, _super);
    function RespPosts() {
        var _this = _super.call(this) || this;
        _this.template = "";
        _this.embed = false;
        return _this;
    }
    Object.defineProperty(RespPosts, "params", {
        get: function () {
            return [
                { "id": null },
                { "context": "context" },
                { "password": "password" },
                { "per-page": "per_page" },
                { "page": "page" },
                { "offset": "offset" },
                { "embed": "_embed" }
            ];
        },
        enumerable: false,
        configurable: true
    });
    RespPosts.prototype.getQueryString = function () {
        var _this = this;
        var url = new URL(RESP_DATA.home + '/wp-json/wp/v2/posts');
        RespPosts.params.forEach(function (e) {
            var key = Object.keys(e)[0];
            var value = _this.getAttribute(key) || "";
            if (value === "$") {
                var currentUrl = new URL(location.href);
                value = currentUrl.searchParams.get(key) || "";
            }
            if (value !== "") {
                if (!!e[key]) {
                    url.searchParams.append(e[key], value);
                }
                else {
                    url.href = url.href + "/" + value;
                }
            }
            if (key == "embed" && value == "true") {
                _this.embed = true;
            }
        });
        return decodeURI(url.toString());
    };
    RespPosts.prototype.getHeaders = function () {
        var cache = (this.getAttribute("cache") || "false");
        var headers = new Headers();
        if (cache === "no-cache") {
            headers.append('pragma', 'no-cache');
        }
        if (cache !== "false") {
            headers.append('cache-control', cache);
        }
        return headers;
    };
    RespPosts.prototype.render = function () {
        var _this = this;
        this.template = this.innerHTML;
        this.innerHTML = "";
        var request = new Request(this.getQueryString());
        var headers = this.getHeaders();
        fetch(request, { headers: headers, credentials: 'include' })
            .then(function (response) {
            if (response.ok) {
                return response.json();
            }
            else if (_this.trace) {
                throw response;
            }
            else {
                throw Error("Request rejected with status " + response.status);
            }
        })
            .then(function (data) {
            if (!Array.isArray(data)) {
                data = [data];
            }
            var shadow;
            if (_this.encapsulate) {
                shadow = _this.attachShadow({ mode: "open" });
            }
            data.map(function (x) {
                var author = null, thumbnail = null;
                if (_this.embed) {
                    author = x._embedded["author"];
                    thumbnail = x._embedded["wp:featuredmedia"][0]["media_details"]["sizes"];
                }
                return {
                    id: x.id,
                    title: x.title['rendered'],
                    excerpt: x.excerpt['rendered'],
                    content: x.content['rendered'],
                    guid: x.guid['rendered'],
                    link: x.link,
                    slug: x.slug,
                    author: !author ? null : author.map(function (a) {
                        return {
                            id: a.id,
                            name: a.slug,
                            url: a.link,
                            avatar: a.avatar_urls
                        };
                    }),
                    thumbnail: thumbnail
                };
            })
                .forEach(function (e) {
                RespAPI.thePost = e;
                if (_this.encapsulate) {
                    shadow.innerHTML += _this.template;
                }
                else {
                    _this.innerHTML += _this.template;
                }
            });
            _this.dispatchCustomEvent("onLoad", data);
        })
            .catch(function (err) {
            if (_this.trace) {
                console.error(err);
                err.text().then(function (m) {
                    _this.dispatchCustomEvent("onError", JSON.parse(m));
                });
            }
            else {
                _this.dispatchCustomEvent("onError", err);
            }
        });
    };
    return RespPosts;
}(RespElement));
var RespMeta = /** @class */ (function (_super) {
    __extends(RespMeta, _super);
    function RespMeta() {
        return _super.call(this) || this;
    }
    RespMeta.prototype.render = function () {
        var _this = this;
        if (typeof this.postData === "undefined") {
            return;
        }
        if (!this.postData.hasOwnProperty("id")) {
            return;
        }
        if (this.postData) {
            RespAPI.getAttribute(this, 'name', function (v) {
                _this.innerHTML = v;
                if (!_this.keepContainer) {
                    _this.outerHTML = _this.innerHTML;
                }
            });
        }
    };
    return RespMeta;
}(RespElement));
var RespTag = /** @class */ (function (_super) {
    __extends(RespTag, _super);
    function RespTag() {
        return _super.call(this) || this;
    }
    Object.defineProperty(RespTag.prototype, "reservedParams", {
        get: function () {
            return [
                "name"
            ];
        },
        enumerable: false,
        configurable: true
    });
    RespTag.prototype.render = function () {
        var _this = this;
        var name = this.getAttribute('name') || "div";
        var dom = document.createElement(name);
        var content = this.innerHTML;
        this.innerHTML = "";
        this.getAttributeNames()
            .filter(function (x) {
            return _this.reservedParams.indexOf(x) === -1;
        })
            .forEach(function (e) {
            RespAPI.getAttribute(_this, e, function (v) {
                dom.setAttribute(e, v);
            });
        });
        dom.innerHTML = content;
        this.replaceWith(dom);
    };
    return RespTag;
}(RespElement));
var RespAPI = /** @class */ (function () {
    function RespAPI() {
    }
    Object.defineProperty(RespAPI, "wpPostParams", {
        get: function () {
            return ["id", "excerpt", "author",
                "name", "type", "title",
                "date", "link", "guid",
                "thumbnail"];
        },
        enumerable: false,
        configurable: true
    });
    RespAPI.getAttribute = function (element, attr, callback) {
        var _this = this;
        var value = element.getAttribute(attr) || "";
        var params = value.split(/(?<=\$)(.*?)(?=\$)/g);
        params.forEach(function (p) {
            var holder = __assign({}, _this.thePost);
            var path = p.split(":");
            if (RespAPI.wpPostParams.indexOf(path[0]) === -1) {
                return;
            }
            path.forEach(function (e) {
                if (holder.hasOwnProperty(e)) {
                    holder = holder[e];
                }
            });
            value = value.replace("$" + p + "$", holder);
        });
        callback(value);
    };
    RespAPI.init = function () {
        customElements.define("resp-meta", RespMeta);
        customElements.define("resp-tag", RespTag);
        customElements.define("resp-posts", RespPosts);
    };
    RespAPI.thePost = {};
    return RespAPI;
}());
RespAPI.init();
