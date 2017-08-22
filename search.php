<?php
session_start();
session_write_close();


error_reporting(E_ALL);
ini_set('display_errors',1);

// search.functions.php
function handlePostRedirectGet()
{
    if( !empty($_POST) )
    {
        session_start();
        $_SESSION['prg_post']=$_POST;
        session_write_close();
        header("HTTP/1.1 303 See Other");
        header("Location: {$_SERVER['REQUEST_URI']}");
        exit;
    }
    if(isset($_SESSION['prg_post']))
    {
        session_start();
        $_POST = $_SESSION['prg_post'];
        unset($_SESSION['prg_post']);
        session_write_close();
    }
}

function getPOST($paramName, $defaultValue = null)
{
    return isset($_POST[$paramName]) ? $_POST[$paramName] : $defaultValue;
}

function print_r_html($obj)
{
    echo '<pre>'.htmlentities(print_r($obj,true)).'</pre>';
}

function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function selectOptions($selectElement,$selectArray=array())
{
    $selectArray=(array)$selectArray;
    $dom = new DOMDocument();
    $dom->loadHTML('<!doctype html><html><head></head><body>'.$selectElement.'</body></html>');
    $body=$dom->getElementsByTagName("body")->item(0);
    $select=$body->getElementsByTagName("select")->item(0);
    $options=$select->getElementsByTagName("option");
    foreach($options as $option)
    {
        if(in_array($option->getAttribute('value'),$selectArray))$option->setAttribute('selected','selected');
    }
    return $dom->saveXML($select);
}
// search.functions.php

handlePostRedirectGet();


$p = empty($_POST) ? NULL : $_POST;
if($p !== NULL)
{
    if(!is_array($p))$p=array();
    $p['fields'] = isset($p['fields']) ? (int)$p['fields'] : 1;
    if($p['fields'] < 1)
    {
        $p['fields'] = 1;
    } elseif ($p['fields'] > 1000) {
        $p['fields'] = 1000;
    } elseif( isset($p['addField']) ) {
        $p['fields']++;
    }
}

$fields = isset($p['fields']) ? (int)$p['fields'] : 1;

?><!doctype html>
<html>
<head>
<title>File Contents Search</title>
<meta name="author" value="Ultimater at gmail dot com" />
<meta name="version" value="1.0.1" />
<style type="text/css">
input{background-color:#fff;color:black}
input.searchtext{background-color:#eee:color:blue;border:1px solid blue;}
</style>
</head>
<body>
<form action="<?= htmlentities($_SERVER['REQUEST_URI']) ?>" method="POST">
Search Directory: <input type="text" name="searchDirectory" size="100" value="<?= htmlentities(isset($_POST['searchDirectory'])?$_POST['searchDirectory']:__DIR__) ?>"><br>
<input type="hidden" name="action" value="search">
<input type="hidden" name="fields" value="<?= $fields ?>">

<label><input type="checkbox" name="includesubdirs" value="1"<?= (empty($p['includesubdirs'])&&!empty($p))?'':' checked="checked"' ?> /> Include Subdirectories</label>
<hr />

<?php

$toOutput = array();
for($i=0;$i<$fields;$i++)
{
    ob_start();

    echo selectOptions('<select name="where[]">
                    <option value="both">Filename and contents</option>
                    <option value="filename">Filename only</option>
                    <option value="contents">Contents only</option>
                    </select>', isset($p['where'][$i]) ? $p['where'][$i] : array());

    echo selectOptions('<select name="invertSearch[]">
                        <option value="0">Contain(s)</option>
                        <option value="1">Doesn\'t Contain</option>
                        </select>', isset($p['invertSearch'][$i]) ? $p['invertSearch'][$i] : array());

    printf('<input type="text" placeholder="Search text" class="searchtext" name="searchtext[]" value="%s">',
        htmlentities(isset($p['searchtext'][$i]) ? $p['searchtext'][$i] : '')
    );


    $toOutput[] = ob_get_clean();
}


    echo implode("<hr />\n", $toOutput);
?>
<hr />
<input type="submit" name="addField" value="Add another field" />
<input type="submit" name="search" value="Search" />
</form>
<?php
if( $p !== NULL )
{
    $terms=array();
    for($i=0;$i<$fields;$i++)
    {
        $term=array();
        if(isset($p['invertSearch'][$i])&&$p['invertSearch'][$i]=='1'){$term['invert']=true;}else{$term['invert']=false;}
        if(isset($p['searchtext'][$i])){$term['search']=(string)$p['searchtext'][$i];}else{$term['search']='';}
        if(isset($p['where'][$i])){$term['where']=($p['where'][$i]=='contents'?'contents':($p['where'][$i]=='filename'?'filename':'both'));}
        else{$term['where']='both';}
        $terms[]=$term;
    }

    $searchDir=realpath(dirname(dirname(__FILE__)));
    $searchArguments=array(
        'dir'=>realpath($_POST['searchDirectory']).'/',
        'subdirs'=>!empty($p['includesubdirs'])?true:false,
        'terms'=>$terms
    );

    //search.glob-searcher.class.php
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
    //search.glob-searcher.class.php
    $matched_files = GlobalSearcher::searchFiles($searchArguments);

    echo sprintf('<hr /><h3>%s %s<br /></h3>',sizeof($matched_files),$matched_files==1?'Match':'Matches');
    print_r_html($matched_files);
}
?>
</body>
</html>