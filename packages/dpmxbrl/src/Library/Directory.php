<?php


namespace DpmXbrl\Library;


use DpmXbrl\Config\Config;

/**
 *
 *
 * @author begicf
 */
class Directory
{

    /**
     * @param $path
     * @param array $string
     * @param null $return
     * @return array|null
     */
    public static function getPath($path, $string = array(), $return = NULL): ?array
    {


        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $ext = array('xsd');
        $content = NULL;
        $dir = array();

        foreach ($rii as $file) :

            if ($file->isDir()) {
                continue;
            }
            $content = $file->getPathname();
            $tmpPath = pathinfo($content);

            foreach ($string as $key => $str):

                if (strpos($content, $str) !== false) :

                    /* @var $tmpPath type pathifno */

                    if (in_array($tmpPath['extension'], $ext)):
                        if ($return == NULL):
                            $dir[$key][] = $content;
                        else:
                            return $content;
                        endif;
                    endif;
                endif;
            endforeach;
        endforeach;
        return $dir;
    }

    /**
     * @param $file_path
     * @return string
     */
    public static function getRootName($file_path): string
    {

        return Format::getBeforeSpecChar(substr($file_path, strlen(Config::publicDir())), '/');

    }

    /**
     * @param $file_path
     * @return string
     */

    public static function getOwnerAbsolutePath($file_path): string
    {

        return Config::publicDir() . self::getRootName($file_path) . DIRECTORY_SEPARATOR . Config::$owner;
    }

    /**
     * @param $file_path
     * @return string
     */
    public static function getOwnerUrl($file_path): ?string
    {
        return self::getStringBetween($file_path, self::getRootName($file_path) . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);

    }

    /**
     * @param $file_path
     * @return string
     */
    public static function getLastPathDirName($file_path): string
    {
        $_info = pathinfo($file_path);
        $_dir = explode(DIRECTORY_SEPARATOR, $_info['dirname']);
        return end($_dir);

    }

    /**
     * @param $string
     * @param $start
     * @param $end
     * @return string
     */
    public static function getStringBetween($string, $start, $end): string
    {

        $pos = stripos($string, $start);
        $str = substr($string, $pos);
        $str_two = substr($str, strlen($start));
        $second_pos = stripos($str_two, $end);
        $str_three = substr($str_two, 0, $second_pos);
        $unit = trim($str_three);

        return $unit;


    }
}