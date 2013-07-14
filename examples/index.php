<?php
/**
 *
 * Fellowship One Library for Utilizing the V2V Token 
 * @license Non-Commercial Creative Commons, http://creativecommons.org/licenses/by-nc/2.0/, code is distributed "as is", use at own risk, all rights reserved
 * @copyright 2013 NewSpring Church
 * @author Drew Delianides drew.delianides@newspring.cc
 * @author Adapted from FellowshipOne-API by Daniel Boorn https://github.com/deboorn/FellowshipOne-API/
 *
 */
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FellowshipOne API</title>
  <style>
    body{
      font-family: Arial, "sans-serif";
    }
    form{
      margin-bottom: 30px;
    }
    div{
      border: 1px #DDD solid;
      padding: 15px;
      margin-bottom: 20px;
    }
    h3{
      margin-top: 0px;
    }
    em{
      font-size: 14px;
      color: #888;
    }
    ul {padding-left: 0px;}
    ul, li{
      list-style: none;
    }
    li span{
      color: #888;
      font-style: italic;
    }
  </style>
</head>
<body>
  <h3>FellowshipOne API Search</h3>
  <form id="search" method="POST">
    <input type="text" name="phone" />
    <input type="submit" value="search" />
  </form>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script> 
</body>
</html>

<?php

 error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); 

 require_once('../lib/phpQuery.php');
 require_once('../lib/FellowshipOne.php');
 require_once('../lib/Credentials.php');

 function old_enough($date, $age_limit=15){
  $birthDate = date('d\/j\/Y', strtotime($date));
  //explode the date to get month, day and year
  $birthDate = explode("/", $birthDate);
   //get age from date or birthdate
  $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md") ? ((date("Y")-$birthDate[2])-1):(date("Y")-$birthDate[2]));
  return $age >= $age_limit;
 }

 function get_campus($f1, $id){
   $r = $f1->json()->contributionreceipts()->search(array(
     'individualID' => $id,
     'recordsPerPage' => 100
   ))->get();
   $results = json_decode($r, TRUE);
   $mostRecent = end($results['results']['contributionReceipt']);
   $subFund = $mostRecent['subFund']['name'];
   $campus = explode(' ', trim($subFund));
   return $campus[0];
 }

 if($_POST){
   $phone = $_POST['phone'];
   
   try{
     $settings = array(
       'key' => $token,
       'url' => $url
     );

     $f1 = \F1\API::connect($settings);
     $p = $f1->json()->people()->search(array(
       'communication' => $phone,
       'include' => 'addresses,communications' //Comma separation only or it breaks
      ))->get();
    
     $results = json_decode($p, TRUE);
     if($results['results']['@count'] > 0){
       foreach($results['results']['person'] as $key => $value){
         $campus = get_campus($f1, $value['@id']);
         if (old_enough($value['dateOfBirth'])){
          // var_dump($value);
          echo "<div>";
          echo "<h4>".$value['firstName']." ".$value['lastName']." <em>(".$value['@id'].")</em> </h4>";
          echo "<h6>".$campus." Campus</h6>";
          foreach($value['addresses']['address'] as $a_key => $address){
            if($address['addressType']['@id'] == 1){ //Primary Address
              echo "<address>";
              echo $address['address1']."<br>";
              echo $address['city'].", ";
              echo $address['postalCode']."<br>";
              echo "</address>"; 
            }
          }
          
          echo "<h5>Contact Info</h5>";
          echo '<ul class="communications">';        
          foreach($value['communications']['communication'] as $c_key => $comm){
            echo '<li><span>'.$comm['communicationType']['name'].':</span>   '.$comm['communicationValue'].'</li>';
          }
          echo "</ul>";
          echo "</div>";
        }
      }
    }else{
     echo "<h3>Couldn't find anyone with the phone number ".$phone."</h3>";    
    }
   }catch(Exception $e){
      var_dump($e);
   }
}

?>


