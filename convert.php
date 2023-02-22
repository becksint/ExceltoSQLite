<?php /** @noinspection ForgottenDebugOutputInspection */
session_start();
$_SESSION['mode'] = 'admin'; 
$pagepick = 1; 

use Shuchkin\SimpleXLSX;/* ini_set('error_reporting', E_ALL); ini_set('display_errors', true); */
require_once ('src/SimpleXLSX.php');
$spreadsheet = 'newclient';



if (isset($_POST["import"])) {

    $allowedFileType = [
        'application/vnd.ms-excel',
        'text/xls',
        'text/xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if (in_array($_FILES["file"]["type"], $allowedFileType)) {

        $targetPath = 'media/loaddock/' . $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);           
        
    } else {
        $type = "error";
        $message = "Invalid File Type. Upload Excel File.";
    }
}

?>
<aside id="rightbar">
    
    <div class="outer-container">
        <form action="" method="post"
            name="frmExcelImport" id="frmExcelImport" enctype="multipart/form-data"><h2>Import Data</h2>
            <div>
                <label>Choose Excel
                    File</label> <input type="file" name="file"  id="file" accept=".xls,.xlsx">
                <button type="submit" id="submit" name="import"
                    class="btn-submit">Import</button>
        
            </div>
        
        </form>
        
    </div>
    <div id="response" class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>"><?php if(!empty($message)) { echo $message; } ?></div>
    

<?php

readfiles('media/loaddock');


echo '</aside>';
if(isset($_GET['qualifydata'])){
    $spreadsheet = $_GET['qualifydata'];
    qualifysheet($spreadsheet);
    unset($_GET['qualifydata']);
}
if(isset($_GET['deletedata'])){
    $spreadsheet = $_GET['deletedata'];
    deletesheet($spreadsheet);
    unset($_GET['deletedata']);
}


function readfiles($path){
    $path    = $path;
    $files = array_diff(scandir($path), array('.', '..'));
    echo '<section class="filegallery"><header><h2>Qualify Data</h2></header><main>';
    if(!empty($files)){
        foreach ($files as $key => $file) {
            $fileray = explode('.',$file);
            $name = $fileray[0];
            if(isset($fileray[1])){ 
                $format = $fileray[1];
            if($format == 'xlsx'){
                echo '<article><header><span class="icons">&#xe8b2;</span>  '.$name.'</header><footer>
                <a href="?qualifydata='.$name.'">Qualify</a>
                <a href="?qualifyolddata='.$name.'">Old Qualify</a>
                <a href="?deletedata='.$name.'">Delete</a>
                </footer></article>';
            }
        }
        }
    }
    echo '</section>';   
}


function deletesheet($spreadsheet){
    if (!unlink('media/loaddock/'.$spreadsheet.'.xlsx')) {
        echo ("$spreadsheet cannot be deleted due to an error");
    }
    else {
        //echo ("$spreadsheet has been deleted");
        header("location:sheettaker.php");
    }
}
function qualifysheet($spreadsheet){
    if ($xlsx = SimpleXLSX::parse('media/loaddock/'.$spreadsheet.'.xlsx')) {
    //echo '<pre>'.print_r($xlsx->sheetNames(), true).'</pre>';
    echo '<main id ="viewgroup"><br><b>Qualified Data will go to</b>
        <h2>';  
        if(isset($_SESSION['client']['folder'])){ echo ' <span class="uppercase">'.$_SESSION['client']['folder'].'</span> ';} //Make Drop Down To Select Client
    echo '</h2>
        <p>Please note that only qualified & warning data will be implemented to the database.</p><p>Ensure critical missing fields are impleneted to maintain data integrity. </p>
        <button class="btn-submit">QUALIFY DATA TO CLIENT</button>';
    $sheets = $xlsx->sheetNames();
   /*  echo '<table cellpadding="10">
	<tr>'; */
    $_SESSION['data'] = array();
    foreach ($sheets as $sheet_number => $sheet_name) {
        //echo sheet_json($xlsx, $sheet_number, $sheet_name);  
        //echo sheet_table($xlsx, $sheet_number, $sheet_name);  
        //echo $sheet_name;
        if ($sheet_number === 1) {
            custom_datacheck($xlsx, $sheet_number, $sheet_name);
        }
        
        //echo  'INSERT INTO '.$sheet_name. '('.implode(",",$_SESSION['importdata'][$sheet_name]['names']).') VALUES '; //implode column names 
        
    } 
   
    foreach ($_SESSION['data'] as $group=>$values) {
        
       echo '<h3>'.$group.'</h3>'; 
       /* foreach($_SESSION['importdata'][$sheet_name]['names'] as $k1=>$v2){
        echo $v2.' ';
    } */ $a = 0; 
        $b = 1000; //eerste
        $b = 3000; //mitchells plain
       foreach ($values as $key => $value) {
            switch ($key) {
                case 2:     echo '<div class="accordian accordianbutton"><input type="checkbox" checked><i></i><header><h2>Qualified <sup>'.count($value).'</sup></h2></header><main class="collectionsbars">'; break;
                case 1:     echo '<div class="accordian accordianbutton"><input type="checkbox" checked><i></i><header><h2>Warning <sup>'.count($value).'</sup></h2></header><main class="collectionsbars">'; break;
                case 0:     echo '<div class="accordian accordianbutton"><input type="checkbox" checked><i></i><header><h2>Rejected <sup>'.count($value).'</sup></h2></header><main class="collectionsbars">'; break;
                default:    echo '<div class="accordian accordianbutton"><input type="checkbox" checked><i></i><header><h2>Ignored <sup>'.count($value).'</sup></h2></header><main class="collectionsbars">'; break;
            }
            //var_dump($value[2]);
           if(isset($value[2])){             
            
                $colnames = array();
                foreach ($value[2] as $kcheck => $colname) {
                    if($_SESSION['importdata']['1. Mixed']['groupcheck'][$kcheck] == $group){
                         $colnames[] = $colname;
                    }
                }
                echo '<div><span>INSERT INTO '.$group.' (';
                if($group == 'person2'){ echo '`id`,' ; }
                echo '`'.implode('`,`', $colnames).'`) <br> VALUES </span></div>'; 
            }
          
            foreach ($value as $k => $data) {
                //$insert[$group] .= $k;   //$names = $value[3];
                if($k<=9 AND !empty(implode('',$data))){
                    switch ($k) {
                        case 0: $settings['title']      = $value[0]; break;
                        case 1: $settings['info']       = $value[1]; break;
                        case 2: $settings['names']      = $value[2]; break;
                        case 3: $settings['table']      = $value[3]; break;
                        case 4: $settings['filter']     = $value[4]; break;
                        case 5: $settings['unique']     = $value[5]; break;
                        case 6: $settings['explode']    = $value[6]; break;
                        case 7: $settings['code']       = $value[7]; break; //how to handle the data, calculations, logic, etc
                        case 8: $settings['ignore']     = $value[8]; break;
                        case 9: $settings['example']    = $value[9]; break;
                    }
                    if(isset($value[0])){   }
                }
                //ar_dump($data);
                if($k>9 AND !empty(implode('',$data))){ //check for empty row contents
                
                $new_array = array();
                foreach ($data as $k2 => $col) { 
                    //echo $names[$ke2y];
                    //echo $col.' '; 
                    //if(isset($_SESSION['importdata']['1. Mixed']['groupcheck'][$k2])){
                    if($_SESSION['importdata']['1. Mixed']['groupcheck'][$k2] == $group){
                        if(isset($value[2])){
                            $name = $settings['names'][$k2]; 
                            $col = colmaker($name, $col);                        
                        }
                        if(!empty($col)) array_push($new_array, $col);
                    }
                }  
                if(!empty($new_array)) {
                    if(is_numeric($k)){
                        echo '<div>("';  if($group == 'person2'){ echo $b.'", "'; echo  ''.($k*1 - 9).'","3","3' ;  
                                            }
                                        //echo implode('","', $new_array); 
                                        echo '"),</div>'; 
                                        $a++; $b++;
                    }
                        }
            }}//}
            echo $a; 
           echo '</main></div>';
        }
    }
    echo '</main>';
    /* echo '</tr></table>'; */
    } 
    else {
        echo SimpleXLSX::parseError();
    }
}


function colmaker($settings, $col){
switch ($settings) {
case 'password': $col = password_hash($col, PASSWORD_DEFAULT); break;
case 'title_id': switch ($col) { //get from json lists, remove case sensitveness
                       case 'MR':  $col =1; break;
                       case 'MS':  $col =6; break;
                       case 'MRS':  $col =2; break;
                       default:$col =0;break;
                    }break;
case 'type': switch ($col) {
                       case 'MANAGER':  $col =3; break;
                       case 'ADMIN':  $col =5; break;
                       default:$col =0;break;
                    }break;
case 'idnumbedrgg': switch ($col){
                       case '1':  $col =1; break;
                       case '2':  $col =2; break;
                       case '3':  $col =3; break;
                       case '4':  $col =4; break;
    }
//modify titles
default: break;
}
return $col;
}

function custom_datacheck($xlsx, $sheet_number, $sheet_name){
    $dim = $xlsx->dimension($sheet_number);
    $groupdata = $xlsx->rows($sheet_number);
    $num_cols = $dim[0];        //limit to rows with internal names, remove spacing & check for duplicates (auto mode)
    //var_dump($groupdata[11]); echo '<br><br>'; //also unset
    $_SESSION['importdata'][$sheet_name]['titles']      = $titles    = $groupdata[0];           // title
    $_SESSION['importdata'][$sheet_name]['info']        = $info      = $groupdata[1];           // sub data, including presets, and notes :: {paragraph 1 | paragraph 2}  list: [key1-choice 1 | key2-choice 2]
    $_SESSION['importdata'][$sheet_name]['names']       = $names     = $groupdata[2];           // [hidden]  column page 
    $groups     = $groupdata[3];           // [hidden] table + id + calculations + special instructions + i (if isset create in seperate table)
    $_SESSION['importdata'][$sheet_name]['special']     = $special   = $groupdata[4]; 
    
    //var_dump($groups);
    $_SESSION['importdata'][$sheet_name]['groupcheck']      = $groups;
    $groups = array_unique($groups);   
    $groups = array_values($groups); 
    $groups = array_filter($groups);    
    $_SESSION['importdata'][$sheet_name]['groups']      = $groups;


    echo '<br>';
    foreach ($groupdata as $key=>$rows) {
        if($key>1){ qualifydata($sheet_name,$key, $rows ,$num_cols);}        
    }   
    
}
function qualifydata($sheet_name, $key, $r, $end){//custom function
    $qualify = 0; //0 - reject, 1 - warn, 2 - accept, 3 - ignore
    $start = 0;
   
     
    switch ($sheet_name) {
        case '2. Users'         :   $sheet_name = 'user';
                                    if(!empty($r[5]) AND !empty($r[7])){ $qualify = 2; } //email & password needed
                                    break;   
        case '3. Consultants'   :   $sheet_name = 'staff';
                                    if(!empty($r[0]) XOR !empty($r[1])){ $qualify = 2; } 
                                    break; 
        case '4. Policies'      :   $sheet_name = 'policy';
                                    if(!empty($r[4])){ $qualify = 2; } 
                                    if(!empty($r[1])){ $qualify = 1; }
                                    if(!empty($r[3])){ $qualify = 1; }
                                    if(!empty($r[5])){ $qualify = 1; }
                                    if(!empty($r[8])){ $qualify = 1; }
                                    if(!empty($r[9])){ $qualify = 1; }
                                    break;
                                    
        case '6. Policieg'      :   $sheet_name = 'policy_history';
                                    if(!empty($r[4])){ $qualify = 2; } 
                                    if(!empty($r[1])){ $qualify = 1; }
                                    if(!empty($r[3])){ $qualify = 1; }
                                    if(!empty($r[5])){ $qualify = 1; }
                                    if(!empty($r[8])){ $qualify = 1; }
                                    if(!empty($r[9])){ $qualify = 1; }                            
                                    break;   
       case '5. Members'       :    $sheet_name = 'person';
                                    if(!empty($r[0]) AND !empty($r[1]) AND !empty($r[2])){ $qualify = 2; } 
                                    if(empty($r[0])){ $qualify = 1; }
                                    if(empty($r[3])){ $qualify = 1; }
                                    if(empty($r[5])){ $qualify = 1; }
                                    if(empty($r[8])){ $qualify = 1; }
                                    if(empty($r[9])){ $qualify = 1; }                                    
                                    if(empty($r[1]) AND empty($r[2])){ $qualify = 5; } 
                                    
                                    break; 
        case '1. Mixed'       :    $groups  = $_SESSION['importdata'][$sheet_name]['groups'];
                                    $sheet_name = 'mixed'; $qualify = 1;
                                    /* if(!empty($r[0]) AND !empty($r[1]) AND !empty($r[2])){ $qualify = 2; } 
                                    if(empty($r[0])){ $qualify = 1; }
                                    if(empty($r[3])){ $qualify = 1; }
                                    if(empty($r[5])){ $qualify = 1; }
                                    if(empty($r[8])){ $qualify = 1; }
                                    if(empty($r[9])){ $qualify = 1; }         */                            
                                    //if(empty($r[1]) AND empty($r[2])){ $qualify = 5; } 
                                    //echo 'ff';
                                    
                                    break; 
        default: $qualify = 5;
            break;
    }

   if($qualify !== 5 AND $key != null){
        for ($i = $start; $i < $end; $i ++) {            
            //$_SESSION['data'][$sheet_name][$qualify][$key]->{'ko'.$i} = ( ! empty($r[ $i ]) ? $r[ $i ] : '  ' );            
           
            $_SESSION['data'][$sheet_name][$qualify][$key][$i] = ( ! empty($r[ $i ]) ? $r[ $i ] : '' );
        }
        
        if($sheet_name == 'persfon'){
            unset($_SESSION['data']['person'][$qualify][$key][3]);
            unset($_SESSION['data']['person'][$qualify][$key][4]);
            unset($_SESSION['data']['person'][$qualify][$key][5]);

            unset($_SESSION['data']['person'][$qualify][$key][8]);
            unset($_SESSION['data']['person'][$qualify][$key][9]);

            unset($_SESSION['data']['person'][$qualify][$key][12]);
            unset($_SESSION['data']['person'][$qualify][$key][13]);

            unset($_SESSION['data']['person'][$qualify][$key][16]);
            unset($_SESSION['data']['person'][$qualify][$key][17]);
            unset($_SESSION['data']['person'][$qualify][$key][18]);
            unset($_SESSION['data']['person'][$qualify][$key][19]);
            unset($_SESSION['data']['person'][$qualify][$key][20]);
            unset($_SESSION['data']['person'][$qualify][$key][21]);
        }
        if($sheet_name == 'persofn' AND !empty($r[3])){ 
            $sheet_name = 'members'; //member
            
            $_SESSION['mainmember'] =$key; 
            
            /* $_SESSION['data'][$sheet_name][$qualify][$key]->name = $r[3];
            $_SESSION['data'][$sheet_name][$qualify][$key]->person_id = $key;
            $_SESSION['data'][$sheet_name][$qualify][$key]->consultant_id = $r[5];
            $_SESSION['data'][$sheet_name][$qualify][$key]->poolicy_id = $r[4]; */
            $_SESSION['data'][$sheet_name][$qualify][$key]['name']          = $r[3];
            $_SESSION['data'][$sheet_name][$qualify][$key]['person_id']     = $key;
            $_SESSION['data'][$sheet_name][$qualify][$key]['consultant_id'] = $r[5];
            $_SESSION['data'][$sheet_name][$qualify][$key]['poolicy_id']    = $r[4];
            $sheet_name = 'member_history';
            
            /* $_SESSION['data'][$sheet_name][$qualify][$key]->start = $r[3]; //start
            $_SESSION['data'][$sheet_name][$qualify][$key]->end = NULL; //end
            $_SESSION['data'][$sheet_name][$qualify][$key]->member_id = $key;
            $_SESSION['data'][$sheet_name][$qualify][$key]->policy_id = $r[4]; //policy id */
            $_SESSION['data'][$sheet_name][$qualify][$key]['start'] = $r[3]; //start
            $_SESSION['data'][$sheet_name][$qualify][$key]['end'] = NULL; //end
            $_SESSION['data'][$sheet_name][$qualify][$key]['member_id'] = $key;
            $_SESSION['data'][$sheet_name][$qualify][$key]['policy_id'] = $r[4]; //policy id
        }
        if($sheet_name == 'persofn' AND empty($r[3])){ 
            $sheet_name = 'member_relations'; //dependant (also count)
            /* $_SESSION['data'][$sheet_name][$qualify][$key]->dependant = 2;
            $_SESSION['data'][$sheet_name][$qualify][$key]->member_id = $_SESSION['mainmember']; //member
            $_SESSION['data'][$sheet_name][$qualify][$key]->person_id = $key;   */   
            $_SESSION['data'][$sheet_name][$qualify][$key]['dependant'] = 2;
            $_SESSION['data'][$sheet_name][$qualify][$key]['member_id'] = $_SESSION['mainmember']; //member
            $_SESSION['data'][$sheet_name][$qualify][$key]['person_id'] = $key;  

        }  
         
    }
    if($groups){ 
            foreach ($groups as $group) {
                $_SESSION['data'][$group] =  $_SESSION['data']['mixed'];
            }
            //var_dump($pointblank);
    } 

}

/* 
This functions allows for the printing out of the sheet table using the foreach loop
Sheet number names with default settings can be edited otu as need. 
$num_rows = $dim[1]; edited out as taken from xlsx->rows($sheet_number)

first commit and contribute for bx!!!
help document other spreadsheet project [converting excel spreadsheets into useful function will be key to my business model, contribute to a a project which allows the automation of this into a function SQL & PHP function logic]
*/

function sortbygroup($xlsx, $sheet_number, $sheet_name){
    $dim = $xlsx->dimension($sheet_number);
    $num_cols = $dim[0];
    //$num_rows = $dim[1];
    $output  = '<td valign="top">';
    $output .= '<h2>'.$sheet_name.'</h2>';
    $output .= '<table border=1>';
    foreach ($xlsx->rows($sheet_number) as $key=>$r) {
        if($key>1){
            $output .= '<tr>';
            for ($i = 0; $i < $num_cols; $i ++) {
                $output .= '<td>' . ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' ) . '</td>';
            }
            $output .= '</tr>';
        }
    }
    $output .= '</table>';
    $output .= '</td>';
    return $output;
}

function sheet_table($xlsx, $sheet_number, $sheet_name){
    $dim = $xlsx->dimension($sheet_number);
    $num_cols = $dim[0];
    //$num_rows = $dim[1];
    $output  = '<td valign="top">';
    $output .= '<h2>'.$sheet_name.'</h2>';
    $output .= '<table border=1>';
    foreach ($xlsx->rows($sheet_number) as $key=>$r) {
        if($key>1){
            $output .= '<tr>';
            for ($i = 0; $i < $num_cols; $i ++) {
                $output .= '<td>' . ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' ) . '</td>';
            }
            $output .= '</tr>';
        }
    }
    $output .= '</table>';
    $output .= '</td>';
    return $output;
}


/* 
$column_names =array('ID_Number', 'Policy_Number', 'Surname', 'Name', 'Policy_Type', 'Consultant_No', 'Street_Address', 'Suburb', 'Inception_Date','Age', 'Date of Birth', 'Mobile', 'Last_Paid', 'Cover_Date', 'Gender', 'Email', 'Balance', 'Dependant_Name', 'Dependant_Surname','Dependant_ID_Number' ,'Dependant_Type'); //get from json
$column_names = array('description', 'product code','uom'); */

function sheet_object($xlsx, $sheet_number, $sheet_name){
    $dim = $xlsx->dimension($sheet_number);
    $num_cols = $dim[0];
    //$num_rows = $dim[1];
    $output  = '';
    $breakcol = 17;

    $output  = '<td valign="top">';
    $output .= '<h2>'.$sheet_name.'</h2>';
    $output .= '<table border=1>';
    $newput = '';
    foreach ($xlsx->rows($sheet_number) as $rnum => $r) {
        $output .= '<tr>';

        if($sheet_number === 5 && !is_string($r[5]) &&  !empty($r[0]) && !is_string($r[4])){
            $num = $r[5] + 88; $total =  $num;
            //echo $r[4].'<br>';  
            if($r[4] == 'SINGLE 85+') $r[4] = 18;
            $premium    = $_SESSION['policy'][$r[4]][4];
            $risk       = $_SESSION['policy'][$r[4]][3];
            $cover      = $_SESSION['policy'][$r[4]][5];
            $category   = 77 + $r[4];
            //echo "UPDATE `Members` SET `Mem_Consultant` = $total, `Mem_Address` = '$r[6]', `Mem_Category`=$category, `Mem_Premium`=$premium, `Mem_Cover`=$cover, `Mem_Risk_Premium`=$risk WHERE `Mem_ID_No` = $r[0]  AND `Mem_Company` = 8;<br>";
            //echo "UPDATE `Members` SET  `Mem_Category`=$category, `Mem_Premium`=$premium, `Mem_Cover`=$cover, `Mem_Risk_Premium`=$risk WHERE `Mem_ID_No` = $r[0]  AND `Mem_Company` = 8;<br>";
            //echo "UPDATE `Members` SET  `Mem_Category`=$category WHERE `Mem_ID_No` = $r[0]  AND `Mem_Company` = 8;<br>";
            //echo "UPDATE `Members` SET  `Mem_Category`=$category WHERE `Mem_ID_No` = $r[0]  AND `Mem_Company` = 8;<br>";
         }
        for ($i = 0; $i < $num_cols; $i ++) {
            if ( $i >= $breakcol){ 
                if(empty($r[ $breakcol ])) break;
                else {    
                    if(!empty($r[0])){ $master = $r[0];}             
                    $subunit[$sheet_number][$master][$rnum][$i] = ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' );  
                }
            } 
            else { $output .= '<td>' . ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' ) . '</td>'; }
        }
        $output .= '</tr>';
    }
    $output .= '</table>';
    $output .= '</td>';
    echo '<pre>';
    ( isset($subunit) ? print_r($subunit): '&nbsp;' ); //get count of sub_unit, get mem_id by id_num, then insert dependant in table 
    echo '</pre>' ;
 
     if($sheet_number === 4){
        //echo '<pre>'; print_r($xlsx->rows($sheet_number)); echo '</pre>' ;
        $_SESSION['policy'] = $xlsx->rows($sheet_number);
        /* echo "INSERT INTO `Company_Packages`(`Comp_ID`, `Und_Cat_ID`, `Und_Cat_Desc`, `Und_ID`, `Und_Cat_Risk`, `Und_Cat_Premium`, `Und_Cat_Cover`, `Und_Cat_Dependants`, `Und_Cat_Active`, `Und_Cat_Age_Start`, `Und_Cat_Age_End`) VALUES ";
        foreach ($_SESSION['policy'] as $key => $value) {
            $total = 77 + $key; 
            if($key !==0){
                
                echo "(8, $total, '$value[2]',7, $value[3], $value[4], $value[5], $value[7], 1, $value[8], $value[9]), <br>";
            }
        } */
        
     }
    return $output;
}

function sheet_json($xlsx, $sheet_number, $sheet_name){
    $dim = $xlsx->dimension($sheet_number);
    $num_cols = $dim[0];
    $output  = '';
    $breakcol = 17;

    $output  = '<h2>'.$sheet_name.'</h2>';
    $output .= '{';
    foreach ($xlsx->rows($sheet_number) as $rnum => $r) {
        
        $unit = array();
        
        for ($i = 0; $i < $num_cols; $i ++) {
            if ( $i >= $breakcol){ 
                if(empty($r[ $breakcol ])) break;
                else {    
                    if(!empty($r[0])){ $master = $r[0];}             
                    $subunit[$sheet_number][$master][$rnum][$i] = ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' );  
                }
            } 
            else { $unit[] = '"' . ( ! empty($r[ $i ]) ? $r[ $i ] : '&nbsp;' ) . '"'; }
        }
        if($rnum>0){ $output .= reformat($rnum,$unit);}
    }
    $output .= '}';

    return $output;
}

function reformat($key,$values){
    //read rows aand match to template

    $output = '';
    if($key >1){$output = ',';}
    if(isset($values[2])){$output .= '"'.$key.'":{"names":{"name":'.$values[0].'},"control":{"code":'.$values[1].',"uom":'.$values[2].',"category":'.$values[3]."}}";}
    else { $output .= '"'.$key.'":{"names":{"name":'.$values[1].'}}';}
    return $output.'<br>';
}

?>


<style> .uppercase { text-transform: uppercase;} #viewgroup h3{ padding: 20px 0px 0px; } </style>
