
<?php
foreach ($track_trace_shipments as $shipment_id => $shipment):

	if (empty($shipment['tracktrace'])) {
		continue;
	}
	?>
	<?php if (version_compare(VERSION, '2.0.0.0', '>=')) : ?>

	<td>
		<img data-toggle="tooltip" title="<?php echo MyParcel()->lang->get('entry_order_details_tracktrrace'); ?>" src="<?php echo MyParcel()->getImageUrl() . 'icon_track_trace.png' ?>"/>
	</td>
	<td>
		<table class="tracktrace_status">
			<thead>
			<tr>
				<th>Track&amp;Trace</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<?php
				printf ('<td><a href="%s">%s</a></td><td>%s</td>', $shipment['tracktrace_url'], $shipment['tracktrace'], $shipment['status']);
				?>
			</tr>
			</tbody>
		</table>
	</td>
<?php else : ?>
	<?php if (isset($_GET['route']) && $_GET['route'] == 'sale/order/info') : ?>
		<td>
			<?php echo 'MyParcel Track&Trace'; ?>
		</td>
		<td>
			<table class="tracktrace_status">
				<thead>
				<tr>
					<th><?php echo MyParcel()->lang->get('entry_order_details_tracktrrace') ?></th>
					<th>Status</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<?php
					printf ('<td><a href="%s">%s</a></td><td>%s</td>', $shipment['tracktrace_url'], $shipment['tracktrace'], $shipment['status']);
					?>
				</tr>
				</tbody>
			</table>
		</td>
	<?php else : ?>
		<table class="tracktrace_status">
			<thead>
			<tr>
				<th>Track&amp;Trace</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<?php
				printf ('<td><a href="%s">%s</a></td><td>%s</td>', $shipment['tracktrace_url'], $shipment['tracktrace'], $shipment['status']);
				?>
			</tr>
			</tbody>
		</table>
	<?php endif; ?>
<?php endif; ?>
<?php endforeach;?>