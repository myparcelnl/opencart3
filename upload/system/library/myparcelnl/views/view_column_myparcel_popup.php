<?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>
    <div id="modal-myparcel-shipment" class="modal fade myparcel-modal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo MyParcel()->lang->get('return_shipment_popup_title') ?></h4>
                </div>
                <div id="modal-body-myparcel-shipment" class="modal-body">

                </div>
            </div>

        </div>
    </div>
<?php else : ?>
    <div id="modal-myparcel-shipment" data-version="1" class="white-popup-block mfp-hide">
        <h1><?php echo MyParcel()->lang->get('return_shipment_popup_title') ?></h1>
        <div id="modal-body-myparcel-shipment" class="modal-body">

        </div>
    </div>

    <?php
    $registry = MyParcel::$registry;
    $url = $registry->get('url');
    $session = $registry->get('session');
    $token = $session->data['token'];
    ?>
    <form method="POST" id="form_selected_orders" target="_blank" action="<?php echo $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'printBatch'), array('token' => $token));?>">

    </form>
<?php endif; ?>