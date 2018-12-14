<?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>
    <tr>
        <td>
            <img data-toggle="tooltip" title="<?php echo 'MyParcel Actions'; ?>" src="<?php echo MyParcel()->getImageUrl() . 'icon.png' ?>"/>
            <?php echo MyParcel()->lang->get('entry_order_details_actions'); ?>
        </td>
        <td>
            <?php echo $buttons ?>
        </td>
    </tr>
<?php else: ?>
    <tr>
        <td>
            <?php echo MyParcel()->lang->get('entry_order_details_actions'); ?>
        </td>
        <td>
            <?php echo $buttons ?>
        </td>
    </tr>
<?php endif; ?>
