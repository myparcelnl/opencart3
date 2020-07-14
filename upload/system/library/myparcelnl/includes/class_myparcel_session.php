<?php

class MyParcel_Session
{
    static public $_session = 'plugin:myparcelnl';

    function __construct()
    {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            if (session_id() == '') session_start();
        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }
    }


    /**
     * __get function.
     *
     * @param mixed $key
     * @return mixed
     */
    public function __get( $key ) {
        return $this->get( $key );
    }

    /**
     * __set function.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function __set( $key, $value ) {
        $this->set( $key, $value );
    }

    /**
     * __isset function.
     *
     * @param mixed $key
     * @return bool
     */
    public function __isset( $key ) {
        return isset( $_SESSION[ ( $key ) ] );
    }

    /**
     * __unset function.
     *
     * @param mixed $key
     */
    public function __unset( $key ) {
        if ( isset( $_SESSION[ $key ] ) ) {
            unset( $_SESSION[ $key ] );
        }
    }

    /**
     * Get a session variable.
     *
     * @param string $key
     * @param  mixed $default used if the session variable isn't set
     * @return mixed value of session variable
     */
    public function get( $key, $default = null )
    {
        $helper = MyParcel()->helper;
        $key = $this->sanitize_key( $key );
        return isset( $_SESSION[ $key ] ) ? $helper->maybe_unserialize( $_SESSION[ $key ] ) : $default;
    }

    /**
     * Set a session variable.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set( $key, $value )
    {
        $helper = MyParcel()->helper;
        if ( $value !== $this->get( $key ) ) {
            $_SESSION[ $this->sanitize_key( $key ) ] = $helper->maybe_serialize( $value );
        }
    }

    /**
     * Add a session variable into array.
     *
     * @param string $key
     * @param string $group
     * @param mixed $value
     */
    public function add( $group, $key, $value )
    {
        $helper = MyParcel()->helper;
        $_SESSION[ $this->sanitize_key( $group ) ][$key] = $helper->maybe_serialize( $value );
    }

    function sanitize_key( $key )
    {
        $key = strtolower( $key );
        $key = preg_replace( '/[^a-z0-9_\-]/', '', $key );
        return $key;
    }
}

return new MyParcel_Session();