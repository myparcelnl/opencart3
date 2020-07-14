<?php
$button_class = (version_compare(VERSION, '2.0.0.0', '>=') ? 'btn btn-info' : 'button');
$button_tag = (version_compare(VERSION, '2.0.0.0', '>=') ? 'button' : 'a');
?>
<<?php echo $button_tag ?>  data-loader="<?php echo MyParcel()->getImageUrl() . 'myparcel-spin.gif' ?>" form="form-order" formaction="<?php echo $formAction; ?>"  data-toggle="tooltip" title="<?php echo MyParcel()->lang->get('print_batch'); ?>"
	class="<?php echo $button_class ?>"
	id="button-print-batch"
	data-version = <?php echo (version_compare(VERSION, '2.0.0.0', '>=') ? '2' : '1') ?>
	<?php echo (version_compare(VERSION, '2.0.0.0', '>=') ? '' : 'target="_blank"') ?>
>
	<!-- <i class="fa fa-print"></i> -->
	<?php echo MyParcel()->lang->get('print_batch'); ?>
</<?php echo $button_tag ?>>

<div id="position_label_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title text-center"><?php echo $position_label_title;?></h4>
            </div>
            <div class="modal-body">
                <div class="overlay  overlay-content-container">
                    <div class="overlay-content">
                        <div class="print-padding">
                            <div class="paper">
                                <div class="a6-label">
                                    <i class="fa fa-check hidden" data-position_number="2"></i>
                                    <i class="fa fa-times"></i>
                                </div>
                                <div class="a6-label">
                                    <i class="fa fa-check hidden" data-position_number="4"></i>
                                    <i class="fa fa-times"></i>
                                </div>
                                <div class="a6-label">
                                    <i class="fa fa-check hidden" data-position_number="1"></i>
                                    <i class="fa fa-times"></i>
                                </div>
                                <div class="a6-label">
                                    <i class="fa fa-check hidden" data-position_number="3"></i>
                                    <i class="fa fa-times"></i>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary btn-print-multi-label-order" form="form-order" formtarget="_blank" formaction="<?php echo $formAction; ?>"  data-toggle="tooltip" >Print</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

            </div>
        </div>

    </div>
</div>