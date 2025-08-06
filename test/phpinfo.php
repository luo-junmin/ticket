<?php
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "秒";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "秒";
$val = array(100);
echo "<br>--- ".$val[0];
$userId = 200;
$val = [$userId];
echo "<br>--- ".$_SERVER['DOCUMENT_ROOT'];
