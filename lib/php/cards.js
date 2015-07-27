<script>
$(document).ready(function() {
  var active, content;
  $('ul.tabs').each(function() {
    var links = $(this).find('a');
    active = $(links.filter('[href="'+location.hash+'"]')[0] || links[0]);
    active.addClass('activesection');
    content = $(active.attr('href'));

    links.not(active).each(function() {
      $($(this).attr('href')).hide();
    });

    $(this).on('click', 'a', function(e) {
      active.removeClass('activesection');
      content.hide();
      var new_active = $(this).attr('href');
      var state = {location: new_active};
      history.pushState(state, '', new_active);
      active = $(this);
      content = $($(this).attr('href'));
      active.addClass('activesection');
      content.addClass('loading');
      content.show();
      content.removeClass('loading');
      e.preventDefault();
    });
  });

  $(window).on("popstate", function(e) {
    if (e.originalEvent.state !== null) {
      active.removeClass('activesection');
      content.hide();
      active = $('a[href="' + e.originalEvent.state.location + '"]');
      content = $(active.attr('href'));
      active.addClass('activesection');
      content.show();
    } else {
      active.removeClass('activesection');
      content.hide();
      $('ul.tabs').each(function() {
        var links = $(this).find('a');
        active = $(links.filter('[href="'+location.hash+'"]')[0] || links[0]);
        active.addClass('activesection');
        content = $(active.attr('href'));
        content.show();
      });
    }
  });
});
</script>