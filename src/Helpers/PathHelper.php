<?php 

namespace Jemer\Tiny\Helpers;

class PathHelper
{
    public static function BuildPath(array $args, ?string $separator = null) : string 
    { 
        $sep = $separator ?? DIRECTORY_SEPARATOR; 
        return implode($sep, $args); 
    } 

    public static function Stringify(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9]+/', '-', $title);
        return trim($title, '-');
    }

    public static function GetFileNameWithOutExtension(string $path) : string 
    { 
        return pathinfo($path, PATHINFO_FILENAME); 
    } 

    public static function GetFileName(string $path) : string 
    { 
        return pathinfo($path, PATHINFO_BASENAME); 
    } 

    public static function GetExtension(string $path) : string 
    { 
        return pathinfo($path, PATHINFO_EXTENSION); 
    } 

    public static function GetParentDirectory(string $path) : string 
    { 
        return pathinfo($path, PATHINFO_DIRNAME); 
    } 

    public static function GetFileNames(string $directory) : array 
    { 
        if (!is_dir($directory)) return [];
        // array_values resets array keys back to 0, 1, 2...
        return array_values(array_diff(scandir($directory), ['.', '..'])); 
    } 

    public static function GetFiles(string $directory) : array 
    { 
        if (!is_dir($directory)) return [];
        $arr = []; 
        $files = array_diff(scandir($directory), ['.', '..']); 
        foreach ($files as $file) { 
            $path = self::BuildPath([$directory, $file]); 
            if (!is_dir($path)) { 
                $arr[] = $path; 
            } 
        } 
        return $arr; 
    } 

    public static function GetDirectoryNames(string $directory) : array 
    { 
        if (!is_dir($directory)) return [];
        $arr = []; 
        $files = array_diff(scandir($directory), ['.', '..']); 
        foreach ($files as $file) { 
            // FIXED: Validating against the full path instead of just the name
            $path = self::BuildPath([$directory, $file]);
            if (is_dir($path)) { 
                $arr[] = $file; 
            } 
        } 
        return $arr; 
    } 

    public static function GetDirectories(string $directory) : array 
    { 
        if (!is_dir($directory)) return [];
        $arr = []; 
        $files = array_diff(scandir($directory), ['.', '..']); 
        foreach ($files as $file) { 
            $path = self::BuildPath([$directory, $file]); 
            if (is_dir($path)) { 
                $arr[] = $path; 
            } 
        } 
        return $arr; 
    } 
}


?>