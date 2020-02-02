<?php

// direct output bypass template system
$tplfile = 0;
runelog("\n--------------------- artist info (start) ---------------------");
// turn off output buffering
ob_implicit_flush(0);

ob_clean();
flush();

// --------------------- All Players ---------------------
if (FALSE === $redis->Get('artist-info')) {
    $redis->Set('artist-info', 0);
}
if ($redis->Get('artist-info')) {
    echo sysCmd("sh /var/www/command/artist_info.sh")[1];
}
runelog("\n--------------------- artist info (end) ---------------------");