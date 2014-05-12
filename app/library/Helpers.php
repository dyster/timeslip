<?php

class Helpers extends \Phalcon\Tag
{

    /**
     * Shortens a string if it is longer then the specified length, default is 20 chars.
     *
     * @param $string
     * @param $length
     * @internal param $array
     * @return string
     */
    static public function shortify($string, $length = 20)
    {
        if(strlen($string) > $length)
            return substr($string, 0, 17)."..";
        else
            return $string;
    }

}