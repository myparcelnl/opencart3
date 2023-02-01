<?php
// Heading
$heading_title = 'Myparcel NL v1.1.10';
$_['heading_title'] = $heading_title;
// Text
$_['text_module']         = 'Modules';
$_['text_success']        = 'Success: You have modified module ' . $heading_title . '!';
$_['text_content_top']    = 'Content Top';
$_['text_content_bottom'] = 'Content Bottom';
$_['text_column_left']    = 'Column Left';
$_['text_column_right']   = 'Column Right';
$_['text_column_myparcel_header']   = 'MyParcel';
$_['text_myparcel_menu_title']   = 'MyParcel';

$_['entry_tab_general_title']           = 'General';
$_['entry_tab_export_title']            = 'Default Export Settings';
$_['entry_tab_checkout_title']          = 'Checkout';

// Entry
$_['entry_tab_1_title']                 = 'General';
$_['entry_tab_2_title']                 = 'Default Export Settings';
$_['entry_tab_3_title']                 = 'Checkout';
$_['entry_title_package_types']         = 'Package Types';
$_['entry_title_button_export']         = 'Export to MyParcel';
$_['entry_title_button_print']          = 'Print MyParcel label';
$_['entry_title_button_return']         = 'Email return label';
$_['entry_title_choose_position_label'] = 'Choose position label print';
$_['entry_mailbox'] = 'Mailbox';
$_['entry_delivery_type'] = 'Package Type';

$_['entry_tab_1_api']                      = 'API';
$_['entry_tab_1_api_setting']              = 'API settings';
$_['entry_tab_1_system_setting']           = 'System settings';
$_['entry_tab_1_admin_folder']             = 'Admin folder';
$_['entry_tab_1_generel_setting']          = 'General settings';
$_['entry_tab_1_label_display']            = 'Label display';
$_['entry_tab_1_radio_download_pdf']       = 'Download PDF';
$_['entry_tab_1_radio_open_pdf']           = 'Open de PDF in a new tab';
$_['entry_tab_1_trackandtrace_email']      = 'Track&trace in email';
$_['entry_tab_1_checkbox_trackandtrace_email'] = 'Add the track&trace code to emails to the customer.Note! When you select this option, make sure you have not enabled the track & trace email in your MyParcel backend.';
$_['entry_tab_1_add_trackandtrace_account'] = 'Track&trace in My Account';
$_['entry_tab_1_checkbox_trackandtrace_myaccount'] = 'Show track&trace trace code & link in My Account.';
$_['entry_tab_1_show_shipment_directly'] = 'Process shipments directly';
$_['entry_tab_1_checkbox_shipment_directly'] = 'When you enable this option, shipments will be directly processed when sent to myparcel.';
$_['entry_tab_1_order_status_automation'] = 'Order status automation';
$_['entry_tab_1_checkbox_order_status_automation'] = 'Automatically set order status to a predefined status after succesfull MyParcel export.
Make sure Process shipments directly is enabled when you use this option together with the Track&trace in email option, otherwise the track&trace code will not be included in the customer email.';
$_['entry_tab_1_automatic_order_status'] = 'Automatic order status';
$_['entry_tab_1_keep_old_shipments'] = 'Keep old shipments';
$_['entry_tab_1_checkbox_keep_old_shipments'] = 'With this option enabled, data from previous shipments (track & trace links) will be kept in the order when you export more than once.';
$_['entry_tab_1_label_use_addition_address_as_number_suffix'] = 'Use addition address as number suffix';
$_['entry_tab_1_checkbox_use_address1_and_address2'] = "'Address field 1' and  'address field 2' will both be used for the full address";
$_['entry_tab_1_checkbox_use_address2_as_number_suffix'] = 'Use address field 1 for \'street\', address field 2 for \'house number\'';
$_['entry_tab_1_checkbox_use_address3_as_number_suffix_1'] = 'Use address field 1 for \'street\', address field 2 for \'house number\' and ';
$_['entry_tab_1_checkbox_use_address3_as_number_suffix_2'] = ' for \'housenumber suffix\'';
$_['entry_tab_1_diagnostic_tools'] = 'Diagnostic tools';
$_['entry_tab_1_log_api_communication'] = 'Log API communication';
$_['entry_tab_1_checkbox_log_api_communication'] = 'Only enable this option when debugging!';
$_['entry_tab_1_download_log_file'] = 'Download log file';
$_['entry_tab_1_title_paper_format'] = 'Paper format';


$_['entry_tab_2_title_export_settings'] = 'Default export settings';
$_['entry_tab_2_title_package_types']   = 'Package Types';
$_['entry_tab_2_title_default_hs_code']   = 'Default HS code';
$_['entry_tab_2_title_default_country_of_origin']   = 'The country of origin';
$_['entry_tab_2_select_package_types'] = 'Select one or more shipping methods for each MyParcel package type';
$_['entry_tab_2_title_connect_customer_email'] = 'Connect customer email';
$_['entry_tab_2_checkbox_connect_customer_email'] = 'When you connect the customer email, MyParcel can send a Track&Trace email to this address. In your %s you can enable or disable this email and format it in your own style.';
$_['entry_tab_2_title_connect_customer_phone'] = 'Connect customer phone';
$_['entry_tab_2_checkbox_connect_customer_phone'] = 'When you connect the customer\'s phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.';
$_['entry_tab_2_title_extra_large_size'] = 'Extra large size (+ &euro;%s)';
$_['entry_tab_2_checkbox_extra_large_size'] = 'Enable this option when your shipment is bigger than 100 x 70 x 50 cm, but smaller than 175 x 78 x 58 cm. An extra fee of &euro; 2,45 will be charged.<br/>
<strong>Note!</strong> If the parcel is bigger than 175 x 78 x 58 of or heavier than 30 kg, the pallet rate of &euro; 70,00 will be charged.';
$_['entry_tab_2_title_address_only'] = 'Home address only (+ &euro;%s)';
$_['entry_tab_2_checkbox_address_only'] = 'If you don\'t want the parcel to be delivered at the neighbours, choose this option.';
$_['entry_tab_2_title_signature_delivery'] = 'Signature on delivery (+ &euro;%s)';
$_['entry_tab_2_checkbox_signature_delivery'] = 'The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signuture will be required.';
$_['entry_tab_2_title_return_no_answer'] = 'Return if no answer';
$_['entry_tab_2_checkbox_return_no_answer'] = 'By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for two weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option.';
$_['entry_tab_2_title_insured_shipment'] = 'Insured shipment (from + &euro;0.50)';
$_['entry_tab_2_title_insured_amount'] = 'Insured amount';
$_['entry_tab_2_title_insured_amount_custom'] = 'Insured amount (in euro)';
$_['entry_tab_2_checkbox_insured_shipment'] = 'By default, there is no insurance on the shipments. If you still want to insure the shipment, you can do that from &euro;0.50. We insure the purchase value of the shipment, with a maximum insured value of &euro; 5.000. Insured parcels always contain the options "Home address only" en "Signature for delivery"';
$_['entry_tab_2_title_label_description'] = 'Label description';
$_['entry_tab_2_textbox_label_description'] = 'With this option, you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in the MyParcel Backend. Use <strong>[ORDER_NR]</strong> to include the order number.';
$_['entry_tab_2_title_empty_parcel_weight'] = 'Empty parcel weight (grams)';
$_['entry_tab_2_textbox_empty_parcel_weight'] = 'Default weight of your empty parcel, rounded to grams.';
$_['entry_tab_2_select_insured_up_to_50']  = 'Insured up to &euro; 50 (+ &euro; 0.50)';
$_['entry_tab_2_select_insured_up_to_250'] = 'Insured up to &euro; 250 (+ &euro; 1.00)';
$_['entry_tab_2_select_insured_up_to_500'] = 'Insured up to &euro; 500 (+ &euro; 1.65)';
$_['entry_tab_2_select_insured_500']       = '> &euro; 500 insured (+ &euro; 1.65 / &euro; 500)';
$_['entry_tab_2_title_age_check']       = 'Age check';
$_['entry_tab_2_title_age_check_desrition']       = 'The recipient must sign for the package and must be at least 18 years old.';
$_['entry_tab_2_default_weight'] = 'Default Weight';


$_['entry_subtotal']                                   = 'Subtotal';
$_['entry_tab_3_enable_myparcel_shipping_message']     = 'Enable \'Myparcel Shipping\' to show the Myparcel delivery option in your checkout.(If don\'t use the Xtensions Best Checkout extension)';
$_['entry_tab_3_title_delivery_option']                = 'Delivery options';
$_['entry_tab_3_label_enable_delivery']                = 'Enable MyParcel delivery options';
$_['entry_tab_3_label_home_address_only']              = 'Home address only';
$_['entry_tab_3_label_signature_on_delivery']          = 'Signature on delivery';
$_['entry_tab_3_label_evening_delivery']               = 'Evening delivery';
$_['entry_tab_3_label_morning_delivery']               = 'Morning delivery';
$_['entry_tab_3_label_postnl_pickup']                  = 'PostNL pickup';
$_['entry_tab_3_label_early_postnl_pickup']            = 'Early PostNL pickup';
$_['entry_tab_3_title_shipment_processing_parameters'] = 'Shipment processing parameters';
$_['entry_tab_3_label_dropoff_days']                   = 'Dropoff days';
$_['entry_tab_3_label_cut_off_time']                   = 'Cut-off time';
$_['entry_tab_3_label_dropoff_delay']                  = 'Dropoff delay';
$_['entry_tab_3_label_delivery_days_window']           = 'Delivery days window';
$_['entry_tab_3_label_additional_fee']                 = 'Additional fee (optional)';
$_['entry_tab_3_textbox_cut_off_time']                 = 'Time at which you stop processing orders for the day (format: hh:mm). If not set, will be assumed as "15:30"';
$_['entry_tab_3_textbox_dropoff_delay']                = 'Number of days you take to process an order. It must be between 0 and 14. If not set, will be assumed as "0"';
$_['entry_tab_3_textbox_delivery_days_window']         = 'Number of days you allow the customer to postpone a shipment';
$_['entry_tab_3_select_dropoff_days']                  = 'Days of the week on which you hand over parcels to PostNL. If not set, Saturday and Sunday will be excluded';
$_['entry_tab_3_title_delivery_option']                = 'Delivery options';
$_['entry_tab_3_label_mailbox_settings']               = 'Mailbox options';
$_['entry_tab_3_label_mailbox_title']                  = 'Mailbox title';
$_['entry_tab_3_label_mailbox_fee']                    = 'Mailbox fee';
$_['entry_tab_3_label_mailbox_accept_weight']          = 'Mailbox weight';
$_['entry_tab_3_label_base_color']                     = 'Base color';
$_['entry_tab_3_label_highlight_color']                = 'Highlight color';
$_['entry_tab_3_label_custom_style']                   = 'Custom style';
$_['entry_tab_3_label_auto_google_fronts']             = 'Automatically load Google fonts';
$_['entry_tab_3_title_customizations']                 = 'Customizations';
$_['entry_tab_3_label_standard_delivery']              = 'Standard delivery';
$_['entry_tab_3_label_belgium_settings']               = 'Belgium pickup';
$_['entry_tab_3_label_belgium_default_fee']            = 'Belgium standard fee';
$_['entry_tab_3_label_belgium_pickup_fee']             = 'Standard pickup fee';
$_['entry_tab_3_label_cut_off_weekday']                = 'Weekdays';
$_['entry_tab_3_label_only_apply_for_xtension_checkout']= 'Only apply for Xtension Best Checkout';
$_['entry_tab_3_label_time_format']                    = 'Time format';
$_['entry_tab_3_label_time_format_description']        = 'H: hour, i: minute, s: second';
$_['entry_tab_3_label_distance']                       = 'Distance format';
$_['entry_tab_3_label_rounding_distance']              = 'Rounding distance format';
$_['entry_tab_3_label_rounding_distance_description']  = 'The optional number of decimal digits to round to if the distance format is kilometer';
$_['entry_tab_3_distance_format_kilometer']            = 'Kilometer';
$_['entry_tab_3_distance_format_meter']                = 'Meter';
$_['entry_tab_3_label_default_price_0_text']           = 'Default Price Text';
$_['entry_tab_3_label_default_price_0_text_description']= 'The text is shown when price is 0';



$_['entry_unknown_error']                = 'Unknown error';
$_['entry_api_error_with_order_id']      = 'Order #%s api error:';
$_['entry_update_order_status_error']    = 'An error occurs during the order status update';
$_['entry_order'] = 'Order';
$_['return_shipment_popup_title'] = 'Return shipment options';
$_['mssg_order_status_changed_by_myparcel'] = 'MyParcel Order Status Automation';
$_['print_batch'] = 'Print Batch';
$_['export_batch'] = 'Export Batch';
$_['export_print_batch'] = 'Export Print Batch';
$_['entry_delivery_options'] = 'Delivery Options';
$_['entry_home_address_only'] = 'Home address only';
$_['entry_signed'] = 'Signature on delivery';
$_['entry_enabled_no'] = 'No';
$_['entry_enabled_yes'] = 'Yes';
$_['entry_pickup_address'] = 'Pickup address';
$_['entry_pickup_comment'] = 'Pickup comment';
$_['entry_postnl_pickup'] = 'PostNL Pickup';
$_['entry_postnl_pickup_express'] = 'PostNL Pickup Express';
$_['entry_pickup_type'] = 'Pickup type';
$_['entry_delivery_date'] = 'Delivery date';
$_['date_format'] = 'Y-m-d';
$_['entry_delivery_time'] = 'Delivery time';
$_['entry_standard_delivery'] = 'Standard';
$_['entry_evening_delivery'] = 'Evening';
$_['entry_morning_delivery'] = 'Morning';
$_['entry_time_not_specified'] = 'Pickup';
$_['entry_order_details_tracktrrace'] = 'MyParcel Trace&Trace';
$_['entry_order_details_actions'] = 'MyParcel Actions';
$_['entry_order_details_shipment'] = 'MyParcel Shipment';
$_['entry_order_details_address'] = 'Shipping Address';
$_['entry_order_details_delivery_options'] = 'Delivery options';
$_['entry_pickup_title'] = 'Pick up at PostNL';
$_['entry_pickup_express_title'] = 'Extra early pick at TNT';
$_['entry_weight'] = 'Weight';

$_['Sunday']    = 'Sunday';
$_['Monday']    = 'Monday';
$_['Tuesday']   = 'Tuesday';
$_['Wednesday'] = 'Wednesday';
$_['Thursday']  = 'Thursday';
$_['Friday']    = 'Friday';
$_['Saturday']  = 'Saturday';

$_['label_hs_code'] = 'HS Code';
$_['label_country'] = 'Country Code';

$_['button_save'] = 'Save';
$_['button_send_to_customer'] = 'Send to customer';
$_['email_track_trace'] = 'You can track your order with the following PostNL track&trace code:';

$_['shipment_status_pending_concept'] = 'pending - concept';
$_['shipment_status_pending_registered'] = 'pending - registered';
$_['shipment_status_enroute_handed_to_carrier'] = 'enroute - handed to carrier';
$_['shipment_status_enroute_sorting'] = 'enroute - sorting';
$_['shipment_status_enroute_distribution'] = 'enroute - distribution';
$_['shipment_status_enroute_customs'] = 'enroute - customs';
$_['shipment_status_delivered_at_recipient'] = 'delivered - at recipient';
$_['shipment_status_delivered_ready_for_pickup'] = 'delivered - ready for pickup';
$_['shipment_status_delivered_package_picked_up'] = 'delivered - package picked up';
$_['shipment_status_inactive_concept'] = 'inactive - concept';
$_['shipment_status_inactive_registered'] = 'inactive - registered';
$_['shipment_status_inactive_enroute_handed_to_carrier'] = 'inactive - enroute - handed to carrier';
$_['shipment_status_inactive_enroute_sorting'] = 'inactive - enroute - sorting';
$_['shipment_status_inactive_enroute_distribution'] = 'inactive - enroute - distribution';
$_['shipment_status_inactive_enroute_customs'] = 'inactive - enroute - customs';
$_['shipment_status_inactive_delivered_at_recipient'] = 'inactive - delivered - at recipient';
$_['shipment_status_inactive_delivered_ready_for_pickup'] = 'inactive - delivered - ready for pickup';
$_['shipment_status_inactive_delivered_package_picked_up'] = 'inactive - delivered - package picked up';
$_['shipment_status_inactive_unknown'] = 'inactive - unknown';

$_['package_type_parcel']          = 'Parcel';
$_['package_type_mailbox']         = 'Mailbox package';
$_['package_type_unpaid_letter']   = 'Unpaid letter';
$_['package_type_digital_stamp']   = 'Digital Stamp';

//order->myparcel
$_['entry_order_myparcel_shipment_type'] = 'Shipment type';
$_['entry_order_myparcel_calculated_weight'] = 'Calculated weight: %s';
$_['entry_order_myparcel_number_of_label'] = 'Number of labels';
$_['entry_order_myparcel_custom_id'] = 'Custom ID (top left on label)';
$_['entry_order_myparcel_insured_for'] = 'Insured for';
$_['entry_order_detail_myparcel_shipment'] = 'MyParcel shipment:';
$_['entry_order_detail_myparcel_status'] = 'Status:';
$_['entry_order_myparcel_text_extra_large_size'] = 'Extra large size';
$_['entry_order_myparcel_text_home_address_only'] = 'Home address only';
$_['entry_order_myparcel_text_signature_on_delivery'] = 'Signature on delivery';
$_['entry_order_myparcel_text_return_if_no_answer']  = 'Return if no answer';
$_['entry_order_myparcel_text_age_check']  = 'Age check';
$_['entry_order_myparcel_text_insured_home'] = 'Insured + home address only + signature on delivery';
$_['entry_order_myparcel_text_standar_insurance'] = 'Standard insurance up to €500 + signature on delivery';
$_['entry_order_myparcel_text_insurance'] = 'Insurance';
$_['entry_order_myparcel_text_insurance_amount'] = 'Insurance amount';

// Error
$_['error_permission']    = 'Warning: You do not have permission to modify module ' . $heading_title;
$_['error_code']          = 'Code Required';
// Error
$_['error_permission']      = 'Warning: You do not have permission to modify module ' . $heading_title;
$_['error_selected_orders_not_exported_yet'] = 'The selected orders have not been exported to MyParcel yet!';
$_['error_unknown_response'] = 'Unknown error, API response:';
$_['error_unknown'] = 'Unknown error';
$_['error_cannot_save_order_shipment'] = 'Cannot save order #%s shipment #%s';
$_['error_create_related_return_shipment_for_order'] = 'An error occurred while creating return shipment for order #%s';
$_['error_return_options_is_empty'] = 'Options for related return shipment is empty';
$_['error_cannot_export_order_shipment'] = 'Cannot export order #%s ';
// Error mailbox settings
$_['error_mailbox_title_empty'] = 'Mailbox title cannot be empty';
$_['error_mailbox_fee_empty'] = 'Mailbox fee cannot be empty and must be numeric';
$_['error_mailbox_weight_empty'] = 'Mailbox weight limit cannot be empty and must be numeric';

// Log Message
$_['log_pdf_data_received'] = 'PDF data received';
$_['error_amount'] = "Insured Amount must be greater than 500";
$_['error_dropoff_delay'] = "Dropoff delay must be a number";
$_['error_only_recipient_fee'] = "Home address only must be a number";
$_['error_delivery_days_window'] = "Delivery day window must be a number";
$_['error_signed_fee'] = "Signature on delivery fee must be a number";
$_['error_night_fee']  =  "Evening delivery fee must be a number";
$_['error_morning_fee']  =  "Morning delivery fee must be a number";
$_['error_pickup_fee']  =  "PostNL pickup fee must be a number";
$_['error_pickup_express_fee']  =  "Early PostNL pickup fee must be a number";
$_['error_cut_off_time']  =  "The format of the cut off time is incorrect";
$_['error_dropoff_delay_wrong_range'] = 'Dropoff delay must be between 0 and 14';
$_['error_delivery_windows'] = 'Delivery day windows cannot be empty and must be numeric';
$_['error_empty_insured_amount']  =  "Insured amount must be numeric and greater than zero";
$_['error_empty_parcel_weight'] = "Empty parcel weight must be a number";
$_['shipping_methods_via'] = 'Via';
$_['error_sort_order_must_be_numeric'] = 'Sort order must be a number';
$_['error_export_print_batch_error'] = 'Some of the selected orders might not be exported due to invalid address';
$_['error_signature_required'] = 'Signature and Recipient Only are required for Pickup and Pick Express. It cannot be disabled.';
$_['error_cut_off_not_correct_format'] = 'Cut off time is not in correct format.';
