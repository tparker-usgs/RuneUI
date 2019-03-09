<?php
$redis = new Redis();
//$redis->connect('127.0.0.1');
$redis->connect('/run/redis/socket');
$hash = $redis->get('password');

if (!password_verify($_POST['pwd'], $hash)) die();

echo 1;

session_start();
$_SESSION['login'] = 1;
