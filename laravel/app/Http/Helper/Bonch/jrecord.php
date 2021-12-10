<?php

namespace App\Http\Helper\Bonch;

class jrecord extends Record
{
    public $bl_id;
    public $formating = array();
    public $j_code = '';
    // На данный момент ситуации, когда нужно по разному отображать локальные записи записи из удалённых источников являются достаточно редкими:
    //отображение экземпляров, электронных версий, переформатирование.
    // Менять из-за них структуру большого смысла нет.
    // ЕСТЬ! При переформатировании, например записей из ИРБИС 64, где не установлены нужные форматы. В этом случае обращаться каждый раз к ST -- бред.
    public $local = true;


    public function __construct($rec = null, $bl_id = 0)
    {
        $this->bl_id = $bl_id;
        if ($rec) {
            $this->Content = $rec->Content;
            $this->mfn = $rec->mfn;
            $this->Status = $rec->Status;
            $this->RecVersion = $rec->RecVersion;
        }
    }

    public function IsLocal()
    {
        if (property_exists($this, 'local'))
            return $this->local;
        else
            return true;
    }

    public function SetLocal($status = false)
    {
        if (property_exists($this, 'local'))
            $this->local = $status;
    }

    public function GetRec_id()
    {
        return $this->j_code;
    }


    public function GetBl_id()
    {
        return $this->bl_id;
    }

    public function SetRec_id($jcode)
    {
        $this->j_code = $jcode;
    }

    // Сохраняем BNS Только для совместимости
    public function BuildRec_id($lib_id, $bns = '')
    {
        $rec_id = crc32($lib_id . $this->GetBl_id() . $this->GetCode());
        $this->j_code = $rec_id;
        return $rec_id;
    }


    public function UpdateFromCachedRec($rec_cache)
    {
        $this->SetProfileAsArray($rec_cache->GetProfileAsArray());
        $this->SetRec_id($rec_cache->GetRec_id());
    }


    public function GetFormatsAsArray($crc_for_string = false)
    {
        $res = array();
        if ($this->formating) {
            foreach ($this->formating as $par) {
                if (strpos($par['format'], '@') !== false) {
                    $res[] = $par['format'];
                } else {
                    $res[] = $crc_for_string ? crc32($par['format']) : $par['format'];
                }
            }
        }
        sort($res);
        return $res;
    }


    public function GetFormatTypesAsArray()
    {
        $res = array();
        if ($this->formating) {
            foreach ($this->formating as $type => $par) {
                $res[] = $type;
            }
        }
        sort($res);
        return $res;
    }

    public function GetFullBO()
    {
        return (isset($this->formating['full']['value']) ? $this->formating['full']['value'] : '');
    }

    public function GetBriefBO()
    {
        return (isset($this->formating['brief']['value']) ? $this->formating['brief']['value'] : '');
    }

    public function GetFormValue($form_name)
    {
        return (isset($this->formating[$form_name]['value']) ? $this->formating[$form_name]['value'] : '');
    }

    public function GetSortKeysAsAssocArray()
    {
        $res = array();
        foreach ($this->formating as $key => $value) {
            if (isset($value['type']) and $value['type'] === 'sort')
                $res[$key] = $value['value'];
        }
        return $res;
    }

    public function SetProfileAsArray($form_array)
    {
        $this->formating = $form_array;
    }

    public function GetProfileAsArray()
    {
        return $this->formating;
    }

    public function SetFormValues($name, $value = '', $type = '', $format = '')
    {
        $this->formating[$name]['value'] = $value;
        if ($type)
            $this->formating[$name]['type'] = $type;
        if ($format)
            $this->formating[$name]['format'] = $format;
    }

    public function SetSortKey($key_name, $key_value = '')
    {
        $this->formating[$key_name]['value'] = $key_value;
        $this->formating[$key_name]['type'] = 'sort';
    }

    public function SetBO($bo_name, $bo_value = '')
    {
        $this->formating[$bo_format]['value'] = $bo_value;
        $this->formating[$bo_format]['type'] = 'bo';
    }

    public function Set_bl_id($value)
    {
        $this->bl_id = $value;
    }

    public function Get_bl_id()
    {
        return (isset($this->bl_id)) ? $this->bl_id : 0;
    }

    public function GetFormat($key)
    {
        return (isset($this->formating[$key]['format'])) ? $this->formating[$key]['format'] : '';
    }

    public function GetFormatValue($key)
    {
        return (isset($this->formating[$key]['value'])) ? $this->formating[$key]['value'] : '';
    }

    public function GetFormating($key)
    {
        return (isset($this->formating[$key]['value'])) ? $this->formating[$key]['value'] : '';
    }

    public function SetFormat($key, $format)
    {
        $this->formating[$key]['format'] = $format;

        if (empty($this->formating[$key]['value']))
            $this->formating[$key]['value'] = '';
    }

    public function SetFormating($formating_array)
    {
        $this->formating = $formating_array;
    }

    public function UpdateProfile($special_profile)
    {
        foreach ($special_profile as $special_key => $special_value) {
            if ($this->GetFormat($special_key) !== $special_value['format']) {
                if ($special_value['type'] === 'sort') {
                    $this->formating[$special_key]['type'] = 'sort';
                    $this->formating[$special_key]['format'] = $special_value['format'];
                } else {
                    $this->formating[$special_key]['type'] = 'bo';
                    $this->formating[$special_key]['format'] = $special_value['format'];

                }
            }
        }
    }


}

?>
