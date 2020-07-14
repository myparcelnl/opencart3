<?php
	$button_class = (version_compare(VERSION, '2.0.0.0', '>=') ? 'btn btn-info' : 'button');
	$button_tag = (version_compare(VERSION, '2.0.0.0', '>=') ? 'button' : 'a');
?>
<<?php echo $button_tag ?> data-loader="<?php echo MyParcel()->getImageUrl() . 'myparcel-spin.gif' ?>" id="button-export-print-batch" data-action="export-print" form="form-order" data-url="<?php echo $formAction; ?>" formtarget="_blank" data-toggle="tooltip" title="<?php echo MyParcel()->lang->get('export_print_batch'); ?>"
		class="<?php echo $button_class ?>"
		data-error-text="<?php echo MyParcel()->lang->get('error_export_print_batch_error') ?>"
>

	<!-- <i class="fa fa-print"></i> -->
	<?php echo MyParcel()->lang->get('export_print_batch'); ?>
</<?php echo $button_tag ?>>