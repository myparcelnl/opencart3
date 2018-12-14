<?php
$button_class = (version_compare(VERSION, '2.0.0.0', '>=') ? 'btn btn-info' : 'button');
$button_tag = (version_compare(VERSION, '2.0.0.0', '>=') ? 'button' : 'a');
?>
<<?php echo $button_tag ?> type="submit" data-loader="<?php echo MyParcel()->getImageUrl() . 'myparcel-spin.gif' ?>" form="form-order" formaction="<?php echo $formAction; ?>" formtarget="_blank" data-toggle="tooltip" title="<?php echo MyParcel()->lang->get('print_batch'); ?>"
	class="<?php echo $button_class ?>"
	id="button-print-batch"
	data-version = <?php echo (version_compare(VERSION, '2.0.0.0', '>=') ? '2' : '1') ?>
	<?php echo (version_compare(VERSION, '2.0.0.0', '>=') ? '' : 'target="_blank"') ?>
>
	<!-- <i class="fa fa-print"></i> -->
	<?php echo MyParcel()->lang->get('print_batch'); ?>
</<?php echo $button_tag ?>>