<?php
$redis = new Redis();
$redis->connect('127.0.0.1');
$hash = $redis->get('password');

if (!password_verify($_POST['pwd'], $hash)) die();

$option = array('cost' => 12);
$hash = password_hash($_POST['pwdnew'], PASSWORD_BCRYPT, $option);

$set = $redis->set('password', $hash);

echo $set;
