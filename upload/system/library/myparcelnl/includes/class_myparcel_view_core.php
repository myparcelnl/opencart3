<?php

class MyParcel_View_Core
{
    function render($view, $variables = array())
    {
        extract($variables, EXTR_OVERWRITE);
        require(MyParcel()->getViewDir($view));
    }
}