<?php
/**
 * Fellowship One Library for Utilizing the V2V Token 
 * @license Non-Commercial Creative Commons, http://creativecommons.org/licenses/by-nc/2.0/, code is distributed "as is", use at own risk, all rights reserved
 * @copyright 2013 NewSpring Church
 * @author Drew Delianides drew.delianides@newspring.cc
 * @author Adapted from FellowshipOne-API by Daniel Boorn https://github.com/deboorn/FellowshipOne-API/
 *
 */

namespace F1;

class API{
  public $debug = false;
  public $error = null;
  public $paths;
  protected $token = '';

  protected $settings = array(
    'url' => '',
    'key' => ''
  );

  public $contentType = 'json';
  protected $endpointId;
  protected $pathIds = array();
  protected $response;

  /**
   * construct
   * @param array $settings=null
   * @returns void
   * @throws \Exception
   */
  public function __construct($settings=null){
    $this->settings = $settings ? (object) $settings : (object) $this->settings;
    $this->loadApiPaths();
  }

  /**
   * connect
   * @param array $settings=null
   * @returns void
   */
  public function connect($settings=null){
    $self = new self($settings);
    if($self->settings->url && $self->settings->key){
      $self->token = base64_encode($self->settings->key);     
    }
    return $self;
  }

  /**
   * deboug output
   * @returns void
   */
  public function d($obj){
    if($this->debug) var_dump($obj);
  }

  /**
   * magic method for building chainable api path with trigger to invoke api method
   * @param string $name
   * @param array $args
   * @returns $this
   */
  public function __call($name, $args){
    $this->endpointId .= $this->endpointId ? "_{$name}" : $name;
    $this->d($this->endpointId);
    $this->d($args);
    if(count($args)>0 && gettype($args[0]) != "array" && gettype($args[0]) != "object") $this->pathIds[] = array_shift($args);
    if(isset($this->paths[$this->endpointId])){
      $r = $this->invoke($this->endpointId, $this->paths[$this->endpointId]['verb'],$this->paths[$this->endpointId]['path'],$this->pathIds,current($args));
      $this->reset();
      return $r;
    }
    return $this;		
  }

  /**
   * clear properties used by chain requests
   * @returns void
   */
  public function reset(){
    $this->endpointId = null;
    $this->pathIds = array();
  }

  /**
   * set content type to xml
   */
  public function xml(){
    $this->contentType = 'xml';
    return $this;
  }

  /**
   * set content type to json
   */
  public function json(){
    $this->contentType = 'json';
    return $this;
  }

  /**
   * returns parsed path with ids (if any)
   * @param string $path
   * @param array $ids
   * @returns string
   * @throws \Exception
   */
  protected function parsePath($path, $ids){
    $parts = explode("/",ltrim($path,'/'));
    for($i=0; $i<count($parts); $i++){
      if($parts[$i]{0}=="{"){
        if(count($ids)==0) throw new \Exception("Api Endpont Path is Missing 1 or More IDs [path={$path}].");
        $parts[$i] = array_shift($ids);
      }
    }
    return '/'.implode("/",$parts);
  }

  /**
   * invoke api endpoint method
   * @param string $id
   * @param string $verb
   * @param string $path
   * @param array $ids=null
   * @param mixed $params=null
   */
  public function invoke($id, $verb, $path, $ids=null, $params=null){
    $path = $this->parsePath($path, $ids);
    $this->d("Invoke[$id]: {$verb} {$path}",$params);
    $url = "{$this->settings->url}{$path}.{$this->contentType}";
    $this->response = $this->fetch($url,$params,$verb);
    $this->d($this->response);
    return $this;
  }

  /**
   * return phpQuery document from xml
   * @param string $xml
   * @requires phpQuery
   * @returns phpQuery
   */
  public function getDoc($xml){
    return \phpQuery::newDocumentXML($xml);
  }

  /**
   * return api response
   * @returns object|boolean
   */
  public function get(){
    if($this->contentType=='json') return $this->response;
    return $this->getDoc($this->response);
  }



  /**
   * return error data
   * @returns object
   */
  public function error(){
    return $this->response['data'];//error_code, error_message
  }

  /**
   * loads api paths list from json file
   * @returns void
   */
  protected function loadApiPaths(){
    $filename = __DIR__ . "/api_paths.json";
    $this->paths = json_decode(file_get_contents($filename),true);
  }

  /**
   * fetches JSON request on F1, parses and returns response
   * @param string $url
   * @param string|array $data
   * @param const $method
   * @param string $contentType
   * @param boolean $returnHeaders
   * @return void
   */
  public function fetch($url,$data=null,$method='GET',$contentType=null,$returnHeaders=false,$retryCount=0){
    $defaults = array(
      CURLOPT_HEADER => 0, 
      CURLOPT_FRESH_CONNECT => 1, 
      CURLOPT_RETURNTRANSFER => TRUE, 
      CURLOPT_TIMEOUT => 4, 
      CURLOPT_HTTPHEADER => array(
        'Content-type: application/'.$this->contentType,
        'Authorization: Basic '.$this->token)
    );

    $ch = curl_init();

    if($method=='GET' && is_array($data)){
      $url .= "?" . http_build_query($data);
      $data = null;
    }

    if(($method=='PUT' || $method=='POST') && (gettype($data)=="array" || gettype($data)=="object")){
      $data = json_encode($data);
      $defaults[CURLOPT_POSTFIELDS] = http_build_query($data);    
    }
    $defaults[CURLOPT_URL] = $url; 
    curl_setopt_array($ch, $defaults);

    if(!$response = curl_exec($ch)) {
        trigger_error(curl_error($ch)); 
    }

    curl_close($ch);
    return $response;
  }

}/*END API*/
?>
