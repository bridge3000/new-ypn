<?php
namespace Util;

class CommonUtil {
    public static function isDate($str)
    {
        if (preg_match ("/\d{4}-\d{1,2}-\d{1,2}/", $str)) {
            return true;
        } else {
            return false;
        }
    }
}

?>
