<div id="delivery-options-wrapper" <?php echo ((!empty($myparcel_country) && $myparcel_country != 'NL' && !$is_order_info) ? 'style="display:none;"' : '' ) ?>>
<?php if ((isset($data['theme']) && $data['theme'] != 'journal2') && ((!$is_order_info && !empty($data['info']) && !MyParcel()->helper->isModuleExist('d_quickcheckout', true)) || (!empty($data['info']) && $is_order_info))) : ?>
    <?php $info = $data['info'] ?>
    <?php $time = array_shift($info['time']) ?>
    <?php

    /** @var MyParcel_Shipment_Helper $shipment_helper **/
    $shipment_helper = MyParcel()->shipment->shipment_helper;
    $is_pickup = $shipment_helper->isPickup( null, $info );
    $is_mailbox = false;
    $time_title = '';

    if (isset($time['price_comment'])) {
        switch ($time['price_comment']) {
            case 'morning':
                $time_title = $lang->get( 'entry_morning_delivery');
                break;
            case 'standard':
                $time_title = $lang->get( 'entry_standard_delivery' );
                break;
            case 'night':
            case 'avond':
                $time_title = $lang->get( 'entry_evening_delivery');
                break;
            case 'mailbox':
                $is_mailbox = true;
        }
    } else {
        if (isset($info['price_comment'])) {
            $time_title = $lang->get( 'entry_time_not_specified');
        }
    }

    if (!$is_mailbox) :
        $time_range = $time['start'];
        if (isset($time['end'])) {
            $time_range .= ' - ' . $time['end'];
        }
        $time_title .= ' (' . $time_range . ')';

        /** @var MyParcel_Shipment_Checkout $checkout_helper **/
        $checkout_helper = MyParcel()->shipment->checkout;
        $delivery_type = $checkout_helper->getDeliveryTypeFromSavedData($data['info']);
        $signed_title = (!empty($data['signed']) ? $lang->get('entry_enabled_yes') : $lang->get('entry_enabled_no'));
        $home_title = (!empty($data['recipient_only']) || in_array($delivery_type, array($checkout_helper::DELIVERY_TYPE_MORNING, $checkout_helper::DELIVERY_TYPE_NIGHT))) ? $lang->get('entry_enabled_yes') : $lang->get('entry_enabled_no');
        ?>

        <?php printf('<div class="delivery-date"><strong>%s: </strong>%s</div>', $lang->get('Delivery date'),  date($lang->get('date_format'), strtotime($info['date'])), $time_title ); ?>
        <?php printf('<div class="delivery-time"><strong>%s: </strong>%s</div>', $lang->get('Delivery time'), $time_title ); ?>

        <?php if (!$is_pickup) : ?>
            <?php printf('<div class="delivery-recipient-only"><strong>%s: </strong>%s</div>', $lang->get('entry_home_address_only'), $home_title); ?>
            <?php printf('<div class="delivery-signed"><strong>%s: </strong>%s</div>', $lang->get('entry_signed'), $signed_title); ?>
        <?php endif; ?>

        <?php if ($is_pickup) : ?>
            <?php
            switch ($info['price_comment']) {
                case 'retail':
                    $title = $lang->get( 'entry_postnl_pickup');
                    break;
                case 'retailexpress':
                    $title = $lang->get( 'entry_postnl_pickup_express');
                    break;
                default:
                    $title = $lang->get( 'entry_postnl_pickup');
            }
            ?>
            <div class="delivery-pickup-address-title">
                <?php
                printf(
                    '<strong>%s: </strong>%s',
                    $lang->get('entry_pickup_type'),
                    $title
                )
                ?>
            </div>
            <div class="delivery-pickup-address">
                <?php
                printf(
                    '<strong>%s: </strong>%s, %s %s, %s, %s',
                    $lang->get('entry_pickup_address'),
                    $info['location'],
                    $info['street'],
                    $info['number'],
                    $info['city'],
                    $info['postal_code']
                )
                ?>
            </div>
            <div class="delivery-pickup-comment">
                <?php
                printf(
                    '<strong>%s: </strong>%s', $lang->get('entry_pickup_comment'),
                    $info['comment']
                )
                ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <!-- Mailbox mode -->
        <div class="delivery-mailbox-title">
            <?php
            printf(
                '<strong>%s: </strong>%s',
                $lang->get('entry_delivery_type'),
                $lang->get('entry_mailbox')
            )
            ?>
        </div>
    <?php endif; ?>


    <?php if (!$is_order_info) : ?>
    <div class="delivery-reset-wrapper">
        <button id="btn-delivery-reset" class="btn btn-success" title="<?php echo $lang->get('entry_edit_delivery_options') ?>">
            <?php echo $lang->get('entry_edit_delivery_options') ?>
        </button>
    </div>
    <?php endif; ?>

<?php else : ?>

    <iframe id="myparcel-iframe" onload="mypaLoaded()" frameborder="0" scrolling="auto" style="width: 100%;" height="<?php echo (MyParcel()->shipment->checkout->isMailboxAvailable() ? '450' : '400') ?>">Bezig met laden...</iframe>

<?php endif; ?>

    <?php if (!$is_order_info) : ?>
        <div id="mypa-chosen-delivery-options" style="width:100%;height:100%;display:none;">
            <myparcel id="myparcel"></myparcel>
            <input type="text"      name="mypa_data" id="mypa-input" value="<?php echo (!empty($data['data']) ? $data['data'] : '') ?>">
            <input type="checkbox"  name="mypa_signed" id="mypa-signed" <?php echo ((!empty($data['signed']) && $data['signed'] == 'on') ? 'checked="checked"' : '') ?>>
            <input type="checkbox"  name="mypa_recipient_only" id="mypa-recipient-only" <?php echo ((!empty($data['recipient_only']) && $data['recipient_only'] == 'on') ? 'checked="checked"' : '') ?>>
        </div>
        <script>
            <?php if (!empty($data['data'])) : ?>
            window.myparcel_data = '<?php echo html_entity_decode($data['data']) ?>';
            <?php endif; ?>
        </script>
    <?php endif; ?>
</div>