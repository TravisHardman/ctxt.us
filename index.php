<?php
//TODO: Add ability to specify your own context
//TODO: Add ability to specify length of your context
//TODO: Add some basic metrics
//TODO: Add some "about" information to get some movabls PR
function get_context($url) {
    $url = substr($url,strpos($url,'://')+3);
    if (substr($url,0,4) == 'www.')
        $url = substr($url,4);
    if (strpos($url,'/') !== false)
        $url = substr($url,0,strpos($url,'/'));
    if (strrpos($url,'.') === false)
        throw new Exception ('Invalid URL');
    else
        $url = substr($url,0,strrpos($url,'.'));
    if (strlen($url) > 9)
        return substr($url,0,9);
    else
        return $url;
}
function encode($number) {
    $chars = str_split('XT7SAGzwDy8ORKNIhWfca1pmlsgVeZM9Fn6Y3b4joCBr5qtJ20kduEPivxLQUH');
    if ($number/62 < 1)
        return $chars[$number%62];
    else {
        $first = encode($number/62);
        return $first.$chars[$number%62];
    }
}
function decode($number) {
    $chars = str_split('XT7SAGzwDy8ORKNIhWfca1pmlsgVeZM9Fn6Y3b4joCBr5qtJ20kduEPivxLQUH');
    $code = array_reverse(str_split($number));
    $multiplier = 1;
    $total = 0;
    foreach ($code as $digit) {
        $digit = array_search($digit,$chars);
        $total += $multiplier * $digit;
        $multiplier *= 62;
    }
    return $total;
}

//Set redirect
if ($_SERVER['REQUEST_URI'] != '/') {
    $url = explode('/',$_SERVER['REQUEST_URI']);
    $number = decode($url[2]);
    $con = mysql_connect('localhost','root','Arbor00');
    mysql_select_db('context');
    $query = mysql_query("SELECT `long` FROM urls WHERE context = '{$url[1]}' AND increment = $number");
    $result = mysql_fetch_array($query);
    mysql_free_result($query);
    if (empty($result))
        $error = "URL Not Found";
    else {
        header('Location: '.$result['long'],302);
        die();
    }
}

//Create new
if (!empty($_POST['long'])) {
    try {
        if (strpos($_POST['long'],'://') === false)
            $_POST['long'] = 'http://'.$_POST['long'];
        $con = mysql_connect('localhost','root','Arbor00');
        mysql_select_db('context');
        if (empty($_POST['context'])) {
            $context = get_context($_POST['long']);
            $context = mysql_real_escape_string($context);
        }
        else {
            $context = urlencode($_POST['context']);
            $context = mysql_real_escape_string($context);
        }
        $_POST['long'] = mysql_real_escape_string($_POST['long']);
        $query = mysql_query("SELECT context,increment FROM urls WHERE `long` = '{$_POST['long']}' AND context = '$context'");
        $result = mysql_fetch_array($query);
        mysql_free_result($query);
        if (empty($result)) {
            $query = mysql_query("SELECT MAX(increment) AS max FROM urls WHERE context = '$context'");
            $result = mysql_fetch_array($query);
            mysql_free_result($query);
            if (!empty($result['max']))
                $number = ++$result['max'];
            else
                $number = 1;
            mysql_query("INSERT INTO urls (`increment`,`context`,`long`) VALUES ($number,'$context','{$_POST['long']}')");
            $newurl = "http://ctxt.us/$context/".encode($number);
        }
        else {
            $newurl = "http://ctxt.us/{$result['context']}/".encode($result['increment']);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
elseif ($_POST) {
    $error = "Please Enter a URL";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>ctxt.us - url shortener with context</title>
        <style type="text/css">
            body {font-family:Arial, Helvetica, sans-serif;font-size:18px;color:#333333;}
            input#long {color:#666666;width:900px;padding:2px;font-size:18px;}
            input#context {color:#666666;width:300px;padding:2px;font-size:18px;}
            #content {margin:auto;width:980px;}
            #form {width:900px;padding:10px 40px 20px;background-color:#FFFFAA;line-height:40px;font-weight:bold;}
            #error {width:900px;padding:10px 40px;background-color:#AA2222;color:#FFFFFF;margin-bottom:20px;}
            #submit {float:right;padding:0px 10px;background-color:#33AA33;color:#FFFFFF;margin-left:10px;cursor:pointer;}
            #result {width:900px;padding:10px 40px 20px;background-color:#FFFFAA;line-height:30px;font-weight:bold;margin-top:20px;}
        </style>
    </head>
    <body onload="document.getElementById('long').focus();">
        <div id="content">
            <h1>ctxt.us - url shortener with context</h1>
            Because people like to know what they're clicking.<br /><br />
            <? if (isset($error)): ?>
            <div id="error">
                <?=$error ?>
            </div>
            <? endif; ?>
            <form action="/" method="post" name="urlform">
                <div id="form">
                    <label for="long">Enter your long URL here:</label><br />
                    <input type="text" name="long" id="long" /><br />
                    <label for="context">Enter your own context (optional):</label><br />
                    http://ctxt.us/ <input type="text" name="context" id="context" /> /...
                    <a id="submit" onclick="document.urlform.submit();">Shorten</a>
                </div>
            </form>
            <? if (isset($newurl)): ?>
            <div id="result">
                Your shortened URL is:<br />
                <a href="<?=$newurl ?>"><?=$newurl ?></a>
            </div>
            <? endif; ?>
        </div>
    </body>
</html>
