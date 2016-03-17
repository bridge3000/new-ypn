<?php
namespace Util;

class FormHelper 
{
    public static function select($attrList, $dataList, $default)
    {
        $str = '<select ';
        
        foreach($attrList as $k=>$v)
        {
            $str .= $k . '="' . $v . '" ';
        }
        
        $str .= '>';
        
        foreach($dataList as $k=>$v)
        {
            $str .= '<option value="' . $k . '"';
                    
            if ($k == $default)
            {
                $str .= 'selected="selected"';
            }
                    
            $str .= '>' . $v . '</option>';
        }
        $str .= '</select>';
        echo $str;
    }
}

?>