<?php
if (MyParcel()->settings->general->trackandtrace_myaccount) {
	if (version_compare(VERSION, '2.0.0.0', '>=')) {
		foreach ($actions as $key => $tracktrace_myaccount) {
			printf('<br/><a href="%s" title="MyParcel Track&Trace barcode" class="btn btn-link %s">%s</a>', $tracktrace_myaccount['url'], $key, $tracktrace_myaccount['code']);
		}

	} else {
		foreach ($actions as $key => $tracktrace_myaccount) {
			printf('<a href="%s" title="MyParcel Track&Trace barcode" class="btn btn-link %s">%s</a><br/>', $tracktrace_myaccount['url'], $key, $tracktrace_myaccount['code']);
		}
	}
}
?>