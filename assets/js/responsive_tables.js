(function () {
    function normalizeLabel(text) {
        return String(text || "").replace(/\s+/g, " ").trim();
    }

    function prepareTable(table) {
        if (!table || table.dataset.responsiveReady === "1") {
            return;
        }

        table.dataset.responsiveReady = "1";
        applyLabels(table);
    }

    function applyLabels(table) {
        const headers = Array.from(table.querySelectorAll("thead th"))
            .map(header => normalizeLabel(header.textContent));

        if (headers.length === 0) {
            return;
        }

        table.querySelectorAll("tbody tr").forEach(row => {
            const cells = Array.from(row.children).filter(cell => {
                return cell.tagName && cell.tagName.toLowerCase() === "td";
            });

            cells.forEach((cell, index) => {
                if (!cell.hasAttribute("data-label")) {
                    cell.setAttribute("data-label", headers[index] || "");
                }
            });
        });
    }

    function prepareAllTables() {
        document.querySelectorAll(".admin-table").forEach(prepareTable);
    }

    window.prepareResponsiveTables = prepareAllTables;

    document.addEventListener("DOMContentLoaded", () => {
        prepareAllTables();

        const observer = new MutationObserver(() => {
            document.querySelectorAll(".admin-table").forEach(table => {
                applyLabels(table);
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();
