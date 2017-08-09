<?php

class GlobalSearcher
{

    public static function searchFiles($args)
    {

        list($dir,$subdirs,$terms) = array($args['dir'],$args['subdirs'],$args['terms']);
        $fileArray=array();
        if($subdirs)
        {
            foreach(glob_recursive($dir . "*.php") as $filename)
            {
                $fileArray[] = $filename;
            }
        }else{
            foreach (glob($dir . "*.php") as $filename)
            {
                $fileArray[] = $filename;
            }
        }
        $fileNameHas=array();
        $fileNameLacks=array();
        $contentHas=array();
        $contentLacks=array();
        $fileOrContentHas=array();
        $fileOrContentLacks=array();
        foreach($terms as $term)
        {
            if($term['where']=='filename')
            {
                if($term['invert']){$fileNameLacks[]=$term['search'];}else{$fileNameHas[]=$term['search'];}
            }
            if($term['where']=='contents')
            {
                if($term['invert']){$contentLacks[]=$term['search'];}else{$contentHas[]=$term['search'];}
            }
            if($term['where']=='both')
            {
                if($term['invert']){$fileOrContentLacks[]=$term['search'];}else{$fileOrContentHas[]=$term['search'];}
            }
        }
        return self::searchFileArray($fileArray,$fileNameHas,$fileNameLacks,$contentHas,$contentLacks,$fileOrContentHas,$fileOrContentLacks);
    }

    protected static function hasAll($file,$arr)
    {
        foreach($arr as $searchFor)
        {
            if($searchFor==='')continue;
            if(strpos($file, $searchFor) === false)return false;
        }
        return true;
    }

    protected static function lacksAll($file,$arr)
    {
        foreach($arr as $searchFor)
        {
            if($searchFor==='')continue;
            if(strpos($file, $searchFor) !== false)return false;
        }
        return true;
    }

    protected static function searchFileArray($fileArray,$fileNameHas,$fileNameLacks,$contentHas,$contentLacks,$fileOrContentHas,$fileOrContentLacks)
    {
        $foundArray=array();
        foreach($fileArray as $file)
        {
            if(!self::hasAll($file,$fileNameHas)||!self::lacksAll($file,$fileNameLacks))continue;
            $content = file_get_contents($file);
            if(!self::hasAll($content,$contentHas)||!self::lacksAll($content,$contentLacks))continue;
            if(!(self::hasAll($file,$fileOrContentHas)||self::hasAll($content,$fileOrContentHas)))continue;
            if(!self::lacksAll($file,$fileOrContentLacks)||!self::lacksAll($content,$fileOrContentLacks))continue;
            $foundArray[] = $file;
        }
       return $foundArray;
    }
}