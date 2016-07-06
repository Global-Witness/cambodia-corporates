<?php

session_start();
header('Content-Type: text/html; charset=utf-8');
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
date_default_timezone_set('Asia/Phnom_Penh'); 

/** Check if user has submitted acceptance **/
if( isset($_POST['acceptance']) && !empty($_POST['acceptance']) && $_POST['acceptance'] === 'accepted' ) { 
        $_SESSION['accepted_'] = 'YES';
        $referral = (!empty($_POST['referral']) ? urldecode($_POST['referral']) : '/' );
        header('Location: ' . $referral );
}
/** Check if it is validated or head to disclaimer **/
elseif( (!isset($_SESSION['accepted_']) || $_SESSION['accepted_'] !== 'YES') && ($_GET['url'] !== 'disclaimer') ) {
    /** get url to enque and let people move to desired destination **/    
    $uri = $_SERVER['REQUEST_URI'];   
    header('Location: /disclaimer?u='.urlencode($uri));
}

   
mb_internal_encoding('utf8');
ini_set('max_execution_time', 120);


/** DATABASE CONNECTION **/
$cfg['db_type']     = 'mysql'; // { sqlite, mysql }
$cfg['db_name']     = 'DATABASE';
$cfg['db_address']  = 'HOST';
$cfg['db_user']     = 'USERNAME';
$cfg['db_password'] = 'PASSWORD';

/** Switch Development/Production **/
define('INDEV', true); 
define('DBTYPE', $cfg['db_type']); // for use in internal functions

if(INDEV) { 
    /** Don't cache things **/
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

?>