(function($) {
  $(function() {
    var $allow = $('.js.error-reporting-allow'),
        $error_trackers = $('.js.error-reporting-php_tracker, .js.error-reporting-js_tracker');

    $allow
      .on('change', toggleErrorTrackers)
      .trigger('change');

    function toggleErrorTrackers() {
      var is_checked = $(this).is(':checked');
      if(!is_checked) {
        $error_trackers.prop('checked', false);
      }

      $error_trackers.prop('disabled', !is_checked);
    }
  });
}(jQuery));
