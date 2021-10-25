jQuery(document).ready(function ($) {
    function updateParam(url, paramToUpdate, newValue) {
        var parts = url.split('?'),
            query,
            param,
            paramFound = false,
            newQuery = [],
            newUrl = parts[0];

        if (parts.length === 1) {
            parts[1] = '';
        }

        query = parts[1].split('&');

        // Update the param in the query, building a new query
        if (query.length > 0) {

            for (var i = 0; i < query.length; i++) {
                param = query[i].split('=');

                if (param[0] === paramToUpdate) {
                    param[1] = newValue;
                    paramFound = true;
                }

                newQuery.push(param);
            }

            if (!paramFound) {
                newQuery.push([paramToUpdate, newValue]);
            }
        }

        // Convert the new query into a string
        if (newQuery.length > 0) {
            newUrl += '?';

            for (var i = 0; i < newQuery.length; i++) {
                param = newQuery[i];

                if (i > 0) {
                    newUrl += '&';
                }

                newUrl += param[0] + '=' + param[1];
            }
        }

        return newUrl;
    }

    $('#publishpress-calendar-ics-subs #publishpress-start-date').on('change', function () {
        var buttonDownload = document.getElementById('publishpress-ics-download'),
            buttonCopy = document.getElementById('publishpress-ics-copy');

        // Get the URL
        var url = buttonDownload.href;

        url = updateParam(url, 'start', $(this).val());

        buttonDownload.href = url;
        buttonCopy.dataset.clipboardText = url;
    });

    $('#publishpress-calendar-ics-subs #publishpress-end-date').on('change', function () {
        var buttonDownload = document.getElementById('publishpress-ics-download'),
            buttonCopy = document.getElementById('publishpress-ics-copy');

        // Get the URL
        var url = buttonDownload.href;

        url = updateParam(url, 'end', $(this).val());

        buttonDownload.href = url;
        buttonCopy.dataset.clipboardText = url;
    });

    new Clipboard('#publishpress-ics-copy');
});
