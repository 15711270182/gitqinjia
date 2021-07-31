<?php
namespace lib;
class Hzpy{	
/* 	汉字转拼音类
	@param string  $beizhuanhanzi 被转换的字符串。
	@param boolean $zhiqushouzimu 只取每组拼音的第一个字母。
	@param boolean $shouzimudaxie 每组拼音首字母大写。
	@param boolean $quanbudaxie 全部转换为大写。
	@param string  $tihuan 非汉字0-9a-zA-Z的替换符。
	@param array   $fujiazidian 添加自定义的字典，覆盖内置字典。格式 ：['萝'=>'gou','卜'=>'mao']
	*/
	function zhuan($beizhuanhanzi,$zhiqushouzimu=false,$shouzimudaxie=false,$quanbudaxie=false,$tihuan='_',$fujiazidian = Array()){
		$py = '';
		preg_match_all("/./u",$beizhuanhanzi,$hanzizu);
		if(count($hanzizu[0]) !=0){
			$zidian = json_decode(file_get_contents(dirname(__FILE__).'/zidian.json'),true);
			
			if(!empty($fujiazidian)){
				$zidian = $fujiazidian + $zidian;
			}
			foreach($hanzizu[0] as $danci){
				if(isset($zidian[$danci])){
					if($shouzimudaxie and strlen($zidian[$danci]) > 1){ // 汉字处理
							if($zhiqushouzimu){
								$py = $py . ucfirst(substr($zidian[$danci],0,1)); // 只取首字母
							}else{
								$py = $py . ucfirst($zidian[$danci]);
							}
					}else{	
						if($zhiqushouzimu){ // 0-9a-zA-Z处理
							$py = $py . substr($zidian[$danci],0,1); // 只取首字母
						}else{
							$py = $py . $zidian[$danci]; 
						}
					}
				}else{  // 其他字符处理
					$py = $py . $tihuan;
				}
			}
			if($quanbudaxie){ // 全部取大写
				$py = strtoupper($py);
			}
		}else{
			$py = '';
		}
		return $py;
	}
}
?>