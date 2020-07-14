<td>
    <?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>
        <img data-toggle="tooltip" title="<?php echo 'MyParcel Shipping Address'; ?>" src="<?php echo MyParcel()->getImageUrl() . 'icon_delivery_details.png' ?>"/>
    <?php endif; ?>
        <?php echo MyParcel()->lang->get('entry_order_details_address') ?>
</td>
<td>
    <div class="oc_shipment_options">
        <?php echo $mp_packet_type; ?>
        <div class="oc_shipment_options_form" style="display: none;">
            <?php echo $ship_to_myparcel; ?>
        </div>
    </div>
</td>