<style media="screen">
	label span{
		display: none;
	}
	.form-table.checkboxes{
		display: inline-block;
		max-height: 500px;
		height: 100%;
		max-width: 100%;
		overflow-y: scroll;
		border: 1px solid #e1e1e1;
	}
	.form-table.checkboxes td{
		padding: 8px 10px;
	}
	.form-table.checkboxes tbody{
		padding: 16px;
    display: block;
	}
	label.label{
		font-weight: 600;
	}
	.checkbox-right{
		float: right;
	}
	th .checkbox-right input{
		vertical-align: middle;
	}
</style>
<form action="<?= admin_url( 'admin-post.php' ); ?>" method="post">
  <input type="hidden" name="action" value="epic_handle_postdata">
	<div class="wrap">
		<?php if (!empty($epic_save_MSG)){ ?>
      <div class="notice notice-success">
        <p><?= $epic_save_MSG ?></p>
      </div>
    <?php } ?>
		<h2><?= __( 'Epicode | Toolkit' ) ?></h2>
		<div class="nav-tab-wrapper" style="padding-left:20px">
			<?php foreach ( $tabs as $tab_key => $tab_label ) { ?>
				<a href="?page=epicode_options&tab=<?= $tab_key; ?>" class="nav-tab <?= ($current_tab == $tab_key ? 'nav-tab-active' : ''); ?>" ><?= $tab_label; ?></a>
			<?php } ?>
		</div>
		<input type="hidden" name="page" value="<?= $current_tab; ?>">
		<?= epictk_view( $tab_content_view, $tab_content_data ); ?>
	</div>
</form>
<script type="text/javascript">
	(function($) {
		$(function() {
			// Checks all checkboxes on click "Check all"
			// Already checks the "Check all" input if all checkbox are checked on doc.rdy
			// ----------------------------------------------------------
			var chxset = $('table.checkboxes input[type=checkbox]');
			var length = chxset.length;

			chxset.each(function(index, element){
				if(!$(element).is(":checked")){
					return false;
				}
				if(index === (length - 1)) {
					$('#checkall').prop('checked', true);
				}
			});

			$('#checkall').on('change', function() {
				if($(this).is(":checked")){
					chxset.each(function(){
						$(this).prop('checked', true);
					});
				} else {
					chxset.each(function(){
						$(this).prop('checked', false);
					});
				}
			});
		});
	}(jQuery));
</script>
