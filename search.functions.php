<?php

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
