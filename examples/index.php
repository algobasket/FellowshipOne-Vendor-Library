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

 $blacklist = array(
   '8642266585',
   '8646726585',
   '8648890000',
   '8434922080',
   '8649659370',
   '8437667747',
   '8434077986',
   '8642710624'
 );

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
     'recordsPerPage' => 1,
     'startReceivedDate' => date('c',strtotime("-6 months")),
     'endReceiveDate' => date('c', strtotime("now"))
   ))->get();

   $results = json_decode($r, TRUE);
   if(isset($results['results']['contributionReceipt'][0])){
     $mostRecent = $results['results']['contributionReceipt'][0];
     // var_dump($mostRecent);
     $subFund = $mostRecent['subFund']['name'];
     $campus = explode(' ', trim($subFund));
     return $campus[0];
   }else{
     return '';
   }
 }

 function is_mobile($people, $phone){
   $found = false;
   foreach($people as $person => $value){
      foreach($value['communications']['communication'] as $key => $c){
        if($c['communicationType']['@id'] == 3 && $c['searchCommunicationValue'] == $phone){
            $found[] = $person;
        }
      }
   }
   return $found;
 }

 if($_POST){
   $phone = $_POST['phone'];
   $is_mobile = false;
    
   $data = array(
       'telephone' => $phone,
       'persons' => array()
     );
   
   try{
     if(in_array($phone, $blacklist)){
        throw new Exception('Number Blacklisted');
     }

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
       $is_mobile = is_mobile($results['results']['person'], $phone);
      
       foreach($results['results']['person'] as $key => $value){
         if($is_mobile === false || in_array($key, $is_mobile)){
           if (old_enough($value['dateOfBirth'])){
              $that = &$data['persons'][];
              $that['id'] = (float) $value['@id'];
              $that['campus'] = (string) get_campus($f1, $value['@id']);
              $that['firstName'] = (string) $value['firstName'];
              $that['lastName'] = (string) $value['lastName'];
              foreach($value['communications']['communication'] as $c_key => $comm){
                if($comm['communicationGeneralType'] == 'Email'){
                  $that['email'] = (string) $comm['communicationValue'];
                }
              }

              foreach($value['addresses']['address'] as $a_key => $address){
                if($address['addressType']['@id'] == 1){ //Primary Address
                  $that['address']['streetAddress'] = (string) $address['address1'];
                  $that['address']['streetAddress2'] = (string) (isset($address['address2'])) ? $address['address2'] : '';
                  $that['address']['city'] = (string) $address['city'];
                  $that['address']['state'] = (string) $address['stProvince'];
                  $that['address']['postalCode'] = (int) $address['postalCode'];
                }
              }
            }
          }      
        }
     }
     var_dump($data);
     return false;
   }catch(Exception $e){
      var_dump($e->getMessage());
   }
}

?>


