<?php
namespace Util;

/**
 * Description of FormHelper
 *
 * @author qiaoliang
 */
class FormHelper {
	/**
	 * 生成表单select
	 * @param string $name 表单元素name
	 * @param array $data 填充的数据
	 * @param string $selectValue 选中的数据
	 * @param array $options 其他选项
	 */
	public static function select($name, $data, $selectValue, $options)
	{
		$optionsStr = '';
		foreach($options as $k=>$v)
		{
			$optionsStr .= " {$k}=\"{$v}\"";
		}
		
		$selectHtml = "<select name=\"{$name}\" {$optionsStr}>";
		foreach($data as $k=>$v)
		{
			$selectHtml .= "<option value=\"{$k}\">{$v}</option>";
		}
		$selectHtml .= '</select>';
		return $selectHtml;
	}
}
