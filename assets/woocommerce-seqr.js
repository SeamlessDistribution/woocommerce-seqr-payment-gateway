if (!window.console) {
    window.console = {
        log: function (msg) {
        }
    };
}

(function () {

    var args = {};

    function initHttpRequest(url, successCallback, errorCallback) {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                successCallback(xmlhttp.responseText);
            }
            if (xmlhttp.readyState == 4 && xmlhttp.status >= 400 && errorCallback) {
                errorCallback(url, xmlhttp.status);
            }
        };
        return xmlhttp;
    }

    function get(url, successCallback, errorCallback) {
        var xmlhttp = initHttpRequest(url, successCallback, errorCallback);
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }

    function parseScriptArgs() {
        var scriptURL = document.getElementById("seqr_js").src;
        var hashes = scriptURL.slice(scriptURL.indexOf('#!') + 2).split('&');
        for (var i = 0; i < hashes.length; i++) {
            var tuple = hashes[i].split('=');
            if (tuple.length == 2) args[tuple[0]] = decodeURIComponent(tuple[1]);
        }
    }

    function pollInvoiceStatus() {
        var url = args['callbackUrl'];
        get(url, function (json) {
            var data = JSON.parse(json);
            var pollFrequency = Math.max(100, Math.min(data.poll_frequency, 5000));
            if (data.status == 'pending') {
                window.setTimeout(pollInvoiceStatus, pollFrequency);
            } else if (data.url) {
                document.location = data.url;
            }
        }, function (url, status) {
            if (status == 404) {
                console.log(status + ' loading ' + url + ', giving up.');
            } else {
                console.log(status + ' loading ' + url + ', retrying.');
                window.setTimeout(pollInvoiceStatus, 1000);
            }
        });
    }

    function initialize() {
        parseScriptArgs();
        window.setTimeout(pollInvoiceStatus, 1000);
    }

    initialize();

}).call();
