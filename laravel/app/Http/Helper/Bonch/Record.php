<?php

namespace App\Http\Helper\Bonch;

class Record
{
    public $Content;
    public $mfn;
    public $Status;
    public $RecVersion;

    public function __construct($rec=''){
        $this->Content = array();
        $this->CleanVersion();
        if ($rec) $this->ParseIrbisRec($rec);
    }


	public function GetVersion(){
	 	return $this->RecVersion;
	}

	public function SetVersion($ver){
	 	$this->RecVersion=$ver;
	}


	public function SetStatus($status){
	  $this->Status=$status;
	}

    public function CleanVersion(){
        $this->mfn = -1;
        $this->Status = 0;
        $this->RecVersion = 0;
	}

    public function __toString()
    {
		return (string)($this->GetAsIrbRec());
	}

    public function GetAsWin(){
  		$new_rec=clone $this;

		foreach($this->Content as $key=>$value){
			for($i=0;$i<count($value);$i++){
				$new_rec->Content[$key][$i]=u::utf_win($value[$i]);
			}
		}

		return $new_rec;
	}


	public function GetAsUtf(){

  		$new_rec=clone $this;

		foreach($this->Content as $key=>$value){
			for($i=0;$i<count($value);$i++){
				$new_rec->Content[$key][$i]=u::win_utf($value[$i]);
			}
		}

		return $new_rec;
	}

    public function ApplyFstResult($rec_fst_translation_string){
	      //if ($res < 0)  return $res;
	      $this->Clear();
	      $m1 = explode("\x01", $rec_fst_translation_string);
	      if (is_array($m1))
	       foreach ($m1 as $m2)
	       {
	       	if (!$m2) continue;

	        $tag = '';
	        $j = 0;
	        while (($m2[$j] >= '0') && ($m2[$j] <= '9')){
	            $tag .= $m2[$j];
	            $j++;
	        }

	        $tag = $tag + 0;
	        $f = substr($m2, $j + 1);
	        if (($tag > 0) && ($f != '')){
	         $f1=explode("\r",$f);
	         foreach($f1 as $f2)
	          if (trim($f2,"\r\n")!='')
	            $this->AddField($tag, trim($f2,"\r\n"));
	        }
	       }
    }

    public function SetFromMarc($mrc, $cp='')
    {
        $this->Clear();
        $leader=array();
        $count=0;
        //str_replace(array("\xA7","\xEB","\xEC","\xE6","\xE5","\xE7"), '', $mrc,$count);
      /*  if ($count)
        	$cp='iso';*/
        //$mrc = iconv("ISO-8859-1", "UTF-8//IGNORE",$mrc);



        $len = strlen($mrc);
        if ($len < 25)
            return - 141;
        $leader['recsize'] = substr($mrc, 0, 5);
        if ((int)$leader['recsize'] != (int)$len)
            return - 141;
        $leader['recstatus'] = substr($mrc, 5, 1);
        $leader['rectype'] = substr($mrc, 6, 1);
        $leader['recbiblevel'] = substr($mrc, 7, 1);
        $leader['rectreelevel'] = substr($mrc, 8, 1);
        $leader['flen'] = substr($mrc, 10, 1);
        $leader['sflen'] = substr($mrc, 11, 1);
        $leader['bdataaddr'] = substr($mrc, 12, 5);
        if (($leader['bdataaddr'] < 25) || ($leader['bdataaddr'] > $leader['recsize']))
            return - 141;
        $leader['codelevel'] = substr($mrc, 17, 1);
        $leader['catform'] = substr($mrc, 18, 1);
        $dircnt = ($leader['bdataaddr'] - 25) / 12;
        $cont=array();
        $cont['999'][] = substr($mrc, 5, 20);
        for ($i = 0; $i < $dircnt; $i++) {
            $tag = substr($mrc, (24 + 12 * $i), 3);
            $flen = substr($mrc, (27 + 12 * $i), 4);
            $fstart = substr($mrc, (31 + 12 * $i), 5);
            $fld = substr($mrc, $fstart + $leader['bdataaddr'], $flen - 1);
            if ($fstart + $flen > $leader['recsize'])
                return - 141;
            $fld = str_replace("\x1F", '^', $fld);
            if ($cp==='win')
             	$fld = u::win_utf($fld);
             elseif($cp==='iso')
            	$fld = u::iso_utf($fld);
            $cont[$tag][] = $fld;
        }
        $this->SetContent($cont);
        return 0;
    }


   protected function GetLeader()
    {
     $c=$this->GetContentA();
     $leader='';
     if (strlen($c['leader'])==24) return $c['leader'];
     $v999=$this->GetField(999,1);
     if (strlen($v999)==24) return $v999;
     if (strlen($v999)==8) return sprintf("#####%s22#####%s450 ", substr($v999,0,5), substr($v999,5,3));
     return '#####nam  22##### i 450 ';
    }

	public  function GetAsIrbRec()
    {
        $rec = $this->Content;
        $res = $this->mfn."#".$this->Status."\r\n0#".$this->RecVersion."\r\n";
        if (!is_array($rec)) return '';
        foreach ($rec as $field => $occs)
        {
            if (is_numeric($field))
                if (is_array($occs))
                    foreach ($occs as $occ => $val) $res .= $field.'#'.$val."\r\n";
        }
        return $res;
    }

    public function GetMfn()
    {
        return $this->mfn;
    }

    public function GetStatus()
    {
        return $this->Status;
    }

    public function GetRecVersion()
    {
        return $this->RecVersion;
    }

    public function GetContent(){
		return $this->Content;
	}


    public function SetContent($ar)
    {
        $this->Content = $ar;
    }


    public function Clear()
    {
        $this->Content = array();
        $this->mfn = -1;
        $this->Status = 0;
        $this->RecVersion = 0;
    }

    public function ParseIrbisRec($r)
    {

    	/* Структура записи:
    	mfn#статус[#]
    	статус#версия
    	метка поля#значение

    	Статус может принимать значения:
    	64 - запись заблокирована

    	*/
    	if (!$r)
    		return null;
        $rec = array();
        if (!is_array($r))
        {

            $strings_list = explode("\r", $r);
            $rm1 = explode('#', $strings_list[0]);
            $this->mfn = $rm1[0];
            $this->Status = $rm1[1] ? $rm1[1] :0;
            if ($this->mfn <= 0)
            	$this->mfn = -1;
            $rm1 = explode('#', $strings_list[1]);
            $this->RecVersion = $rm1[1];

            if (isset($strings_list[2]) and strpos($strings_list[2],'#{')){
            	unset($strings_list[2]);
            }

            unset($strings_list[0]);
            unset($strings_list[1]);
            $r = $strings_list;
        }
        foreach ($r as $f)
        {

        	if (($pos = strpos($f, "#"))!==false)
            {
                $rec[(int)substr($f, 0, $pos)][] = substr($f, $pos + 1);
            }
        }
        $this->Content = $rec;
    }


    public function DeleteField($field, $occ=0){
    	if ($occ == 0)
            unset($this->Content[$field]);
        else{
        	if (!empty($this->Content[$field][$occ - 1])){
	        	unset($this->Content[$field][$occ - 1]);
	        	//$this->Content[$field]=array_values($this->Content[$field]);
        	}
        }
    }

    public function RemoveField($field, $occ=0){
		$this->DeleteField($field, $occ);
    }

    public function FieldDelete($field, $occ=0){
		$this->DeleteField($field, $occ);
    }

	public function DeleteSubField($field, $occ=0, $subfield = null){
        if ($occ==0){
       		$field_content =&$this->Content[$field];
       		if ($field_content){
	       		foreach ($field_content as &$field_occ)
			        $field_occ=preg_replace('{(\^'.$subfield.'[^\\^]*)}i','',$field_occ);
       		}
        }else
	        $this->SetSubField($field, $occ, $subfield, '');
	}

	public function RemoveSubField($field, $occ=0, $subfield=null){
    	$this->DeleteSubField($field, $occ, $subfield);
    }

    public function DeleteAllSubFields($field, $subfield){
    	$this->DeleteSubField($field, 0, $subfield);
    }

    public function DeleteAllFields($field){
    	$this->DeleteField($field, 0);
    }


    public function AddField($field, $value)
    {
        // Не вижу никакого смысла предполагать, что $this->Content не является массивом,
        //такак как массив инициализируется ещё в конструкторе.

    	$this->Content[$field][] = $value;
    }


    public function AddFieldUnique($field, $value){
    	if (!$this->SearchFieldOcc($field,$value,'',false)){
			$this->AddField($field,$value);
			return true;
		}
		return false;
    }

    public function AddFieldContent($field, $values)
    {
        if (is_array($values)){
        	$this->Content[$field]=(isset($this->Content[$field])) ? $this->Content[$field] : array();
    		$this->Content[$field] = array_unique(array_merge($this->Content[$field],$values));
        }
    }



    public function GetFieldContent($field){
        	return (isset($this->Content[$field])) ? $this->Content[$field] : array();
    }
    public function GetFieldOccCount($field){
        	return (isset($this->Content[$field])) ? count($this->Content[$field]) : 0;
    }

    public function GetFieldOcc($fld)
    {
		if (isset($this->Content[$fld]))
			return count($this->Content[$fld])+1;
		else
			return 0;

    }
    public function GetField($fld, $occ=0)
    {
        $c = $this->Content;
        if  (!$occ) {
        	return (isset($c[$fld])) ? $c[$fld] : array();
        }
        return (isset($c[$fld][$occ - 1])) ? $c[$fld][$occ - 1] : '';
    }

    public function IsField($fld, $occ=0)
    {
        $c = $this->Content;
        if  (!$occ) {
        	return (isset($c[$fld])) ? true : false;
        }
        return (isset($c[$fld][$occ - 1])) ? true : false;
    }

    public function SetField($fld, $occ=0, $val=null)
    {
    	if (empty($this->Content[$fld]) && !$val)
    	return;

        if (!$occ && is_array($val)){
        	$this->Content[$fld]=$val;
        }else {
	        $this->Content[$fld][$occ - 1] = $val;
        }
    }

    public function SetFieldContent($fld, $val)
    {
        if (is_array($val))
        $this->Content[$fld]=$val;
    }

    public function GetSubField($fld, $occ=1, $subf='')
    {
    	if (!$subf and $subf!=='0')
    		return $this->GetField($fld,$occ);

		 if (isset($this->Content[$fld][$occ - 1])) {

		 	$field=$this->Content[$fld][$occ - 1];

		 }else {
		 	return '';
		 }

    	if (preg_match('{\^'.$subf.'([^\\^]+)}i',$field,$res))
			return $res[1];
			else
			return '';
    }

    public function GetSubFieldAsList($fld,$subf=''){

    }

    public function SetSubField($field, $occ=1, $subfield='', $new_value='')
    {
    	if (!$subfield && $subfield!=='0')
    		return $this->SetField($field,$occ,$new_value);

    	$number=$occ-1;

		if ($subfield=='*'){
			if (isset($this->Content[$field][$number]) && ($pos=strpos($this->Content[$field][$number],'^'))!=0)
				$this->Content[$field][$number]=str_replace(substr($this->Content[$field][$number],0,$pos),$new_value,$this->Content[$field][$number]);
			elseif ($new_value && isset($this->Content[$field][$number]))
				$this->Content[$field][$number]=$new_value.$this->Content[$field][$number];
			elseif ($new_value && empty($this->Content[$field][$number]))
				$this->Content[$field][$number]="$new_value";
			return;
		}

        if (isset($this->Content[$field][$number]) && preg_match('{(\^'.$subfield.'[^\\^]*)}i',$this->Content[$field][$number],$rez))
			$this->Content[$field][$number]=str_replace($rez[1],($new_value ? "^$subfield$new_value" : ''),$this->Content[$field][$number]);
		elseif ($new_value && isset($this->Content[$field][$number]))
			$this->Content[$field][$number].="^$subfield$new_value";
		elseif ($new_value && empty($this->Content[$field][$number]))
			$this->Content[$field][$number]="^$subfield$new_value";

    }






    public function SearchFieldOcc($fld,$string,$subf='',$truncation=true){
    	$string=mb_strtoupper($string,'UTF-8');
    	if (!$string)
    		return false;

    		for($i=1;$i<$this->GetFieldOcc($fld);$i++){

    			$haystack= $subf ? mb_strtoupper($this->GetSubField($fld,$i,$subf),'UTF-8'): mb_strtoupper($this->GetField($fld,$i),'UTF-8');

				if (!$haystack)
					continue;

					//echo "|$haystack|";

				if ((strpos($haystack,$string)!==false && $truncation) || $haystack===$string){
					return $i;
				}

			}
			return false;
    }

    public function SearchFieldValues($fld,$string,$subf='',$truncation=true){
        	$res=array();
    		for($i=1;$i<$this->GetFieldOcc($fld);$i++){
				if ($subf) {
					if ((strpos($this->GetSubField($fld,$i,$subf),$string)!==false && $truncation) || $this->GetSubField($fld,$i,$subf)===$string){
						$res[]=$this->GetField($fld,$i);
					}

				}else {
					if ((strpos($this->GetField($fld,$i),$string)!==false && $truncation) || $this->GetField($fld,$i)===$string){
						$res[]=$this->GetField($fld,$i);
					}
				}
			}
			return $res;
    }


    public function SearchSubFieldValues($fld,$string,$search_subf='',$output_subf='',$truncation=true){
     	$res=array();
     	$fields=$this->SearchFieldValues($fld,$string,$search_subf,$truncation);

     	foreach($fields as $field){
	     	if (preg_match('{\^'.$output_subf.'([^\\^]+)}i',$field,$subfields)){
	     		$res[]=$subfields[1];
	     	}
    	}

    	return 	$res;
    }


   	public function pfte($pft,$occ=1){
		if (preg_match_all('{
		(?:\'([^\']+?)\')#Безусловный литерал
		|

		(?:
			(?:([\|\"])([^\2]+?)\2)?	 #Условный литерал слева

			(v|d)(\d+)\^?([[:alpha:]]?) #Метка поля

			(?:([\|\"])([^\7]+?)\7)? #Условный литерал справа

		)

		}xi',$pft,$pockets,PREG_SET_ORDER)===false) return '';
		//|(?:\#|\/)
		//print_r($pockets);
		//OUTPUT TEXT
		/* @var $r record */
		$o='';
		foreach($pockets as $a){
			//Добавляем к существующему массиву пустые элементы чтобы избежать проблем с их недоступностью
			//$a=array_merge($a,array_fill(count($a),10,''));
			//print_r($a);
			if ($a[1])
			 $o.=$a[1];
			 else {
			 	//FIELD
			 	$f=($a[6])? $this->GetSubField($a[5],$occ,$a[6]): $this->GetField($a[5],$occ);
			 	if ($f && isset($a[3]) &&  ($a[2]==='|' || $occ==1)) $o.=$a[3];
			 	if (strtolower($a[4])==='v')$o.=$f;
			 	if ($f && isset($a[8]) && ($a[7]==='|' || $occ==1)) $o.=$a[8];

			 }

		}
		return $o;
	}

    public function GetHash(){
     	$res=md5(serialize($this->GetContent()));
    	return 	$res;
    }

    public function GetAsTextFormat(){
  		$content=$this->GetContent();
  		if (!$content) return '';
  		$txt_rec='';
  		foreach ($content as $field=>$value){
  			foreach($value as $occ_value){
  				if ($occ_value)
  					$txt_rec.="#$field: $occ_value\r\n";
  			}
  		}
  		return $txt_rec."*****\r\n";
    }

    public function GetCode(){
    	return ($this->GetField(914,1) ? $this->GetField(914,1) : ($this->GetField(903,1) ? $this->GetField(903,1) :  $this->GetHash()) );
    }

    public function GetLabelsArray(){
    	return array_keys($this->GetContent());
    }

    public function add_from_another_rec($fld_own,$subf_own='',$rec_conv=null,$fld_conv=null,$subf_conv=''){
    	for ($occ_conv=1;($res=$rec_conv->GetSubField($fld_conv,$occ_conv,$subf_conv));$occ_conv++){
    		$this->SetSubField($fld_own,$occ_conv,$subf_own,$res);
    	}
    }

	public function filter($useful_fields,$deleted_subfields){

		//CONTENT TEMP
		$ct=array();
		$content_orig=$this->GetContent();
		foreach ($useful_fields as $uf){
			if (isset($content_orig[$uf]))
				$ct[$uf]=$content_orig[$uf];
		}
			foreach ($deleted_subfields as $label=>$subfield){
				if (isset($ct[$label])){
					foreach($ct[$label] as &$field){
						if(stripos($field,'^'.$subfield)!==FALSE) {
							$field=preg_replace('{(\^'.$subfield.'[^\\^]*)}i','',$field);
						}

					}

			}
		}


		$this->SetContent($ct);
	}

	public function SetFromIrbTXT($irb_txt_rec,$cp='utf'){
		$irb_fields_array=explode("\n",trim($irb_txt_rec));
		foreach ($irb_fields_array as $irb_field_string){
			$temp=explode(':',$irb_field_string,2);

			if (is_array($temp) and count($temp)==2 ){
				$field=trim($temp[0],'# ');
				$value=trim($temp[1]);
				if (is_numeric($field) && $value)
					$this->AddField($field,($cp==='win' ? u::win_utf($value) : $value ));
			}
		}

	}

	public function get_fields_numbers_array(){
		return array_keys($this->GetContent());
	}

}


?>
