jQuery(document).ready(function($) {
    $('#city-search').on('keyup', function() {
        var searchQuery = $(this).val();

        $.ajax({
            url: ajax_params.ajax_url, // localized variable for AJAX URL
            type: 'POST',
            data: {
                action: 'search_cities', // The action for registered in PHP
                search: searchQuery
            },
            success: function(data) {
                // Clear the previous table and add the new search results
                $('#search-results').html(data);
                $('table').not('#search-results table').hide(); // Hide the original table
            },
            error: function(xhr, status, error) {
                console.log('Search failed:', status, error);
            }
        });
    });
});
