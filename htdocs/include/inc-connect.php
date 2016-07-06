<?php
if ($cfg['db_type'] == 'mysql') {
 $db = new PDO("mysql:host=".$cfg['db_address'].";dbname=".$cfg['db_name'].";charset=utf8", $cfg['db_user'], $cfg['db_password']);
 $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
 $db->exec("SET NAMES utf8");
 $db->exec("SET SESSION group_concat_max_len=5045"); // increase size of group_concat field
} else {
 $db = new PDO("sqlite:".$cfg['db_name'].".sqlite");
}
?>
