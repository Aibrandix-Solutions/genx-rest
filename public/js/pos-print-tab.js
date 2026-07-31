(function () {
    const PLACEHOLDER_KEY = "_posPrintPlaceholder";
    const PLACEHOLDER_TIMER_KEY = "_posPrintPlaceholderTimer";

    function toSameOriginUrl(url) {
        if (!url) {
            return null;
        }

        try {
            const parsed = new URL(String(url), window.location.origin);
            return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
        } catch (e) {
            return null;
        }
    }

    function resolvePrintUrl(url) {
        if (!url) {
            return null;
        }

        if (typeof url === "string") {
            return toSameOriginUrl(url);
        }

        if (Array.isArray(url)) {
            const first = url[0];
            if (typeof first === "string") {
                return toSameOriginUrl(first);
            }
            if (first && typeof first === "object") {
                return toSameOriginUrl(first.url ?? first[0] ?? null);
            }
        }

        if (typeof url === "object" && url.url) {
            return toSameOriginUrl(url.url);
        }

        return null;
    }

    function clearPlaceholderTimer() {
        if (window[PLACEHOLDER_TIMER_KEY]) {
            clearTimeout(window[PLACEHOLDER_TIMER_KEY]);
            window[PLACEHOLDER_TIMER_KEY] = null;
        }
    }

    function navigateExistingPrintWindow(targetWindow, resolvedUrl) {
        // Prefer fetch+document.write into the pre-opened tab so we avoid a full
        // browser navigation round-trip feel on about:blank placeholders.
        return fetch(resolvedUrl, {
            credentials: "same-origin",
            headers: { Accept: "text/html" },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Print fetch failed: " + response.status);
                }
                return response.text();
            })
            .then(function (html) {
                if (!targetWindow || targetWindow.closed) {
                    return false;
                }
                targetWindow.document.open();
                targetWindow.document.write(html);
                targetWindow.document.close();
                try {
                    targetWindow.focus();
                } catch (e) {
                    // ignore
                }
                return true;
            })
            .catch(function () {
                if (!targetWindow || targetWindow.closed) {
                    return false;
                }
                try {
                    targetWindow.location.replace(resolvedUrl);
                } catch (e) {
                    targetWindow.location.href = resolvedUrl;
                }
                return true;
            });
    }

    window.closePosPrintPlaceholder = function closePosPrintPlaceholder() {
        clearPlaceholderTimer();

        const placeholder = window[PLACEHOLDER_KEY];
        if (placeholder && !placeholder.closed) {
            placeholder.close();
        }

        window[PLACEHOLDER_KEY] = null;
    };

    window.preparePosPrintPlaceholder = function preparePosPrintPlaceholder() {
        window.closePosPrintPlaceholder();

        const placeholder = window.open("about:blank", "_blank");
        window[PLACEHOLDER_KEY] = placeholder;

        if (placeholder && !placeholder.closed) {
            try {
                placeholder.document.write(
                    "<!DOCTYPE html><html><head><title>Preparing print…</title></head>" +
                        '<body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#444">' +
                        "<p>Preparing print…</p></body></html>"
                );
                placeholder.document.close();
            } catch (e) {
                // ignore
            }
        }

        window[PLACEHOLDER_TIMER_KEY] = setTimeout(() => {
            window.closePosPrintPlaceholder();
        }, 20000);

        return placeholder;
    };

    window.openPosPrintTab = function openPosPrintTab(url, existingWindow = null) {
        const resolvedUrl = resolvePrintUrl(url);
        if (!resolvedUrl) {
            return false;
        }

        clearPlaceholderTimer();

        let targetWindow = existingWindow;
        if (!targetWindow || targetWindow.closed) {
            targetWindow = window[PLACEHOLDER_KEY];
        }

        if (targetWindow && !targetWindow.closed) {
            window[PLACEHOLDER_KEY] = null;
            navigateExistingPrintWindow(targetWindow, resolvedUrl);
            return true;
        }

        const opened = window.open(resolvedUrl, "_blank");
        if (!opened) {
            const anchor = document.createElement("a");
            anchor.href = resolvedUrl;
            anchor.target = "_blank";
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        }

        window[PLACEHOLDER_KEY] = null;
        return true;
    };

    document.addEventListener("livewire:init", () => {
        if (!window.Livewire || typeof window.Livewire.on !== "function") {
            return;
        }

        window.Livewire.on("closePosPrintPlaceholder", () => {
            window.closePosPrintPlaceholder();
        });
    });
})();
