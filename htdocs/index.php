<?php
    /** parse and filter user-submitted data **/
    function safe($string) {
     return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    $page_title = 'Cambodia Corporates';
    $page_title_key = ( isset($_GET['type']) && !empty($_GET['type']) ? safe($_GET['type'] == 'address' ? $_GET['house'] . ' ' .$_GET['street'] : ($_GET['type'] == 'ids' ? $_GET['id'] : $_GET[$_GET['type']])) : '// ');
    $page_title = (!empty($page_title_key) ? $page_title_key . ' - ' . $page_title : $page_title);
    
    /** Available routes **/
    $routes = array(
                    'home',
                    'contact', 
                    'disclaimer',
                    'project',
                    'search',
                    'terms-of-use'
                   );
    
    include($_SERVER['DOCUMENT_ROOT'] . '/include/inc-config.php');
    include(ROOT . '/include/inc-connect.php');
    include(ROOT . '/include/functions.php'); 
    include(ROOT . '/include/inc-head.php'); 
    
        
    $page = (!empty($_GET['url']) ? safe($_GET['url']) : 'home');
    #echo "[$page]<br>";
    #echo ROOT . '/page/' . $page . '.php' . "<br>";

    if(file_exists(ROOT . '/page/' . $page . '.php') && in_array($page, $routes) ) { 
        if($page !== 'disclaimer') { 
            include(ROOT . '/include/inc-header.php');
            include(ROOT . '/include/inc-search.php');
        }

        include(ROOT . '/page/' . $page . '.php');
    }  

    else { 
        echo "404.";   
    }

    if($page !== 'disclaimer') { 
        include(ROOT . '/include/inc-footer.php');  
    }
    
    
?>
    
