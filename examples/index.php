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

 error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); 

 require_once('../lib/phpQuery.php');
 require_once('../lib/FellowshipOne.php');
 require_once('../lib/Credentials.php');

 try{

   $settings = array(
     'key' => $token,
     'url' => $url
   );

   $f1 = \F1\API::connect($settings);
   $p = $f1->json()->people_new()->get();
   var_dump($p);

 }catch(Exception $e){
		var_dump($e);
 }
