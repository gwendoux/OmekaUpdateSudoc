<?php
    $host = "#";
    $db = "#";
    $user = "#";
    $pwd = "#";
    $link = mysql_connect($host, $user, $pwd);

    if (!$link)
    {
        die('Could not connect: ' . mysql_error());
    }
    $db_selected = mysql_select_db($db, $link);
    if (!$db_selected) {
        die('Can\'t use foo : ' . mysql_error());
    }
    mysql_query("SET NAMES utf8");

    function SQL($req, $display_error = 1)
    {
        $result = mysql_query($req);
        if (!$result)
        {
            if ($display_error)
            {
                die('Invalid query: ' . mysql_error());
            }
        }
        return $result;
    }
?>
