<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FactTable extends Model
{
    protected $table = 'fact_table';
    protected $fillable = ['fact_header_id', 'cr_code', 'string_value', 'cr_sheet_code', 'metric', 'xbrl_context_key', 'xbrl_context_key_raw', 'user_id'];

    /**
     * @param $req
     */
    public static function storeInstance($req, $factheader_id, $sheet)
    {


        parse_str($req, $res);

        if (isset($sheet)):
            $sheet = (json_decode($sheet, true));
            $cr_sheet = $sheet['sheet'];
            unset($sheet['sheet'],$sheet['metric'],$sheet['order']);
        endif;


        foreach ($res as $key => $row):

            if (isset($row['value']) && !empty($row['value'])):

                $arr = json_decode($row['dim'], true);

                if (isset($arr['metric'])):
                    $metric = $arr['metric'];
                    unset($arr['metric']);
                else:
                    $metric = NULL;
                endif;

                if (isset($sheet)):
                    $arr = array_merge($arr, $sheet);
                endif;

                $raw = $arr;

                if (isset($arr['typedMember'])):
                    unset($arr['typedMember']);
                    $dim = self::makeString($arr);
                else:

                    $dim = self::makeString($arr);
                endif;

                self::updateOrCreate([
                    'fact_header_id' => $factheader_id,
                    'cr_code' => $key,
                    'cr_sheet_code' => $cr_sheet ?? '000'

                ], [
                    'fact_header_id' => $factheader_id,
                    'cr_code' => $key,
                    'cr_sheet_code' => $cr_sheet ?? '000',
                    'xbrl_context_key' => $dim,
                    'xbrl_context_key_raw' => json_encode($raw),
                    'string_value' => $row['value'],
                    'metric' => $metric,
                    'user_id' => Auth::id()
                ]);


            endif;
        endforeach;


    }

    /**
     * @param $arr
     * @return string
     */
    private static function makeString($arr)
    {


        if (isset($arr['typ'])):
            unset($arr['typ']);
        endif;

        $tmp = array();
        foreach ($arr as $key => $value) {
            $val = substr(strstr($value, ':x'), strlen(':x'));
            $keyDomen = strstr($value, ':x', TRUE);

            $key = substr(strstr($key, ':'), strlen(':'));

            if ($val != 0):
                $tmp[$key] = $keyDomen . ':x' . $val;
            elseif ($val === FALSE):
                $tmp[$key] = $value;
            endif;
        }

        ksort($tmp);

        $string = NULL;
        foreach ($tmp as $key => $str):

            $string .= $key . '=' . $str . ',';

        endforeach;

        return rtrim($string, ',');
    }
}
