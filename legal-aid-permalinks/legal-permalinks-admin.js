jQuery(document).ready(function ($) {
    // Enable table sorting
    $("#legal-table").tablesorter();

    function applyFilters() {
        var activeFilters = {};

        // Gather taxonomy filters
        $(".legal-tax-filter").each(function () {
            var tax = $(this).data("tax");
            var val = $(this).val();
            if (val) activeFilters[tax] = val;
        });

        // Content type filter
        var contentType = $("#legal-content-filter").val();

        // Search text
        var searchVal = $("#legal-search").val().toLowerCase();

        $("#legal-table tbody tr").each(function () {
            var rowTax = $(this).data("tax") ? $(this).data("tax").split(" ") : [];
            var rowType = $(this).data("content-type");
            var rowText = $(this).text().toLowerCase();
            var show = true;

            // Taxonomy filter check
            $.each(activeFilters, function (tax, val) {
                if ($.inArray(val, rowTax) === -1) {
                    show = false;
                }
            });

            // Content type check
            if (contentType && rowType !== contentType) {
                show = false;
            }

            // Search filter
            if (searchVal && rowText.indexOf(searchVal) === -1) {
                show = false;
            }

            $(this).toggle(show);
        });
    }

    // Bind filters
    $("#legal-search").on("keyup", applyFilters);
    $(".legal-tax-filter").on("change", applyFilters);
    $("#legal-content-filter").on("change", applyFilters);

    // Save row AJAX
    $(".legal-save-row").on("click", function () {
        var row = $(this).closest("tr");
        var post_id = row.data("post-id");
        var custom = row.find(".legal-custom-path").val();
        var redirects = row.find(".legal-redirect-paths").val();

        $.post(LegalPermalinksAjax.ajax_url, {
            action: "legal_save_row",
            nonce: LegalPermalinksAjax.nonce,
            post_id: post_id,
            custom: custom,
            redirects: redirects
        }, function (response) {
            if (response.success) {
                alert("Saved!");
            } else {
                alert("Error saving row");
            }
        });
    });
});
