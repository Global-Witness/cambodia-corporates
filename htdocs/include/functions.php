<?php 

// function to sort company names in alphabetical order
function lines($a,$b) {
 if ($a[0] == $b[0]) return 0;
 return $a[0] < $b[0] ?  -1 : 1;
}

/** sort alphabetical by company name **/
function company_name_sort($a, $b) {
    return strcmp($a["company"], $b["company"]);
}

/** parse ids to find out collection date **/
function checkId($string){
 $parsed = array('R' => array(), 'A' => array());
 if(!is_array($string)){ 
  $parts = explode(',', $string);
 }
 else {
  $parts = $string;
 }
 
 //dbga($parts);
 foreach($parts as $part) {
  if($part[0] == 'R'){
   $parsed['R'][] = substr($part, 1);
  }
  else {
   $parsed['A'][] = substr($part, 1);
  }
 }
 //dbga($parsed);
 return $parsed;
}


// function to sort name search result based on fuzziness (i.e. from closest to farthest match)
function bits($a, $b) {
 if ($a[0] == $b[0]) {
  if ($a[1] == $b[1]) return 0;
  return ($a[1] > $b[1]) ? -1 : 1;
 }
 return ($a[0] < $b[0]) ? -1 : 1;
}

/** helper function to print out arrays nicely **/
function dbga($array){
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

function printFlag($nationality){
    $recon = array(
        'United-States' => 'American',
        'Cambodia' => 'Cambodian',
        'China' => 'Chinese',
        'France' => 'French',
        'Japan' => 'Japanese',
        'South-Korea' => 'Korean',
        'Malaysia' => 'Malaysian',
        'Singapore' => 'Singaporean',
        'Thailand' => 'Thai',
        'Vietnam' => 'Vietnamese'
    );
    
    if(in_array($nationality, $recon)) { 
        $flags = array_flip($recon);
        echo '<img class="flag flag-icon" src="/assets/img/flags/' . $flags[$nationality] . '.png" alt="' . $nationality . '" data-toggle="tooltip" data-placement="top" title="' . $nationality . '">';
    }
    else {
        $nationality = (empty($nationality) ? 'Unknown' : $nationality);
        echo '<i class="fa fa-globe" data-toggle="tooltip" data-placement="top" title="' . $nationality . '"><span class="sr-only">' . $nationality . ' </span></i>';
    }
}

/** Name/Nationality search **/
function nameSearch($db) {
    $results = array();
    $amendments = array(); 
    
    $_name = isset($_GET['individual']) ? trim(safe($_GET['individual'])) : '';
    $_nationality = isset($_GET['nationality']) ? safe(trim($_GET['nationality'])) : '';
    $_threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 2;
    
    $companies_where = '';
    $amendments_where = '';
    $check = '';
    
    $companies_ids = array();
    $amendments_ids = array();
    
    if ($_name != '') {
        
        $check = mb_convert_case($_name, MB_CASE_TITLE);

        $companies_ids = array();
        $amendments_ids = array();
        $normalized_name = strtolower($_name);    
        if($_threshold == 'parts') {
		   
            $placeholder_name_spaces = '% '.($normalized_name).' %';
			$placeholder_name_nospaces = '%'.($normalized_name).'%';
			$placeholder_name_spaces_front = '% '.($normalized_name).'%';
			$placeholder_name_spaces_back = '%'.($normalized_name).' %';
			
			
            $sql =
			"SELECT id, chairman, agent FROM companies WHERE
				LOWER(chairman) LIKE :spaces OR
				LOWER(agent) LIKE :frontspaces OR
				LOWER(chairman) LIKE :nospaces OR
				LOWER(agent) LIKE :frontspaces2";
				
			$stmt = $db->prepare($sql);
			$stmt->bindParam(':spaces', $placeholder_name_spaces, PDO::PARAM_STR);
			$stmt->bindParam(':frontspaces', $placeholder_name_spaces_front, PDO::PARAM_STR);
			$stmt->bindParam(':frontspaces2', $placeholder_name_spaces_front, PDO::PARAM_STR);
			$stmt->bindParam(':nospaces', $placeholder_name_nospaces, PDO::PARAM_STR);
			
			$query = $stmt->execute();
			
			if(!$query){
			 dbga($stmt->errorInfo());
			}
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach($rows as $row){
			    $companies_ids[] = $row['id'];
			}
			
			$sql = "SELECT id, chairman FROM amendments WHERE
					LOWER(chairman) LIKE :spaces OR
					LOWER(chairman) LIKE :nospaces";
					
            $stmt = $db->prepare($sql);
			$stmt->bindParam(':spaces', $placeholder_name_spaces, PDO::PARAM_STR);
			$stmt->bindParam(':nospaces', $placeholder_name_nospaces, PDO::PARAM_STR);
			
			$query = $stmt->execute();
			
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $amendments_ids[] = $row['id'];
            }
                  
                  
        }
        else { // Assuming a threshold has been picked , let's process data with levenshtein distance
            $sql = "SELECT id, chairman, agent FROM companies";
            $result = $db->query($sql);
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (levenshtein($check,$row['chairman']) <= $_threshold || ($row['agent'] != '' & levenshtein($check,$row['agent']) < $_threshold)) $companies_ids[] = $row['id'];
            }
            
            $result = $db->query("SELECT id, chairman FROM amendments");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (levenshtein($check,$row['chairman']) <= $_threshold) $amendments_ids[] = $row['id'];
            }
            
        }
    }
    
    
    if (count($companies_ids) > 0) $companies_where = 'id IN ('.implode(',',$companies_ids).')';
    if (count($amendments_ids) > 0) $amendments_where = 'id IN ('.implode(',',$amendments_ids).')';
    
    if ($_nationality != '') {
        $_nationality = mb_convert_case($_nationality,MB_CASE_TITLE);

        if ($companies_where != '') {
            $companies_where .= ' AND ';
            $amendments_where .= ' AND ';
        }
        //$companies_where .= 'chairman_nationality = "'.$_nationality.'"';
        $companies_where .= 'chairman_nationality = :nationality';
        $amendments_where .= '1 = 2';  // amendments are served without nationality information, disable results via false where clause
    }

    $hits = array();
    if ($_nationality == '') $hits[] = $check;

    if ($db && ($check != '' || $_nationality != '')) {
        $hitsbits = array();
 
    if (DBTYPE == 'mysql') {
        $s = "SELECT 
                count(id) as nb, 
                group_concat(id SEPARATOR ',') as ids, 
                chairman, 
                group_concat(chairman_gender SEPARATOR '|') as chairman_genders, 
                group_concat(name SEPARATOR '|') as names, 
                group_concat(agent SEPARATOR '|') as agents,
                group_concat(chairman_nationality SEPARATOR '|') as nationalities
            FROM (
                SELECT 
                    concat('R',id) as id, 
                    name, 
                    chairman, 
                    chairman_gender, 
                    chairman_nationality, 
                    agent 
                FROM companies 
                WHERE ".($companies_where != '' ? $companies_where : "1 = 2")." 
                UNION SELECT 
                    concat('A',id) as id, 
                    name, 
                    chairman, 
                    chairman_gender, 
                    '' as nationality,
                    '' as agent 
                FROM amendments 
                WHERE ".($amendments_where != '' ? $amendments_where : "1 = 2")."
            ) 
            as merged 
            GROUP BY chairman 
            ORDER BY nb DESC, chairman";
     
      $stmt = $db->prepare($s);
      $bind = $stmt->bindParam(':nationality', $_nationality, PDO::PARAM_STR);       
      $result = $stmt->execute();
      
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      if(!$result){
        dbga($stmt->errorInfo());
      }
    } else {
        $s = "SELECT count(id) as nb, group_concat(id,',') as ids, chairman, chairman_gender, group_concat(name,'|') as names, group_concat(agent,'|') as agents FROM (SELECT 'R'||id as id, name, chairman, chairman_gender, agent FROM companies WHERE ".($companies_where != '' ? $companies_where : "1 = 2")." UNION SELECT 'A'||id as id, name, chairman, chairman_gender, '' as agent FROM amendments WHERE ".($amendments_where != '' ? $amendments_where : "1 = 2").") as merged GROUP BY chairman ORDER BY nb DESC, chairman";
        $result = $db->query($s);
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    //while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    foreach($rows as $row){
        
    /*    if ($first) {
        echo '<b>COMPANY CHAIRMAN & AGENT RESULTS</b><br>--------------<br>';
        $first = false;
        }   */
        
    
        $hit = 0; // 0 = no fuzzy match, 1 = chairman match, 2 = agent match
        $lev = 0;
        $sim = 0;
        if ($check != '' && levenshtein($check, $row['chairman']) <= $_threshold) {
            $lev = levenshtein($check,$row['chairman']);
            $sim = similar_text($check,$row['chairman']);

            $hits[] = $row['chairman'];
            $hit = 1;
        } else {
            $agents = explode('|',$row['agents']);
            foreach ($agents as $a) {
                if ($check != '' && levenshtein($check,$a) < $_threshold) {
                    $lev = levenshtein($check,$a);
                    $sim = similar_text($check,$a);

                    $hits[] = $a;
                    $hit = 2;
                }
            }
        }
        
        // determine most used gender string
        $genders = explode('|',$row['chairman_genders']);
        $genders = array_count_values($genders);
        asort($genders);
        $genders = array_keys($genders);
        $row['chairman_gender'] = $genders[count($genders) - 1];
        
        // determine nationality
        $nationalities = explode('|',$row['nationalities']);
        $nationalities = array_filter(array_filter($nationalities)); // remove empty values
        $nationalities = array_count_values($nationalities);
        asort($nationalities);
        $nationalities = array_keys($nationalities);
        $row['chairman_nationality'] = (!empty($nationalities[0]) ? $nationalities[0] : null);
        
        /** REFACTORED TO ARRAY **/    
        //$bit = $row['chairman_gender'].' '.($hit == 1 || $check == '' ? '<strong>'.$row['chairman'].'</strong>' : $row['chairman']).' <a href="/search?type=ids&id='.$row['ids'].'" class="linkid">'.$row['nb'].($row['nb'] > 1 ? ' hits' : ' hit').'</a><br>';
        
        $chairman = array();
        $chairman['gender'] = $row['chairman_gender'];
        $chairman['name'] = ($hit == 1 || $check == '' ? '<strong>'.$row['chairman'].'</strong>' : $row['chairman']);
        $chairman['ids'] = $row['ids']; 
        $chairman['nb'] = $row['nb'];
        $chairman['nationality'] = $row['chairman_nationality'];
        $bit = $chairman;
        
        $ids = explode(',',$row['ids']);
        $names = explode('|',$row['names']);
        $agents = explode('|',$row['agents']);
        $hitsname = array();
        $lines = array();
        for ($i = 0; $i < count($names); $i++) {
            if (!in_array($names[$i],$hitsname)) {
                $hitsname[] = $names[$i];
                /** REFACTORING **/
                $lines[] = array($names[$i],$ids[$i],'<span class="details">'.$names[$i]. ($hit == 2 && levenshtein($check,$agents[$i]) < 3 ? ' (Agent: <b>'.$agents[$i].'</b>)' : '').'</span> ');
            } else {
                $hitarray = array_search($names[$i],$hitsname);
                $lines[$hitarray][1] .= ','.$ids[$i];
            }
        }

        usort($lines,"lines"); // sort company names in alphabetical order

        foreach($lines as $l) {

            /** REFACTOR **/
            $bit['companies'][] = array('name' => $l[2], 'registration' => $l[1]); // $l[2].'<a href="print.php?id='.$l[1].'" class="linkcompany">&bull; View</a><br>';
        }

        //$bit .= '<br>';
        $hitsbits[] = array($lev, $hit, $bit);
    }
 
    if ($check != '') usort($hitsbits,"bits"); // sort name search result based on fuzziness (i.e. from closest to farthest match)
    
    $results['people'] = $hitsbits;
        
    //foreach($hitsbits as $hb) echo $hb[2];

    $hits = array_filter($hits);
    
    
        
    if (count($hits) > 0) {
        /** Finding Amendments **/
        $resolutions_where = "resolution_text like '%".implode("%' or resolution_text like '%",$hits)."%'";
        if (DBTYPE == 'mysql') {
            $s = "SELECT concat('A',id) as id, name, resolution_date, resolution_text, chairman, chairman_gender FROM amendments WHERE (".$resolutions_where.") ORDER BY name, resolution_date";
        } else {
            $s = "SELECT 'A'||id as id, name, resolution_date, resolution_text, chairman, chairman_gender FROM amendments WHERE (".$resolutions_where.") ORDER BY name, resolution_date";
        }
        $result = $db->query($s);

        //$first = true;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            /*
            if ($first) {
                echo '<b>AMENDMENT RESOLUTION RESULTS</b><br>--------------<br>';
                $first = false;
            }
            */

            /** REFACTOR HERE **/
            // echo $row['name'].($row['chairman'] != '' ? '<br>Chairman: '.$row['chairman_gender'].' <a href="name.php?name='.urlencode($row['chairman']).'" class="linkname">&bull; '.$row['chairman'].'</a>' : '').'<br>';

            // beautify resolution text
            $row['resolution_text'] = mb_eregi_replace('-\s*res','<br>- Res',$row['resolution_text']);
            $row['resolution_text'] = mb_eregi_replace('\+\s*res','<br>- Res',$row['resolution_text']);
            $row['resolution_text'] = mb_eregi_replace('([0-9]+)\.\s*res', '<br>- Res',$row['resolution_text']);
            $row['chairperson'] = $row['chairman'];
            $row['chairperson_gender'] = $row['chairman_gender'];
            // highlight string matches in resolution text (for less than a hundred matching string to prevent flooding)
            if (count($hits) < 100) {
                foreach($hits as $h) {
                    $hlen = mb_strlen($h);
                    $rlen = mb_strlen('<span class="highlight">'.$h.'</span>');
                    $cpos = 0;
                    while (($pos = mb_stripos($row['resolution_text'],$h,$cpos)) !== false) {
                        $row['resolution_text'] = mb_substr($row['resolution_text'],0,$pos).'<span class="highlight">'.mb_substr($row['resolution_text'],$pos,$hlen).'</span>'.mb_substr($row['resolution_text'],$pos+$hlen);
                        $cpos = $pos + $rlen;
                    }
                }
            }
            
            $amendments[] = $row; 
            // echo 'Amendment <a href="print.php?id='.$row['id'].'" class="linkid">&bull; #'.$row['id'].'</a> dated '.$row['resolution_date'].': <span class="details"><i>'.$row['resolution_text'].'</i></span><br><br>';
        }
        
    }
        $results['amendments'] = $amendments;
        
        return $results;
        
}


}




/** Address Search **/
function addressSearch($db){
    
    $_house = isset($_GET['house']) ? trim($_GET['house']) : '';
    $_street = isset($_GET['street']) ? trim($_GET['street']) : '';
    
    $results = array();
	
	$streetCheck = false;
	$houseCheck = false; 
	
    $check = '';
    $check_chairman = '';
    $check_resolution_place = '';
    $check_resolution_address = '';

    if ($_house != '') {
		 /** all placeholders for $_house -> transform $_house into $paramHouse **/
		 $houseCheck = true; 
		 $paramHouse = '%' . $_house . '%'; 
         $check = "house LIKE :house_1";
         $check_chairman = "chairman_address_house LIKE :house_2";
         $check_resolution_place = "resolution_place_house LIKE :house_3";
         $check_resolution_address = "resolution_address_house LIKE :house_4";
    }
    if ($_street != '') {
         $streetCheck = true;
		 $digitStreet = false;
		 /** all placeholders for $_street -> transform $_house into $paramStreet **/
		 $paramStreetB = $_street;
		 $paramStreet1 = '%' . $_street . '%';
		 $paramStreet2 = $_street . ' %';
		 $paramStreet3 = '% ' . $_street . ' %';
		 $paramStreet4 = '% ' . $_street;
		 
		 
		 if ($check != '') {
		      $check .= " AND ";
              $check_chairman .= " AND ";
              $check_resolution_place .= " AND ";
              $check_resolution_address .= " AND ";
         }
         if (ctype_digit($_street)) {
              // if the street value entered is a number, search for exact number
			  $digitStreet = true;
              $check 			.= "(street like :street OR street like :street2 OR street like :street3 OR street like :street4)";			  
              $check_chairman 	.= "(chairman_address_street like :streetC OR chairman_address_street like :street2C OR chairman_address_street like :street3C OR chairman_address_street like :street4C)";
              $check_resolution_place 	.= "(resolution_place_street like :streetRP OR resolution_place_street like :street2RP OR resolution_place_street like :street3RP OR resolution_place_street like :street4RP)";
              $check_resolution_address .= "(resolution_address_street like :streetRA OR resolution_address_street like :street2RA OR resolution_address_street like :street3RA OR resolution_address_street like :street4RA)";
         } else {
              // if the street value entered is a string, be more flexible
              $check .= "street like :streetS1";
              $check_chairman .= "chairman_address_street like :streetSC1";
              $check_resolution_place .= "resolution_place_street like :streetSRP1";
              $check_resolution_address .= "resolution_address_street like :streetSRA1";
         }
    } 

    if ($db && $check != '') {
        if (DBTYPE == 'mysql') {
            $sql = "SELECT count(id) as nb, group_concat(id SEPARATOR ',') as ids, group_concat(name SEPARATOR '|') as names, group_concat(chairman SEPARATOR '|') as chairmans, group_concat(agent SEPARATOR '|') as agents, ad FROM (SELECT concat('R',id) as id, name, chairman, agent, address as ad, house as hs, street as st, '0000-01-01' as da FROM companies WHERE ".$check." UNION SELECT concat('R',id) as id, name, chairman, agent, chairman_address as ad, house as hs, street as st, '0000-01-01' as da FROM companies WHERE ".$check_chairman." UNION SELECT concat('A',id), name, chairman, '' as agent,  resolution_place as ad, resolution_place_house as hs, resolution_place_street as st, COALESCE(resolution_date, '1000-01-01') as da FROM amendments WHERE ".$check_resolution_place." UNION SELECT concat('A',id) as id, name, chairman, '' as agent,  resolution_address as ad, resolution_address_house as hs, resolution_address_street as st, COALESCE(resolution_date, '1000-01-01') as da FROM amendments WHERE ".$check_resolution_address." ORDER BY da) as merged GROUP BY lower(ad) ORDER BY nb DESC, hs, st";
			$stmt = $db->prepare($sql);
			
			if($houseCheck){
			 $stmt->bindParam(':house_1', $paramHouse, PDO::PARAM_STR);
			 $stmt->bindParam(':house_2', $paramHouse, PDO::PARAM_STR);
			 $stmt->bindParam(':house_3', $paramHouse, PDO::PARAM_STR);
			 $stmt->bindParam(':house_4', $paramHouse, PDO::PARAM_STR);
			}
			if($streetCheck){
			 if(!$digitStreet) { //lightweight search & fewer params 
			  $stmt->bindParam(':streetS1', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':streetSC1', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':streetSRP1', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':streetSRA1', $paramStreet1, PDO::PARAM_STR);
			 }
			 else {
			  // heavy lifting... 16 placeholders!
			  $stmt->bindParam(':street', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':street2', $paramStreet2, PDO::PARAM_STR);
			  $stmt->bindParam(':street3', $paramStreet3, PDO::PARAM_STR);
			  $stmt->bindParam(':street4', $paramStreet4, PDO::PARAM_STR);
			  
			  $stmt->bindParam(':streetC', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':street2C', $paramStreet2, PDO::PARAM_STR);
			  $stmt->bindParam(':street3C', $paramStreet3, PDO::PARAM_STR);
			  $stmt->bindParam(':street4C', $paramStreet4, PDO::PARAM_STR);
			  
			  $stmt->bindParam(':streetRP', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':street2RP', $paramStreet2, PDO::PARAM_STR);
			  $stmt->bindParam(':street3RP', $paramStreet3, PDO::PARAM_STR);
			  $stmt->bindParam(':street4RP', $paramStreet4, PDO::PARAM_STR);
			  
			  $stmt->bindParam(':streetRA', $paramStreet1, PDO::PARAM_STR);
			  $stmt->bindParam(':street2RA', $paramStreet2, PDO::PARAM_STR);
			  $stmt->bindParam(':street3RA', $paramStreet3, PDO::PARAM_STR);
			  $stmt->bindParam(':street4RA', $paramStreet4, PDO::PARAM_STR);
			  
			 }
			 
			}
			
			//echo $sql; 
			
			
        } else {
            $s = "SELECT count(id) as nb, group_concat(id,',') as ids, group_concat(name,'|') as names, group_concat(chairman,'|') as chairmans, group_concat(agent,'|') as agents, ad FROM (SELECT 'R'||id as id, name, chairman, agent, address as ad, house as hs, street as st, '0000-01-01' as da FROM companies WHERE ".$check." UNION SELECT 'R'||id as id, name, chairman, agent, chairman_address as ad, house as hs, street as st, '0000-01-01' as da FROM companies WHERE ".$check_chairman." UNION SELECT 'A'||id, name, chairman, '' as agent,  resolution_place as ad, resolution_place_house as hs, resolution_place_street as st, COALESCE(resolution_date, '1000-01-01') as da FROM amendments WHERE ".$check_resolution_place." UNION SELECT 'A'||id as id, name, chairman, '' as agent,  resolution_address as ad, resolution_address_house as hs, resolution_address_street as st, COALESCE(resolution_date, '1000-01-01') as da FROM amendments WHERE ".$check_resolution_address." ORDER BY da) as merged GROUP BY lower(ad) ORDER BY nb DESC, hs, st";
        }
        
		$query = $stmt->execute();
		
		if(!$query){
		 dbga($stmt->errorInfo());
		}
		
		$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		//dbga($records);
		
        $match = array();
     
        foreach ($records as $row) {
		 
            /** CYCLING RESULTS **/
            $resultset = array();
            $ids = explode(',',$row['ids']);
            $hits = array();
            foreach($ids as $i) if (!in_array($i,$hits)) $hits[] = $i;
            $nb = count($hits);
            //echo '<b>'.$row['ad'].'</b> <a href="print.php?id='.implode(',',$hits).'" class="linkid">&bull; '.$nb.($nb > 1 ? ' hits' : ' hit').'</a><br>';
            
            $resultset['address'] = $row['ad'];
            $resultset['hits'] = $hits;
            
            
            $hits = array();
            $names = explode('|',$row['names']);
            $chairmans = explode('|',$row['chairmans']);
            $agents = explode('|',$row['agents']);
            
            $hitsname = array(); // prevent company duplicates
            $hitspeople = array(); // prevent chairman / agent duplicates
            $lines = array();
            
            for ($i = 0; $i < count($names); $i++) {
                if (!in_array($names[$i],$hitsname)) {
                    $hitsname[] = $names[$i];
                    $hitspeople[count($hitsname)-1] = array($chairmans[$i],$agents[$i]);
                    $lines[] = array(
                        'company'       => $names[$i],
                        'registration'  => $ids[$i],
                        // DUPLICATE '<span class="details">'.$names[$i].'</span>',
                        'chairmen' => [ ($chairmans[$i] != '' ? $chairmans[$i] : '')],
                        'agents' => [ ($agents[$i] != '' && $agents[$i] != $chairmans[$i] ? $agents[$i] : '') ]
                    );
                    
                   // dbga($lines);
                } 
                else {
                    $hit = array_search($names[$i],$hitsname);
                    $lines[$hit]['registration'] .= ','.$ids[$i];
                    if ($chairmans[$i] != '' && !in_array($chairmans[$i],$hitspeople[$hit])) {
                        $hitspeople[$hit][] = $chairmans[$i];
                        
                        if(!in_array($chairmans[$i], $lines[$hit]['chairmen'])) { 
                            $lines[$hit]['chairmen'][] = $chairmans[$i];
                        }
                        
                        /*if (mb_strpos($lines[$hit][3],'>'.$chairmans[$i].'<') === false) {
                            
                            
                            $lines[$hit]['chairmen'][] = $chairmans[$i];
                                //'; Chairman: <a href="name.php?name='.urlencode($chairmans[$i]).'" class="linkname">&bull; '.$chairmans[$i].'</a>';
                        }
                        */
                    }
                    if ($agents[$i] != '' && !in_array($agents[$i],$hitspeople[$hit])) {
                        if(!in_array($agents[$i], $lines[$hit]['agents'])) { 
                            $lines[$hit]['agents'][] = $agents[$i];
                        }
                        /*
                        $hitspeople[$hit][] = $agents[$i];
                        if (mb_strpos($lines[$hit][3],'>'.$agents[$i].'<') === false) {
                            
                            $lines[$hit]['agents'][] = $agents[$i];
                                //'; Agent: <a href="name.php?name='.urlencode($agents[$i]).'" class="linkname">&bull; '.$agents[$i].'</a>';     
                        }
                        */
                    }
                }
            }
            usort($lines,"company_name_sort"); // sort company names in alphabetical order
            $resultset['entries'] = $lines;
            foreach($lines as $l) {
               /* echo $l[2].' <a href="print.php?id='.$l[1].'" class="linkcompany">&bull; View</a>';
                if ($l[3] != '') echo ' / '.$l[3];
                echo '<br>';
            */    
               //dbga($l);
            }
          //echo '<br>';
           $results[] = $resultset; 
        }
        return $results;
    }

}

/** Company Search **/
function companySearch($db){
    $_company = isset($_GET['company']) ? trim( safe(mb_strtolower($_GET['company'])) ) : '';
    $check = '';
    $check_resolution = '';
    $results = array();
    $results['companies'] = [];
    $results['amendments'] = [];
    if (mb_strlen($_company) > 1) {
        $bits = explode(' ',$_company);
        $tmp = array();
        $acro = false;
        $acro_bits = array();
        foreach ($bits as $b) {
            $b = trim($b);
  
            // since acronyms come in various forms (C.R.C.K, CRCK, C R C K), search for all forms
            if (mb_strlen($b) == 1) {
			    $b = filter_var($b, FILTER_SANITIZE_STRING);
				
                $acro = true;
                $acro_bits[] = $b;
            } else {
                if ($acro) {
                    $acro = false;
                    $check .= " (name LIKE '%".implode(' ',$acro_bits)."%' OR name LIKE '%".implode('.',$acro_bits)."%' OR name LIKE '%".implode('',$acro_bits)."') AND ";
                    $check_resolution .= " (resolution_text LIKE '%".implode(' ',$acro_bits)."%' OR resolution_text LIKE '%".implode('.',$acro_bits)."%' OR resolution_text LIKE '%".implode('',$acro_bits)."') AND ";
                    $tmp[] = implode(' ',$acro_bits);
                    $tmp[] = implode('.',$acro_bits);
                    $tmp[] = implode('',$acro_bits);
                    $acro_bits = array();
                }
                if (mb_substr_count($b,'.') > 0) {
                    $check .= " (name LIKE '%".$b."%' OR name LIKE '%".mb_eregi_replace('\.',' ',$b)."%' OR name LIKE '%".mb_eregi_replace('\.','',$b)."%') AND ";
                    $check_resolution .= " (resolution_text LIKE '%".$b."%' OR resolution_text LIKE '%".mb_eregi_replace('\.',' ',$b)."%' OR resolution_text LIKE '%".mb_eregi_replace('\.','',$b)."%') AND ";
                    $tmp[] = $b;
                    $tmp[] = mb_eregi_replace('\.',' ',$b);
                    $tmp[] = mb_eregi_replace('\.','',$b);
                } else {
                    $check .= " name LIKE '%".$b."%' AND ";
                    $check_resolution .= " resolution_text LIKE '%".$b."%' AND ";
                    $tmp[] = $b;
                }    
            }
        }
        if ($acro) {
            $check .= " (name LIKE '%".implode(' ',$acro_bits)."%' OR name LIKE '%".implode('.',$acro_bits)."%' OR name LIKE '%".implode('',$acro_bits)."') AND ";
            $check_resolution .= " (resolution_text LIKE '%".implode(' ',$acro_bits)."%' OR resolution_text LIKE '%".implode('.',$acro_bits)."%' OR resolution_text LIKE '%".implode('',$acro_bits)."') AND ";
            $tmp[] = implode(' ',$acro_bits);
            $tmp[] = implode('.',$acro_bits);
            $tmp[] = implode('',$acro_bits);
        }
 
        $bits = $tmp;
    }

    if ($db && $check != '') {
        if (DBTYPE == 'mysql') {
            $s = "SELECT group_concat(id SEPARATOR ',') as ids, name, group_concat(chairman SEPARATOR '|') as chairmans, group_concat(agent SEPARATOR '|') as agents FROM (SELECT concat('R',id) as id, name, chairman, agent, '0000-01-01' as date FROM companies WHERE ".$check." 1=1 UNION SELECT concat('A',id) as id, name, chairman, '' as agent, COALESCE(resolution_date, '1000-01-01') as date FROM amendments WHERE ".$check." 1=1 ORDER BY date) as merged GROUP BY name ORDER BY name";
        } else {
            $s = "SELECT group_concat(id,',') as ids, name, group_concat(chairman,'|') as chairmans, group_concat(agent,'|') as agents FROM (SELECT 'R'||id as id, name, chairman, agent, '0000-01-01' as date FROM companies WHERE ".$check." 1=1 UNION SELECT 'A'||id as id, name, chairman, '' as agent, COALESCE(resolution_date, '1000-01-01') as date FROM amendments WHERE ".$check." 1=1 ORDER BY date) as merged GROUP BY name ORDER BY name";
        }
        //echo $s;
        $result = $db->query($s);
        
        /** Wrap all Ids to use in amendment search **/
        $idCollection = array();
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            
            $company = array();
            
            $ids = explode(',',$row['ids']);
            $idCollection[] = $ids;
            $chairmans = explode('|',$row['chairmans']);
            $agents = explode('|',$row['agents']);
  
            $hits = array();
            $line = array();
            
            /** REFACTOR **/
            //echo '<b>'.$row['name'].'</b> <a href="print.php?id='.$row['ids'].'" class="linkcompany">&bull; View</a><br>';
            $company['name'] = $row['name'];
            $company['ids'] = $ids;
            
            for ($i = 0; $i < count($chairmans); $i++) {
                if (!in_array($chairmans[$i],$hits)) {
                    
                    $hits[] = $chairmans[$i];
                    
                    //if (count($hits) > 1) echo '; ';
                    //echo 'Chairman: <a href="name.php?name='.urlencode($chairmans[$i]).'" class="linkname">&bull; '.$chairmans[$i].'</a>';
                    
                    $company['chairmen'][] = $chairmans[$i];
                
                }
                if ($agents[$i] != '' && !in_array($agents[$i],$hits)) {
                    $hits[] = $agents[$i];
                    //if (count($hits) > 1) echo '; ';
                    //echo 'Agent: <a href="name.php?name='.urlencode($agents[$i]).'" class="linkname">&bull; '.$agents[$i].'</a>';    
                    $company['agents'][] = $agents[$i];
                }
            }
            
            //echo '<br>';
  
            for ($i = 0; $i < count($ids); $i++) {
                //if ($i > 0) echo ', ';
                //echo ($ids[$i][0] == 'R' ? 'Registration ' : 'Amendment ').'<a href="print.php?id='.$ids[$i].'" class="linkid">&bull; #'.$ids[$i].'</a>';
            }
  
            $results['companies'][] = $company;
        }
 
        $hits = explode(' ',trim($_company));
        
        /** AMENDMENTS/RESOLUTIONS **/
        if (DBTYPE == 'mysql') {
            $s = "SELECT concat('A',id) as id, name, resolution_date, resolution_text, chairman, chairman_gender FROM amendments WHERE (".substr($check_resolution, 0, -4).") OR resolution_text LIKE '%" . implode(' ' , $bits) . "%' AND 1=1 ORDER BY name, resolution_date";
          
        } else {
            $s = "SELECT 'A'||id as id, name, resolution_date, resolution_text, chairman, chairman_gender FROM amendments WHERE ".$check_resolution." 1=1 ORDER BY name, resolution_date";
        }
        
        
        $result = $db->query($s);
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $amendment = array();
            // echo $row['name'].($row['chairman'] != '' ? '<br>Chairman: '.$row['chairman_gender'].' <a href="name.php?name='.urlencode($row['chairman']).'" class="linkname">&bull; '.$row['chairman'].'</a>' : '').'<br>';

            
            $row['resolution_text'] = mb_eregi_replace('-\s*res','<br>- Res',$row['resolution_text']);
            $row['resolution_text'] = mb_eregi_replace('\+\s*res','<br>- Res',$row['resolution_text']);
            $row['resolution_text'] = mb_eregi_replace('([0-9]+)\.\s*res', '<br>- Res',$row['resolution_text']);
            
            // highlight string matches in resolution text
            foreach($hits as $h) {
                $hlen = mb_strlen($h);
                $rlen = mb_strlen('<span class="highlight">'.$h.'</span>');
                $cpos = 0;
                while (($pos = mb_stripos($row['resolution_text'],$h,$cpos)) !== false) {
                    $row['resolution_text'] = mb_substr($row['resolution_text'],0,$pos).'<span class="highlight">'.mb_substr($row['resolution_text'],$pos,$hlen).'</span>'.mb_substr($row['resolution_text'],$pos+$hlen);
                    $cpos = $pos + $rlen;
                }
            }
            
            $amendment['resolution_date'] = $row['resolution_date'];
            $amendment['name'] = $row['name'];
            $amendment['id'] = $row['id'];
            $amendment['resolution_text'] = $row['resolution_text'];
            
            if(!empty($row['chairman'])){ 
                $amendment['chairman'] = array(
                    'gender' => $row['chairman_gender'],
                    'name' => $row['chairman']
                ); 
            }
            /*
            echo 'Amendment <a href="print.php?id='.$row['id'].'" class="linkid">&bull; #'.$row['id'].'</a> dated '.$row['resolution_date'].': <span class="details"><i>'.$row['resolution_text'].'</i></span><br>';
            echo '<br>';
            */
            $results['amendments'][] = $amendment;
        }
    }
    return $results;
}

/** ID (Registration, Resolution, Amendment) Search **/
function idSearch($db){
    
    $_id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
    $check_registration = '';
    $check_amendment = '';
    
    $results = array();
    $results['registrations'] = [];
    $results['amendments'] = [];
    
    if ($_id != '') {
        $ids = explode(',',$_id);
        foreach($ids as $i) {
            if ($i[0] == 'R') {
                if ($check_registration != '') $check_registration .= ',';
                $check_registration .= mb_substr($i,1);
            } elseif($i[0] == 'A') {
                if ($check_amendment != '') $check_amendment .= ',';
                $check_amendment .= mb_substr($i,1);  
            }
            else {
                if ($check_amendment != '') $check_amendment .= ',';
                $check_amendment .= (integer)$i;
                
                if ($check_registration != '') $check_registration .= ',';
                $check_registration .= (integer)$i;
            }
        }
    }
    
    
    if ($db && $_id != '') {
        if ($check_registration != '') {
            $result = $db->query("SELECT * FROM companies WHERE id IN (".$check_registration.")");
  
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {  
                
                
                $results['registrations'][] = $row;
                /*
                echo '<b>'.$row['name'].'</b><span style="opacity:0.4;"> Registration #R'.$row['id'].'</span><br>';
                echo 'Address: '.$row['address'].($row['house'] != '' && $row['street'] != '' ? ' <a href="address.php?house='.urlencode($row['house']).'&street='.urlencode($row['street']).'" class="linkaddress">&bull; search address</a><br>' : '<br>');
                echo 'Phone: '.$row['telephone'].'<br>Email: '.$row['email'].'<br>';
                echo 'Chairman: '.$row['chairman_gender'].' <a href="name.php?name='.urlencode($row['chairman']).'" class="linkname">&bull;  '.$row['chairman'].'</a> ('.$row['chairman_nationality'].')<br>';
                echo 'Chairman address: '.$row['chairman_address'].($row['chairman_address_house'] != '' && $row['chairman_address_street'] != '' ? ' <a href="address.php?house='.urlencode($row['chairman_address_house']).'&street='.urlencode($row['chairman_address_street']).'" class="linkaddress">&bull; search address</a><br>' : '<br>');
                if ($row['agent'] != $row['chairman']) echo 'Agent: '.$row['agent_gender'].' <a href="name.php?name='.urlencode($row['agent']).'" class="linkname">&bull; '.$row['agent'].'</a> ('.$row['agent_potition'].')<br>';
                */
            }
        }
 
        if ($check_amendment != '') {
			$sql = "SELECT * FROM amendments WHERE id IN (".$check_amendment.") ORDER BY resolution_date";
			// echo $sql;
            $result = $db->query($sql);
 
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {  
   
                $row['resolution_text'] = mb_eregi_replace('-\s*res','<br>- Res',$row['resolution_text']);
                $row['resolution_text'] = mb_eregi_replace('\+\s*res','<br>- Res',$row['resolution_text']);
                $row['resolution_text'] = mb_eregi_replace('([0-9]+)\.\s*res', '<br>- Res',$row['resolution_text']);
                
                /*
                echo '<b>'.$row['name'].'</b><span style="opacity:0.4;"> Amendment #A'.$row['id'].'</span><br>';
                echo 'Address: '.$row['resolution_address'].($row['resolution_address_house'] != '' && $row['resolution_address_street'] != '' ? ' <a href="address.php?house='.urlencode($row['resolution_address_house']).'&street='.urlencode($row['resolution_address_street']).'" class="linkaddress">&bull; search address</a><br>' : '<br>');
                if ($row['resolution_address'] != $row['resolution_place']) echo 'Resolution Place: '.$row['resolution_place'].($row['resolution_place_house'] != '' && $row['resolution_place_street'] != '' ? ' <a href="address.php?house='.urlencode($row['resolution_place_house']).'&street='.urlencode($row['resolution_place_street']).'" class="linkaddress">&bull; search address</a><br>' : '<br>');  
                echo 'Resolution date: '.$row['resolution_date'].'<br>';
                echo 'Resolution text: <i>'.$row['resolution_text'].'</i><br><br>';

                echo 'Phone: '.$row['chairman_telephone'].'<br>';
                echo 'Chairman: '.$row['chairman_gender'].' <a href="name.php?name='.urlencode($row['chairman']).'" class="linkname">&bull; '.$row['chairman'].'</a><br>';
                */
                $results['amendments'][] = $row;
            }
        }
    }
    return $results;
}
?>