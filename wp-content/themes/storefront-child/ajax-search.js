jQuery(document).ready(function($) {
    $('#city-search').on('input', function() {
        var search_term = $(this).val();

        $.ajax({
            url: ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'cities_search',
                search_term: search_term
            },
            success: function(response) {
                $('#search-results').html(response);
            }
        });
    });
});
