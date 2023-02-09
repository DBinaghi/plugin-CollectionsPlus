if (!Omeka) {
    var Omeka = {};
}

Omeka.CollectionsBrowse = {};

(function ($) {
    Omeka.CollectionsBrowse.setupDetails = function (detailsText, showDetailsText, hideDetailsText) {
        $('.details').hide();
        $('.action-links').prepend('<li><a href="#" class="details-link">' + detailsText + '</a></li>');

        $('tr.collection').each(function() {
            var collectionDetails = $(this).find('.details');
			if ($.trim(collectionDetails.html()) != '') {
                $(this).find('.details-link').click(function(e) {
                    e.preventDefault();
                    collectionDetails.slideToggle('fast');
                });
            }
        });

        var toggleList = '<a href="#" class="toggle-all-details full-width-mobile blue button">' + showDetailsText + '</a>';

        $('.quick-filter').before(toggleList);

        // Toggle item details.
        var detailsShown = false;
        $('.toggle-all-details').click(function (e) {
            e.preventDefault();
            if (detailsShown) {
            	$('.toggle-all-details').text(showDetailsText);
            	$('.details').slideUp('fast');
            } else {
            	$('.toggle-all-details').text(hideDetailsText);
            	$('.details').slideDown('fast');
            }
            detailsShown = !detailsShown;
        });
    };
})(jQuery);
