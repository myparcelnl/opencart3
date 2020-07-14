<?php
/*
 * Check if the current page is order info
 * Then add global js variables
 * */

$route_order_edit = (version_compare(VERSION, '2.0.0.0', '>=') ? 'sale/order/edit' : 'sale/order/update');
?>
<?php  if (isset($_GET['route']) && ($_GET['route'] == 'sale/order/info' || $_GET['route'] == $route_order_edit || $_GET['route'] == 'account/order/info') && !empty($_GET['order_id'])) : ?>
    <?php
        $ajax_get_total_details = MyParcel()->helper->add_query_arg(
            array(
                'route' => 'extension/myparcelnl/myparcel_delivery/total_details',
            ),
            MyParcel()->getRootUrl() . 'index.php'
        );

        $ajax_get_loading_icon = MyParcel()->getImageUrl() . 'myparcel-spin.gif';

        $order_id = $_GET['order_id'];
        $delivery_options = MyParcel()->shipment->checkout->getDeliveryDataFromOrder($order_id);
        $delivery_options = json_encode($delivery_options);
    ?>
    <script>
        jQuery( function( $ ) {
            window.myparcel_delivery_options = <?php echo $delivery_options; ?>;
            window.myparcel_ajax_get_total_details_url = "<?php echo $ajax_get_total_details ?>";
            window.myparcel_loading_icon = "<?php echo $ajax_get_loading_icon ?>";
            window.myparcel_order_id = "<?php echo $order_id ?>";
        })
    </script>
<?php endif; ?>