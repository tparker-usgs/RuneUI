<?php

// direct output bypass template system
$tplfile = 0;
runelog("\n--------------------- lyric (start) ---------------------");
// turn off output buffering
ob_implicit_flush(0);

ob_clean();
flush();

// --------------------- All players ---------------------
if (!$redis->Exists('lyric')) {
    $redis->Set('lyric', 0);
    echo '';
} else if ($redis->Get('lyric')) {
    //echo str_replace ( "</br>" , "\n" , sysCmd("sh /var/www/command/lyric.sh")[2]);
    echo sysCmd("sh /var/www/command/lyric.sh")[2];
} else {
    echo '';
}
runelog("\n--------------------- lyric (end) ---------------------");