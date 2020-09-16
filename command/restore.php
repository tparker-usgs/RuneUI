#!/usr/bin/php
<?php
$file = $_FILES["filebackup"];
$filename = $file["name"];
$filetmp = $file["tmp_name"];
$filedest = "/srv/http/tmp/$filename";
// clear the cache otherwise filesize() returns incorrect values
clearstatcache();
$filesize = filesize($filetmp);

if ($filesize === 0) die("File upload error !");

exec("rm -f /srv/http/tmp/backup_*");
if (! move_uploaded_file($filetmp, $filedest)) {
    die("File move error !");
} else {
    echo "Restore starting - automatic reboot will follow";
}

$restore = exec("sudo /srv/http/command/restore.sh $filedest; echo $?");

if ($restore == 0) {
    // this will never be displayed reboot initiated from restore.sh
    echo "Restored successfully. Reboot recommended";
} else {
    // this will be displayed if an error occurs
    echo "Restore failed !";
}

