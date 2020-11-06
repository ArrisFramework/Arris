<?php


namespace ArrisDeprecated;


class removed_methods
{
    /**
     *
     * @return string
     */
    public function getIP_simple():string
    {
        return
            isset($_SERVER['HTTP_CLIENT_IP'])
                ? $_SERVER['HTTP_CLIENT_IP']
                : (
            isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                ? $_SERVER['HTTP_X_FORWARDED_FOR']
                : $_SERVER['REMOTE_ADDR']
            );
    }
    
}