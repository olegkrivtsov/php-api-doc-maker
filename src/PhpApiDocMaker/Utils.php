<?php
namespace PhpApiDocMaker;

class Utils
{
    /**
     * Recursively scans directory for files and subdirectories. 
     */
    public static function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = $dir.DIRECTORY_SEPARATOR.$value;
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                self::getDirContents($path, $results);                
            }
        }

        return $results;
    }
}

