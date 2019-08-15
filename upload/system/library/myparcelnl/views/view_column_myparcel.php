<?php
foreach ($listing_actions as $action => $data) {
    if($action == 'get_labels'){
        printf( '
        <a  href="%1$s"
            id="btn-myparcel-%2$s-%4$s"
            class="btn btn-secondary %2$s ' . (isset($data["class_name"]) ? $data["class_name"] : '') . '"
            alt="%3$s"
            data-tip="%3$s"
            data-order-id="%4$s"
            data-loader="%5$s"
            data-action="%2$s"
            data-screen="%6$s"
            data-toggle="modal" data-target="#position_label_modal_%4$s"
            data-popup-loader="%7$s"
        >',
            '#',
            $action,
            $data['alt'],
            $order_id,
            isset($data['loader']) ? $data['loader'] : '',
            !empty($screen) ? $screen : '',
            isset($data['popup_loader']) ? $data['popup_loader'] : ''
        );
    }
    else{
        printf( '
        <a  href="%1$s"
            id="btn-myparcel-%2$s-%4$s"
            class="btn btn-secondary %2$s ' . (isset($data["class_name"]) ? $data["class_name"] : '') . '"
            alt="%3$s"
            data-tip="%3$s"
            data-order-id="%4$s"
            data-loader="%5$s"
            data-action="%2$s"
            data-screen="%7$s"
            %6$s
            data-popup-loader="%8$s"
        >',
            $data['url'],
            $action,
            $data['alt'],
            $order_id,
            isset($data['loader']) ? $data['loader'] : '',
            $data['target'],
            !empty($screen) ? $screen : '',
            isset($data['popup_loader']) ? $data['popup_loader'] : ''
        );
    }
    ?>
        <img src="<?php echo $data['img']; ?>" data-toggle="tooltip" title="<?php echo $data['alt']; ?>" alt="<?php echo $data['alt']; ?>" width="16" class="myparcelnl_button_img">
    </a>
    <?php

}
if(isset($listing_position_label) && count($listing_position_label)){
?>

    <div id="position_label_modal_<?php echo $order_id; ?>" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title text-center"><?php echo $listing_position_label['title'];?></h4>
                </div>
                <div class="modal-body">
                    <div class="overlay  overlay-content-container">
                        <div class="overlay-content">
                            <div class="print-padding">
                                <div class="paper">
                                    <div class="a6-label active">
                                        <i class="fa fa-check" data-position_number="2"></i>
                                        <i class="fa fa-times hidden"></i>
                                    </div>
                                    <div class="a6-label">
                                        <i class="fa fa-check hidden" data-position_number="4"></i>
                                        <i class="fa fa-times "></i>
                                    </div>
                                    <div class="a6-label">
                                        <i class="fa fa-check hidden" data-position_number="1"></i>
                                        <i class="fa fa-times "></i>
                                    </div>
                                    <div class="a6-label">
                                        <i class="fa fa-check hidden" data-position_number="3"></i>
                                        <i class="fa fa-times "></i>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-print-label-order" data-order-id="<?php echo $order_id; ?>" data-url="<?php echo $listing_position_label['url'];?>">Print</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>

<?php } ?>
