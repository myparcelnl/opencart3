<td>
    <?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>
        <img data-toggle="tooltip" title="<?php echo 'MyParcel Delivery Options'; ?>" src="<?php echo MyParcel()->getImageUrl() . 'icon_delivery_options.png' ?>"/>
    <?php endif; ?>
        <?php echo MyParcel()->lang->get('entry_order_details_delivery_options') ?>
</td>

<td>
    <table class="ocmp_delivery_options_wrapper">
        <tbody>
        <tr>
            <td>
                <div class="ocmp_delivery_options">
                    <?php echo $delivery_options_html; ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</td>