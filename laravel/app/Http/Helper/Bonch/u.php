<?php


namespace App\Http\Helper\Bonch;

class u {

	// Выделение имени файла и расширения
	public static function get_filename_parts($fname){
		$parts=array();
		if (!preg_match('/(.+?)\\.(\w+)$/',basename($fname),$p))return -1;
			else {
			$parts['name']=$p[1];
		    $parts['extension']=strtolower($p[2]);
		}
		return $parts;
	}

	public static function get_delimited_string_as_array($delimited_string){
		$parts=array();
		$parts=preg_split('{[\s\r\n\\\/]*[;,][\s\r\n\\\/]*}u',$delimited_string);
		return $parts;
	}

	public static function array_from_delim_string($str){
		return self::get_delimited_string_as_array($str);
	}
	// Получение сетевого имени файла без слэша впереди
	public static function get_net_path($fname=''){
		$fname=self::sla((!$fname) ? __FILE__ :$fname);

		if (isset($_SERVER["DOCUMENT_ROOT"]))
			return str_replace($_SERVER["DOCUMENT_ROOT"].'/','',$fname);

		if (preg_match('{.+/(.+/.+\.php)$}',$fname,$res)){
			return $res[1];
		}
		return '';
	}

	//Сортировка ключей для одноуровневых библиографических списков
	public static function list_struct_keys($keys,$records){
		sort($keys);
		foreach ($keys as $key){
			$kp=explode('***',$key);
			$list_struct_array[$kp[0]][]=$records[$kp[2]];
		}

		return $list_struct_array;
	}

	// Удаление каталога целиком
	public static function del_full_dir($directory,$file_string='',$del_dirs=true){
		if (!file_exists($directory))
			return -1;
		$dir = @@opendir($directory);
		while(($file = readdir($dir))!==false){
		  	if ( is_file("$directory/$file")){
		  		if ((!$file_string || strpos($file,$file_string)!==false) || ($file_string==='no_ext' && strpos($file,'.')===false))
		    		@@unlink("$directory/$file");
		  	}else {

			    if( is_dir("$directory/$file") && ($file!= ".") && ($file!= ".."))
			  	  self::del_full_dir("$directory/$file");
		    }
		  }
		closedir ($dir);
		//Удаление директории имеет большое значение при обновлении -- иначе на удалённой машине будет создаваться ненужная структура каталогов.
		if ($del_dirs)
			@@rmdir ($directory);
	}


	// Копирует все файлы и каталоги из первой во вторую директорию
	public static function syncronyze_dirs($sourse,$destination){
  	  $sourse=trim($sourse,'/');
	  $dir = opendir($sourse);
	  while(($file = readdir($dir))!==false){
	    if (is_file("$sourse/$file")) @copy("$sourse/$file","$destination/$file");
	    else  {
		    if( is_dir("$sourse/$file") && ($file!= ".") && ($file!= "..")){
		    	@mkdir("$destination/$file");
		    	self::syncronyze_dirs("$sourse/$file","$destination/$file");
		    }

	    }
	  }
	  closedir ($dir);
	}



	// Заменяет метки типа !ххх! на соответствующие значения.
	public static function parse_pft($pft_content,$rep_array){
		foreach ($rep_array as $key=>$value){
			$labels[]='!'.$key.'!';
			$values[]=$value;
		}
		return str_replace($labels,$values,$pft_content);
	}

		// Удаляет комментарии из PFT
	public static function clean_pft_comments($pft_content){
		return preg_replace('{/\*.*}',' ',$pft_content);
	}


	// Получение каталога для хранения временных файлов и файлов сессий.
	public static function get_temp($static=false){

		if (!$static){
			$sp=ini_get('session.save_path');
			if (is_writable( $sp ) &&  strpos($sp,'.')===false)
			return self::sla($sp);
		}

		if (isset($HTTP_ENV_VARS['TEMP'])) {
			if (is_writable( $HTTP_ENV_VARS['TEMP'] ))
			return self::sla($HTTP_ENV_VARS['TEMP']);
		}

		if (isset($HTTP_ENV_VARS['TMP'])) {
			if (is_writable( $HTTP_ENV_VARS['TMP'] ))
			return self::sla($HTTP_ENV_VARS['TMP']);
		}


		if (is_writable( 'C:/irbiswrk' ))
		return 'C:/irbiswrk';

		@mkdir('C:/temp');
		if (is_writable( 'C:/temp' ))
				return 'C:/temp';

	}
	//Замена на обратный слеш
	public static function sla($string){
		return str_replace(array(chr(92).chr(92),chr(92)),'/',$string);
	}
	//Замена на DOS слеш
	public static function sld($string){
		return str_replace('/',chr(92),$string);
	}

	public static function sldq($string){
		return str_replace(array(chr(92),'/'),'\\',$string);
	}
	// Чтение INI файла стандарта ИРБИС без учёта комментариев
	public static function ini_read($ini_path){
		if (($ini_txt=file_get_contents($ini_path))===false) return -1;
		return self::ini_txt_read($ini_txt);
	}

	public static function ini_txt_read($ini_txt){
		if (!trim($ini_txt)) return -1;

		$strings=explode("\n",trim($ini_txt));
		$section_name='NO_SECTIONS';
		foreach ($strings as $string){
			if (substr(trim($string),0,1)=='#')
				continue;
			if (preg_match('{\\[(.+)\\]}',$string,$rez)){
				$section_name=strtoupper($rez[1]);
			}elseif (preg_match('{(.+?)=(.+)}',$string,$rez)){
				$ini_array[$section_name][strtoupper($rez[1])]=trim($rez[2]);
			}

		}
		return $ini_array;
	}

	// Запись INI файла c комментариями
	public static function ini_write($ini_path,$ini_array){

		if (!($inifile=fopen($ini_path,'w')) || count($ini_array)<1) return -1;

		foreach ($ini_array as $section=>$section_value){
			if ($section!=='NO_SECTIONS')
			fwrite($inifile,"\n\n[".$section."]\n");

			foreach ($section_value as $par=>$value){
				if (is_numeric($par))
					fwrite($inifile,"$value\n");
				else
					fwrite($inifile,"$par=$value\n");
			}
		}

		fclose($inifile);
	return true;
	}


	//Чтение csv файла стандарта Excel: разделитель полей -- точка с запятой, записей -- перевод строки
	public static function read_csv($csv_path){

		if (!($csv=@fopen($csv_path,'r')))
			return null;


		for($i=0; $i<($fields=fgetcsv($csv,5000,';'));$i++){
			if ($i>0){
				for($j=0; $j<count($fields);$j++){
			      @$records[$i-1][$headers[$j]]=$fields[$j];
				}
			}else
				$headers=$fields;
		}
		fclose($csv);
		return 	$records ? $records :-1;
	}

	public static function add_csv_string($csv_path,$assoc_array=array()){
		$string='';
		$fields='';

		if (!is_array($assoc_array)|| !$assoc_array)
			return -3;

		if (!($csv=@fopen($csv_path,'a+')))
			return -1;

		//rewind($fp);
		$fields=fgetcsv($csv,5000,';');
		if (!is_array($fields)){
			// Если нет заголовка, формируем его
			$fields=array_keys($assoc_array);

			if(!fwrite($csv,implode(';',$fields)))
				return -4;

		}
		// Предполагается наличие загловка
		foreach ($fields as $field_name){
			$string.=str_replace(array("\r","\n","\t",";"),' ',u::utf_win(u::ga($assoc_array,$field_name,''))).';';
		}

		fseek ($csv,SEEK_END);

		if(!fwrite($csv,"\r\n".$string))
			return -4;
		fclose($csv);

		return 0;
	}


	// Чтение MNU файла стандарта ИРБИС
	public static function mnu_read($mnu_path,$convert_from_win=false){
		if (!file_exists($mnu_path))
			return -1;

	$keys=file($mnu_path);
		for($i=0;$i<count($keys)-1;$i+=2){
			if (trim($keys[$i])==='*****') break;
			$readed_mnu[trim($keys[$i])]=trim(($convert_from_win ? u::win_utf($keys[$i+1]) : $keys[$i+1]));
		}
	return $readed_mnu;
	}

	public static function mnu_write($file_path,$assoc_array,$convert_from_utf=false){
		if (!is_array($assoc_array))
			return false;

		$mnu='';
		foreach ($assoc_array as $key=>$value){
			if ($convert_from_utf)
			$mnu.=self::utf_win($key)."\r\n".self::utf_win($value)."\r\n";
				else
			$mnu.="$key\r\n$value\r\n";
		}
		return @file_put_contents($file_path,$mnu);

	}

	public static function to_translit($string) {
	$translit_table = array(
	   "Ґ"=>"G","Ё"=>"YO","Є"=>"E","Ї"=>"YI","І"=>"I",
	   "і"=>"i","ґ"=>"g","ё"=>"yo","№"=>"#","є"=>"e",
	   "ї"=>"yi","А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
	   "Д"=>"D","Е"=>"E","Ж"=>"ZH","З"=>"Z","И"=>"I",
	   "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
	   "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
	   "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
	   "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
	   "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
	   "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"zh",
	   "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
	   "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
	   "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
	   "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"",
	   "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
	   "."=>"_","_"=>"_"," "=>"_","("=>"_",")"=>"_","/"=>"_",";"=>"_",":"=>"_",","=>"","'"=>""
	  );
	return strtr($string, $translit_table);
	}


	//Преобразование к URI формату
	public static function to_uri($string){
		return urlencode(str_replace('+','%2B',$string));
	//	return urlencode($string);
	}

	//Запись файла конфигурации
	public static function cfg_write($filename,$cfg_data,$array_name='CFG'){
		//echo $filename;
		if (!($file=@fopen($filename,'w')))
		return false;
		//file_put_contents('C:/12',var_export($cfg_data['forms_profile'],true));
		//if (!var_export($CFG))  'Пустой файл CFG';
		fwrite($file,"<?php \n \$$array_name=\$GLOBALS['$array_name']=".var_export($cfg_data,true).";\n ?>");
		fclose($file);
		return true;
	}


	public static function win_utf($s){
		$t='';
		if (is_string($s)){
			if (extension_loaded('iconv')){
				return @iconv('Windows-1251','UTF-8//IGNORE', $s);
			}else {

				$c209 = chr(209); $c208 = chr(208); $c129 = chr(129);
				for($i=0; $i<strlen($s); $i++) {
					$c=ord($s[$i]);
					if ($c>=192 and $c<=239) $t.=$c208.chr($c-48);
					elseif ($c>239) $t.=$c209.chr($c-112);
					elseif ($c==184) $t.=$c209.$c209;
					elseif ($c==168) $t.=$c208.$c129;
					else $t.=$s[$i];

				}
				return $t;

			}
		}
		return $s;
	}

	public static function detect_utf($Str) {
		if (is_string($Str)){
			 for ($i=0; $i<strlen($Str); $i++) {
			  if (ord($Str[$i]) < 0x80) $n=0; # 0bbbbbbb
			  elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			  elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			  elseif ((ord($Str[$i]) & 0xF0) == 0xF0) $n=3; # 1111bbbb
			  else return false; # Does not match any model
			  for ($j=0; $j<$n; $j++) { # n octets that match 10bbbbbb follow ?
			   if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) return false;
			  }
			 }
		}
	 return true;
	}


	public static function insured_utf_win($s){
		if (self::detect_utf($s))
			return self::utf_win($s);
		else
			return $s;
	}

	public static function utf_win($s){
	$out="";
	if (is_string($s)){
		if (extension_loaded('iconv')){
			return @iconv('UTF-8','Windows-1251//IGNORE', $s);
		} else {
			$c1="";
			$byte2=false;
				for ($c=0;$c<strlen($s);$c++){
					$i=ord($s[$c]);
					if ($i<=127) $out.=$s[$c];
					if ($byte2){
						$new_c2=($c1&3)*64+($i&63);
						$new_c1=($c1>>2)&5;
						$new_i=$new_c1*256+$new_c2;
						if ($new_i==1025){
							$out_i=168;
						}else{
							if ($new_i==1105){
								$out_i=184;
								}else {
								$out_i=$new_i-848;
							}
						}
						$out.=chr($out_i);
						$byte2=false;
					}
					if (($i>>5)==6) {
					$c1=$i;
					$byte2=true;
					}
				}
			return $out;
		}
	}
	return $s;

   }

   public static function iso_utf($str){
   	if (is_string($str) && extension_loaded('iconv'))
   		return @iconv("ISO-8859-1", "UTF-8//IGNORE",$str);
   	else
   		return $str;
   }
  //SET BEFORE
  public static function sb($prefix,$value){
  	if (trim($value))
  	return  $prefix.$value;
  }
  //SET AFTER
  public static function sa($suffix,$value){
  	if (trim($value))
  	return  $value.$suffix;
  }
  //SET BEFORE AFTER
  public static function sba($prefix='',$value,$suffix=''){
  	if ($value)
  	return  $prefix.$value.$suffix;
  }
    //GET VALUE
  public static function gv($variable,$default=''){
  	return (isset($variable) and $variable) ? $variable : $default;
  }
    //GET ARRAY ELEMENT
  public static function ga($array=array(),$element_name='',$default='',$prf_before='',$prf_after=''){
  	if (isset($array[$element_name]) and $array){
  		if (is_string($array[$element_name])|| is_numeric($array[$element_name]))
  				return ($array[$element_name] ? $prf_before : '').$array[$element_name].($array[$element_name] ? $prf_after : '');
  		else
  				return $array[$element_name];

  	}else {
  		return $default;
  	}
  }

	public static function get_microtime(){
		$microtime=explode(' ',microtime());
		return (int)$microtime[1].substr($microtime[0],2,4);
	}

	public static function get_cache($type,$expired,$req_conditions=''){


		$hash=abs(crc32($req_conditions));
		$dir_name=substr($hash,0,4);
		$temp_path=u::get_temp();
		$file_path="{$temp_path}/$dir_name/$type$hash";
		if (file_exists($file_path)){
			if (filemtime($file_path)>time()-$expired || $expired==-1){
				return unserialize(file_get_contents($file_path));
			}//else unlink($file_path);
		}
		return -1;
	}

	public static function set_cache($type,$expired,$req_conditions,$data){


		$hash=abs(crc32($req_conditions));
		$temp_path=u::get_temp();
		$dir_name=substr($hash,0,4);
		$file_path="$temp_path/$dir_name/$type$hash";

		if (!file_exists("{$temp_path}/$dir_name"))
			@mkdir("{$temp_path}/$dir_name");

		if (!@file_put_contents($file_path,serialize($data)))
			return false;
		return true;
	}

	public static function del_cache($type,$req_conditions){
		$hash=abs(crc32($req_conditions));
		$temp_path=u::get_temp();
		$dir_name=substr($hash,0,4);
		$file_path="$temp_path/$dir_name/$type$hash";
		@unlink($file_path);
		return true;
	}

	public static function get_first_assoc_key($assoc_array){
		if (!$assoc_array) return '';
		if (is_array($assoc_array)){
			foreach($assoc_array as $key=>$value){
				return $key;
			}
		}
	}


	public static function get_first_assoc_value($assoc_array){
		if (!$assoc_array) return '';
		if (is_array($assoc_array)){
			foreach($assoc_array as $key=>$value){
				return $value;
			}
		}
	}

	public static function make_dirs_of_path($file_path,$start_dir){

		$relative_path=trim(str_ireplace(array(trim($start_dir,'/').'/',basename($file_path)),'',$file_path),'/');
		$dir_names_array=explode('/',$relative_path);

		$checked_dir=$start_dir;
		foreach($dir_names_array as $dir){
			$checked_dir.='/'.$dir;
			if (!file_exists($checked_dir)){
				if  (!@mkdir($checked_dir))
					return false;
			}
		}
		return true;
	}

	public static function r0($number){
		if ($number<0)
			return 0;
		return $number;

	}

	public static function clean_cr_lf($str){
		if (!$str or !is_string($str))
			return $str;
		return 	str_replace(array("\x0A","\x0D"),'',$str);
	}

	public static function clean_quotes($str){
		if (!$str or !is_string($str))
			return $str;
		return 	str_replace(array("'",'"'),'',$str);

	}


	public static function eval_str($str){
		eval("\$res='$str';");
		return $res;
	}

	public static function get_number($str){
		if (preg_match('{([\d]+)}',$str,$p))
			return $p[1];
		return '';
	}

	public static function array_encoding($array=array(),$output_encoding='utf'){
		foreach ($array as &$val){
			if(is_array($val)){
				$val=self::array_encoding($val,$output_encoding);
			}elseif(is_string($val)){
				if ($output_encoding==='utf')
					$val=self::win_utf($val);
				else
					$val=self::utf_win($val);
			}
		}
		return $array;
	}



	public  static function set_flag($flag_name){
		$stop_file_path=u::get_temp(true)."/$flag_name";
		@file_put_contents($stop_file_path,time());
	}

	public static function del_flag($flag_name){
		$stop_file_path=u::get_temp(true)."/$flag_name";
		@@unlink($stop_file_path);

	}
	public static function is_flag($flag_name,$actuality_time=90){
		$stop_file_path=u::get_temp(true)."/$flag_name";
		if (!file_exists($stop_file_path))
			return false;
		if (($file_time=@file_get_contents($stop_file_path))){
			if ($file_time<time()-$actuality_time){
				@@unlink($stop_file_path);
				return false;
			}
				return true;
		}
	}



	public static function format_irb_date($irb_date_string='',$format='Y.m.d'){
		if (!$irb_date_string)
			return '';

		$date_string='';
		if (is_numeric($irb_date_string) and strlen($irb_date_string)==8){
			$irb_date_string=substr($irb_date_string,6,2).'.'.substr($irb_date_string,4,2).'.'.substr($irb_date_string,0,4);
			// Если дата с временем
		}elseif(strlen($irb_date_string)>10){
			//$irb_date_string=substr($irb_date_string,0,10);
			$irb_date_string=substr($irb_date_string,3,2).'.'.substr($irb_date_string,0,2).'.'.substr($irb_date_string,6,4);
		}
		$date_string=date($format,strtotime($irb_date_string));
		return $date_string;

	}

	public static function is_russian_string($str){
		if (preg_match("{[А-Я]+}u", $str))
		return true;
		else
		return false;
	}

	// Получение ассоциативного массива на основе списка
	public static function list_to_assoc($list=array(),$key_name=''){
		$assoc_array=array();
		if (self::is_assoc_array($list))
			return $list;
		foreach ($list as &$value){
			if (isset($value[$key_name])){
				$key_value=$value[$key_name];
				// Практического смысла не имеет, удаляем
				unset($value[$key_name]);
				$assoc_array[$key_value]=$value;
			}
		}
		return $assoc_array;
	}


   public static function is_assoc_array(array $array){
        // Keys of the array
        $keys = array_keys($array);
        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }

   public static function unif($str){
   	return mb_strtoupper(trim($str),'UTF-8');
   }

   public static function get_protocol(){
   		if (isset($_SERVER['HTTPS']) &&
		    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		  $protocol = 'https';
		} else {
		  $protocol = 'http';
		}
		return $protocol;
   }

   public static function get_user_ip(
        $ip_param_name = null,
        $allow_non_trusted = false,
        array $non_trusted_param_names = array('HTTP_X_REAL_IP','HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR')
    ){

    	if((empty($ip_param_name) || !is_string($ip_param_name)) && !empty( $_SERVER['REMOTE_ADDR'])){
    	// если не задан или не корректен
            $ip = $_SERVER['REMOTE_ADDR'];
        }else{
        //иначе используем нужную переменную
            if(!empty($_SERVER[$ip_param_name]) && filter_var($_SERVER[$ip_param_name], FILTER_VALIDATE_IP)){
            // если переменная подошла как надо
                $ip = $_SERVER[$ip_param_name];
            }else if($allow_non_trusted){
            // мы решили пойти на крайний шаг и использовать сырые данные
                foreach($non_trusted_param_names as $ip_param_name_nt){
                    if($ip_param_name === $ip_param_name_nt)
                    // мы уже проверяли эту переменную
                        continue;
                    if(!empty($_SERVER[$ip_param_name_nt]) && filter_var($_SERVER[$ip_param_name_nt], FILTER_VALIDATE_IP)){
                    // если переменная подошла как надо
                        $ip = $_SERVER[$ip_param_name_nt];
                        break;
                    }
                }
            }
        }

        return $ip;


   }


   public static function is_url($string){
   		return (filter_var($string, FILTER_VALIDATE_URL)? true :false);
   }


}

// Исключительно для целей тестирования. Фактически не используются.
if (!extension_loaded('mbstring')){
	function mb_strtoupper($str,$encoding=''){
		if ($encoding!=='UTF-8') return $str;
		$str=u::utf_win($str);
		$str=strtoupper($str);
		return u::win_utf($str);
	}

	function mb_strtolower($str,$encoding=''){
		if ($encoding!=='UTF-8') return $str;
		$str=u::utf_win($str);
		$str=strtolower($str);
		return u::win_utf($str);
	}

	function mb_substr($str,$start,$end,$encoding=''){
		if ($encoding!=='UTF-8') return $str;
		$str=u::utf_win($str);
		$str=substr($str,$start,$end);
		return u::win_utf($str);
	}

	function mb_strlen($str,$encoding=''){
		if ($encoding!=='UTF-8') return $str;
		$str=u::utf_win($str);
		return strlen($str);
	}


	function mb_strpos($haystack,$needle,$offset,$encoding=''){
		if ($encoding!=='UTF-8') return strpos($haystack,$needle,$offset);
		return strpos(u::utf_win($haystack),u::utf_win($needle),$offset);
	}



}
  //echo u::sla('ffffffff/dd');


//u::del_full_dir('C:\irbiswrk','',false);
  //echo u::format_irb_date('20061001','Y.m.d');
 //  echo u::unif('Студ,Преп,Сотр,Асп');
  //print_r(u::get_delimited_string_as_array(u::unif('Студ,Преп,Сотр,Асп')));

?>
