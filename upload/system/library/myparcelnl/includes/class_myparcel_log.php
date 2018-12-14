<?php

class MyParcel_Log
{
    function add($message)
    {
        if (MyParcel()->settings->general->log_api_communication) {
            $log_file = MyParcel()->getLogsDir('myparcel_log.txt');

            $current_date_time = date("Y-m-d H:i:s");
            $message = $current_date_time . ' ' . $message . "\n";

            file_put_contents($log_file, $message, FILE_APPEND);
        }
    }
}

return new MyParcel_Log();