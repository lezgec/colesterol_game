(function () {
    window.APP_BASE_PATH = (window.APP_BASE_PATH || "/colesterol_game").replace(/\/$/, "");

    window.appUrl = function appUrl(path) {
        const cleanPath = String(path || "").replace(/^\//, "");
        return `${window.APP_BASE_PATH}/${cleanPath}`;
    };

    window.csrfHeaders = function csrfHeaders(extraHeaders) {
        const headers = Object.assign({}, extraHeaders || {});

        if (window.CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.CSRF_TOKEN;
        }

        return headers;
    };
})();
