<table class="ocmyparcel_settings_table" style="width: auto">
	<tr>
		<td>
			<?php echo $data['entry_order_myparcel_shipment_type']; ?>:<br/>
			<small class="calculated_weight"><?php echo sprintf($data['entry_order_myparcel_calculated_weight'], MyParcel()->shipment->shipment_helper->getTotalOrderWeight($order_id)); ?></small>
		</td>
		<td>
			<?php
				if (isset($recipient['cc']) && !MyParcel()->helper->isEUCountry($recipient['cc'])) {
					unset($data['package_types'][2]); // mailbox package
				}

				if ($is_pickup) {
					unset($data['package_types'][2]); // mailbox package
					unset($data['package_types'][3]); // unpaid letter
					$data['package_types'][1] .= ' (Pakjegemak)';
				}
				if ($return) {
					unset($data['package_types'][2]); // mailbox package
					unset($data['package_types'][3]); // unpaid letter
				}
			?>
			<select name="myparcel_options[<?php echo $order_id; ?>][package_type]" class="package_type">
				<?php foreach ($data['package_types'] as $key => $package_type) : ?>
					<?php $selected = (isset($export_settings['package_type']) && ($export_settings['package_type'] == $key) ? 'selected="selected"' : '' ) ?>
					<option <?php echo $selected ?> value="<?php echo $key; ?>" ><?php echo $package_type; ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<?php if (!$return) : ?>
	<tr>
		<td>
			<?php echo $data['entry_order_myparcel_number_of_label']; ?>:
		</td>
		<td>
			<input type="number" step="1" min="1" name="myparcel_options[<?php echo $order_id; ?>][extra_options][number_of_copies]" value="<?php echo !empty($number_of_copies)?$number_of_copies:1;?>" size="2">
		</td>
	</tr>
	<?php endif; ?>
</table>
<?php if ($return) : ?>
<br>
<?php endif; ?>
<?php
	if (isset($recipient['cc']) && !MyParcel()->helper->isEUCountry($recipient['cc'])) {
		$export_settings['insured'] = 1;
		$export_settings['insurance']['amount'] = 499;
		$is_eu = false;
	} else {
		$is_eu = true;
	}
?>

<table class="ocmyparcel_settings_table parcel_options">
	<tbody>
		<tr>
			<td>
				<?php
                    $checked = (isset($export_settings['large_format']) && ($export_settings['large_format']==1))?'checked="checked"':'';
                ?>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][large_format]" value="0" />
				<input <?php echo $checked; ?> type="checkbox" name="myparcel_options[<?php echo $order_id; ?>][large_format]" value="1" class=""><?php echo $data['entry_order_myparcel_text_extra_large_size']; ?>
			</td>
			<td class="oc_option_cost">

			</td>
		</tr>

		<?php if ($is_eu): ?>
		<tr>
			<td>
				<?php
                    $checked = (isset($export_settings['only_recipient']) && ($export_settings['only_recipient']==1))?'checked="checked"':'';
                ?>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][only_recipient]" value="0" />
				<input <?php echo $checked; ?> type="checkbox" name="myparcel_options[<?php echo $order_id; ?>][only_recipient]" value="1" class=""><?php echo $data['entry_order_myparcel_text_home_address_only']; ?>
			</td>
			<td class="oc_option_cost">

			</td>
		</tr>


		<tr>
			<td>
				<?php
                    $checked = (isset($export_settings['signature']) && ($export_settings['signature']==1))?'checked="checked"':'';
                ?>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][signature]" value="0" />
				<input <?php echo $checked; ?> type="checkbox" name="myparcel_options[<?php echo $order_id; ?>][signature]" value="1" class=""><?php echo $data['entry_order_myparcel_text_signature_on_delivery']; ?>
			</td>
			<td class="oc_option_cost"></td>
		</tr>

		<tr>
			<td>
				<?php
                    $checked = (isset($export_settings['return']) && ($export_settings['return']==1))?'checked="checked"':'';
                ?>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][return]" value="0" />
				<input <?php echo $checked; ?> type="checkbox" name="myparcel_options[<?php echo $order_id; ?>][return]" value="1" class=""><?php echo $data['entry_order_myparcel_text_return_if_no_answer']; ?>
			</td>
			<td class="oc_option_cost">
			</td>
		</tr>

		<tr>
			<td>
				<?php
					$checked = (isset($export_settings['insured']) &&  $export_settings['insured']==1)?'checked="checked"':'';
					if (!isset($export_settings['insurance'])) {
						$export_settings['insurance']['amount'] = '';
					}
                ?>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][insured]" value="0" />
				<input <?php echo $checked; ?> type="checkbox" name="myparcel_options[<?php echo $order_id; ?>][insured]" value="1" class="insured"><?php echo $data['entry_order_myparcel_text_insured_home']; ?>
				<input type="hidden" name="" class="insured_enable" value="<?php echo (isset($export_settings['insured']))?$export_settings['insured']:0; ?>" />
			</td>
			<td class="oc_option_cost">
			</td>
		</tr>
		<?php else: ?>
		<tr>
			<td>
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][insured]" value="1" class="insured" /><?php echo $data['entry_order_myparcel_text_standar_insurance']; ?>
			</td>
			<td class="oc_option_cost">
			</td>
		</tr>
		<?php endif; ?>



		<?php if ($is_eu): ?>
		<tr <?php echo (!empty($checked) ? '' : 'style="display:none"') ?>>
			<td><?php echo $data['entry_order_myparcel_text_insurance']; ?></td>
			<td>
				<select name="myparcel_options[<?php echo $order_id; ?>][insured_amount_selectbox]" class="insured_amount">
					<?php foreach ($data['insured_amounts'] as $key => $insured_amount) : ?>
                        <?php $selected = ((!empty($export_settings['insured_amount_selectbox']) && ($export_settings['insured_amount_selectbox']) == $key) ? 'selected="selected"' : '' ) ?>
					<option <?php echo $selected ?> value="<?php echo $key; ?>"><?php echo $insured_amount; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr <?php echo (!empty($checked) ? '' : 'style="display:none"') ?>>
			<td>
				<?php echo $data['entry_order_myparcel_text_insurance_amount']; ?>
			</td>
			<td>
				<input type="text" name="myparcel_options[<?php echo $order_id; ?>][insured_amount_custom]" value="<?php echo (isset($export_settings['insured_amount_custom']))?$export_settings['insured_amount_custom']:0; ?>" style="width:100%" class="insured_amount">
			</td>
		</tr>
		<?php else: ?>
		<tr>
			<td colspan="2" style="display:none;">
				<input type="hidden" name="myparcel_options[<?php echo $order_id; ?>][insured_amount_input_not_eu]" value="<?php echo (isset($export_settings['insured_amount_custom']))?($export_settings['insured_amount_custom']/100):0; ?>">
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php
	$description = isset($export_settings['label_description'])?$export_settings['label_description']:"";
?>

<table class="ocmyparcel_settings_table">
	<tbody>
		<tr>
			<td><?php echo $data['entry_order_myparcel_custom_id']; ?></td>
			<td>
				<input type="text" name="myparcel_options[<?php echo $order_id; ?>][label_description]" value="<?php echo $description ?>" style="width:100%">
			</td>
		</tr>

		<?php if ($description && trim($description) == '[ORDER_NR]') : ?>
		<tr>
			<td>
			</td>
			<td>
				<label>Order ID: <?php echo $order_id; ?></label>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

<div class="oc_save_shipment_settings <?php echo ($return ? 'clearfix' : '') ?>">
	<?php if ($return) : ?>
		<a 	class="button save btn btn-primary pull-right"
		   	id="btn-myparcel-submit-return"
		  	data-order="<?php echo $order_id; ?>"
		   	data-url="<?php echo $url; ?>"
			data-order-id="<?php echo $order_id; ?>"
			data-loader="<?php echo MyParcel()->getImageUrl() . 'myparcel-spin.gif' ?>"
		    data-screen="<?php echo $screen; ?>"
		>
			<?php echo $data['button_send_to_customer'] ?>
		</a>
	<?php else : ?>
		<a class="button save btn btn-primary" data-order="<?php echo $order_id; ?>" data-url="<?php echo $url; ?>"><?php echo $data['button_save'] ?></a>
		<img src="<?php echo MyParcel()->getImageUrl() . 'myparcel-spin.gif' ?>" class="oc_spinner waiting">
	<?php endif; ?>
</div>