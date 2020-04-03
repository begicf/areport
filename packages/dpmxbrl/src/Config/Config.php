<?php

namespace DpmXbrl\Config;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author begicf
 */
class Config
{
    /* Preferencija jezika u upotrebi */

    public static $monetaryItem = 'BAM';

    public static $lang = [
        '0' => "ba",
        '1' => 'bs-Latn-BA',
        '2' => 'en'
    ];
    /* Renderovanje specifikacija */
    public static $confSet = [
        'lab-codes' => 'lab-codes',
        'rend' => 'rend',
        'def' => 'def',
        'pre' => 'pre',
        'tab' => 'tab'
    ];
    /* Koristi se radi poboljsanja performansi kod Modula, nije potrebno sve specifikacije da renderuje */
    public static $moduleSet = [
        'pre' => 'pre',
        'rend' => 'rend',
        'lab-codes' => 'lab-codes'
    ];
    public static $createInstance = [
        'rend' => 'rend',
        'def' => 'def',
    ];
    public static $owner = 'www.fba.ba';

    /* Setuj absolut path na root direktorij */

    public static function publicDir()
    {
        return public_path() . DIRECTORY_SEPARATOR . 'tax' . DIRECTORY_SEPARATOR;
    }

    /* Setuj prefix za ownera */

    public static $prefixOwner = 'fba';

    public static function setLogoPath()
    {

        return public_path() . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'fba_color.png';
    }

    public static function owners()
    {
        return [
            'fba' => [
                'namespace' => 'http://www.fba.ba',
                'officialLocation' => 'http://www.fba.ba/xbrl',
                'prefix' => 'fba',
                'copyright' => '(C) FBA'
            ],
            'eba' => [
                'namespace' => 'http://www.eba.europa.eu/xbrl/crr',
                'officialLocation' => 'http://www.eba.europa.eu/eu/fr/xbrl/crr',
                'prefix' => 'eba',
                'copyright' => '(C) EBA'
            ],
        ];
    }

    public static function tmpPdfDir()
    {
        // Default sys_get_temp_dir();
        return storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;

    }


}