<?php


namespace DpmXbrl\Library;


use DpmXbrl\Config\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveCallbackFilterIterator;

/**
 * Class Directory
 * @category
 * @package DpmXbrl\Library
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 08/04/2020
 * Time: 11:37
 */
class Directory
{

    /**
     * @param $path
     * @param array $string
     * @param null $return
     * @return array|null
     */
    public static function getPath($path, $string = array(), $return = NULL)
    {


        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        $ext = array('xsd');
        $content = NULL;
        $dir = array();

        foreach ($rii as $file) :

//            if ($file->isDir()) {
//                continue;
//            }

            $content = $file->getPathname();

            foreach ($string as $key => $str):

                if (strpos($content, $str) !== false) :

                    /* @var $tmpPath type pathifno */

                    if (in_array($file->getExtension(), $ext)):
                        if ($return == NULL):
                            $dir[$key][] = $content;
                        else:
                            return $content;
                        endif;
                    endif;
                endif;
            endforeach;

            //  }
        endforeach;
        return $dir;
    }

    /**
     * Pretraga direktorija rekurzivno glob metoda
     * @param $pattern
     * @return string|null
     */
    public static function searchFileGlob($pattern, $flags = 0): ?array
    {

        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {

            $files = array_merge($files, self::searchFileGlob($dir . '/' . basename($pattern), $flags));
        }
        return $files;

    }

    /**
     * Pretraga direktorija rekurzivno Iterator
     * @param $directory
     * @param $pattern
     * @return string|null
     */
    public static function searchFile($directory, $pattern, $maxDepth = 6): ?array
    {


        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        $iterator->setMaxDepth($maxDepth);
        foreach ($iterator as $file) {

            if (strpos($file, $pattern) !== false) {
                $files[] = $file;
            }
        }

        return $files;

    }


    /**
     * Pretraga direktorija rekurzivno Iterator exclude some directory
     * @param $directory
     * @param $pattern
     * @param int $maxDepth
     * @return string|null
     */
    public static function searchFileExclude($directory, $pattern, $maxDepth = 6, $exclude = ['www.xbrl.org', 'www.eurofiling.info', 'ext', 'dict', 'func', 'tab', 'val']): ?array
    {

        $files = [];

        $filter = function ($file, $key, $iterator) use ($exclude) {
            if ($iterator->hasChildren() && !in_array($file->getFilename(), $exclude)) {
                return true;
            }
            return $file->isFile();
        };

        $innerIterator = new RecursiveDirectoryIterator(
            $directory,
        );
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator($innerIterator, $filter),
        );
        $iterator->setMaxDepth($maxDepth);


        foreach ($iterator as $pathname => $file) {

            if (strpos($file->getFilename(), $pattern) !== false) {
                $files[] = $file;

            }
        }
        return $files;
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
