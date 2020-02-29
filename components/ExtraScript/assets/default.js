((r, d) => {
    "use strict"

    function testScript() {
        if (typeof d !== "undefined" && d.developmentMode === "1") {
            r.log("RESP %s initialized", d.version);
        }
    }
	
    r.ready(testScript);

})(RESP, RESP_DATA);