/**
 * Licensed under Apache 2.0 (https://github.com/WP-RESP/resp/blob/master/LICENSE)
 * Copyright (C) 2019 WP-RESP (https://wp-resp.com)
 */

; ((api, $ , d) => {

    function getColorMap(name, value) {

        const colors = Object.keys(d.colors);

        if(!d.colors[name]["styles"]){
            return "";
        }
        

        return d.colors[name]["styles"].map((element) => {
            var selector = element['selector'];

            if(!Array.isArray(selector)){
                selector = [selector];
            }

            selector = selector.map((item)=>{
                return item.replace("$", name);
            }).join(",");

            const definition = element['params'];
            const rules = Object.keys(definition);
            const result = rules.map((rule) => {
                var temp = definition[rule];
            
                colors.forEach(colorName => {
                    const colorValue = d.colors[colorName]['value'] || "black";
                    temp = temp.replace(`$${colorName}` , colorValue );
                });

                return rule + ":" + temp.replace("$", value);
            }).join(';');
            return selector.replace("$", name) + "{" + result + "}";
        }).join('\n');

    }

    for (const key in d.colors) {
        if (d.colors.hasOwnProperty(key)) {
            const id = `resp-color-${key}`;
            wp.customize(id, function (setting) {
                setting.bind(function (value) {
                    $(`#${id}`).remove();
                    $(`<style id="${id}"></style>`)
                        .text(getColorMap(key, value))
                        .appendTo("head");
                });
            });
        }
    }

})(wp.customize, jQuery, RESP_DATA);
