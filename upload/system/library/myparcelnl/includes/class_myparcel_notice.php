<?php

class MyParcel_Notice
{
    public function __construct()
    {

    }

    function count( $notice_type = '' )
    {
        $notice_count = 0;
        $all_notices  = MyParcel()->session->get( 'myparcel_notices', array() );
        if ( isset( $all_notices[$notice_type] ) ) {

            $notice_count = abs( intval( sizeof( $all_notices[$notice_type] ) ) );

        } elseif ( empty( $notice_type ) ) {

            foreach ( $all_notices as $notices ) {
                $notice_count += absint( sizeof( $all_notices ) );
            }

        }

        return $notice_count;
    }

    function has_notice( $message, $notice_type = 'success' )
    {
        $notices = MyParcel()->session->get( 'myparcel_notices', array() );
        $notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();
        return array_search( $message, $notices ) !== false;
    }

    function add( $message, $notice_type = 'success' )
    {
        $notices = MyParcel()->session->get( 'myparcel_notices', array() );
        $notices[$notice_type][] = $message;

        MyParcel()->session->set( 'myparcel_notices', $notices );
    }

    function clear_notices()
    {
        MyParcel()->session->set( 'myparcel_notices', null );
    }

    function print_notices() {

        $all_notices  = MyParcel()->session->get( 'myparcel_notices', array() );
        $notice_types = array( 'error', 'success', 'notice', 'warning' );

        echo '<div class="myparcel-message-wrapper">';

        foreach ( $notice_types as $notice_type ) {
            if ( $this->count( $notice_type ) > 0 ) {
                foreach ($all_notices[$notice_type] as $notice) {
                    echo '<div class="alert alert-' . $notice_type . (version_compare(VERSION, '2.0.0.0', '>=') ? '' : ' opencart1') . '"> <i class="fa fa-check-circle"></i> ';
                    echo $notice;
                    if (version_compare(VERSION, '2.3.0.0', '>=')) {
                        echo '<button type="button" class="close" data-dismiss="alert">×</button>';
                    }
                    echo '</div>';
                }
            }
        }

        echo '</div>';

        $this->clear_notices();
    }
}

return new MyParcel_Notice();