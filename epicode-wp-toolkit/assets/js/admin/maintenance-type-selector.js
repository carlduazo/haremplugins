(function($) {
  $(function() {
    var $maintenance_type = $('.js.maintenance-type'),
        $maintenance_type_rows = $('.js.maintenance-type-row');

    $maintenance_type.on('change', toggleMaintenanceTypeFields);

    function toggleMaintenanceTypeFields (event) {
      var $this = $(this),
          maintenance_type = $this.val(),
          $maintenance_type_row = $('.js.maintenance-type-row.' + maintenance_type);
          
          $maintenance_type_rows.addClass('hidden');
          $maintenance_type_row.removeClass('hidden');
    }
  });
}(jQuery));
