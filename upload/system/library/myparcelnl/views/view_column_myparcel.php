<?php
foreach ($listing_actions as $action => $data) {
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
    ?>
        <img src="<?php echo $data['img']; ?>" data-toggle="tooltip" title="<?php echo $data['alt']; ?>" alt="<?php echo $data['alt']; ?>" width="16" class="myparcelnl_button_img">
    </a>
    <?php
}
?>