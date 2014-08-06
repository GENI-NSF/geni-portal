<!-- Source: http://www.jacklmoore.com/notes/jquery-tabs/ -->

		<script>
			// Wait until the DOM has loaded before querying the document
			$(document).ready(function(){
                            var $active, $content;
				$('ul.tabs').each(function(){
					// For each set of tabs, we want to keep track of
					// which tab is active and it's associated content
					var $links = $(this).find('a');

					// If the location.hash matches one of the links, use that as the active tab.
					// If no match is found, DON'T use the first link as the initial active tab.
					// (We want this for the sliceresouce.php page)
					$active = $($links.filter('[href="'+location.hash+'"]')[0]);
					$active.addClass('active');
					$content = $($active.attr('href'));

					// Hide the remaining content
					$links.not($active).each(function () {
						$($(this).attr('href')).hide();
					});

					// Bind the click event handler
					$(this).on('click', 'a', function(e){
						// Make the old tab inactive.
						$active.removeClass('active');
						$content.hide();

                                            // Record the location in the history
                                            var new_active = $(this).attr('href');
                                            var state = {location: new_active};
                                            history.pushState(state, '', new_active);

						// Update the variables with the new link and content
						$active = $(this);
						$content = $($(this).attr('href'));

						// Make the tab active.
						$active.addClass('active');
						$content.show();

						// Prevent the anchor's default click action
						e.preventDefault();
					});
				});
                            $(window).on("popstate", function(e) {
                                if (e.originalEvent.state !== null) {
                                    $active.removeClass('active');
				    $content.hide();
				    $active = $('a[href="' + e.originalEvent.state.location + '"]');
				    $content = $($active.attr('href'));
				    // Make the tab active.
				    $active.addClass('active');
				    $content.show();
                                } else {
                                    $active.removeClass('active');
				    $content.hide();
				    $('ul.tabs').each(function(){
					// For each set of tabs, find and display the default tab
					var $links = $(this).find('a');

					// If the location.hash matches one of the links, use that as the active tab.
					// If no match is found, use the first link as the initial active tab.
					$active = $($links.filter('[href="'+location.hash+'"]')[0] || $links[0]);
					$active.addClass('active');
					$content = $($active.attr('href'));
				        $content.show();
				    });
                                }
                            });
			});


		</script>
