<?php
	foreach ($track_trace_shipments as $shipment_id => $shipment):
		if (empty($shipment['tracktrace'])) {
			continue;
		}
?>
<td>
	<?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>
		<img data-toggle="tooltip" title="<?php echo 'MyParcel Latest Exported Data'; ?>" src="<?php echo MyParcel()->getImageUrl() . 'icon.png' ?>"/>
	<?php else: ?>
		<?php echo MyParcel()->lang->get('entry_order_details_shipment') ?>
	<?php endif; ?>
</td>
<td>
	<table class="tracktrace_status">
		<thead>
			<tr>
				<th><?php echo $labels['entry_order_detail_myparcel_shipment']; ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<div class="wcmp_shipment_summary">
						<div class="delivery-options"></div>				
						<a href="#" class="wcmp_show_shipment_summary"><span class="encircle wcmp_show_shipment_summary">i</span></a>
						<div class="wcmp_shipment_summary_list" style="display: none;">
							<?php echo $labels['entry_order_detail_myparcel_status']; ?> 
							<a href="<?php echo $shipment['tracktrace_url']; ?>" class="myparcel_tracktrace_link" target="_blank" title="<?php echo $shipment['tracktrace']; ?>"><?php echo $shipment['status'];?></a>
							<br><?php echo $labels['entry_order_myparcel_shipment_type']; ?>: 
							<?php
								echo $package_types[$shipment['shipment']['options']['package_type']];
							?>
							<ul class="wcmyparcel_shipment_summary">
								<?php 
									foreach ($option_strings as $key => $label) {
										if (isset($shipment['shipment']['options'][$key]) && (int) $shipment['shipment']['options'][$key] == 1) {
											printf('<li class="%s">%s</li>', $key, $label);
										}
									}
									// Insurance
									if (!empty($shipment['shipment']['options']['insurance'])) {
										$price = number_format ( $shipment['shipment']['options']['insurance']['amount'] / 100, 2 );
										printf('<li>%s: € %s</li>', $labels['entry_order_myparcel_insured_for'], $price);
									}
									// Custom ID
									if (!empty($shipment['shipment']['options']['label_description'])) {
										printf('<li>%s: %s</li>', $labels['entry_order_myparcel_custom_id'], $shipment['shipment']['options']['label_description']);
									}
								?>
							</ul>
						</div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</td>
<?php endforeach;?>