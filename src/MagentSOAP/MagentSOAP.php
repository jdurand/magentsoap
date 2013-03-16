<?php

namespace MagentSOAP;

use SoapClient;
use SoapFault;
use stdClass;

class MagentSOAP
{
    
    private $client;
    private $session;
    
    private $sessionId;
    
    private $response;
    
    function &getInstance($class = 'MagentSOAP\MagentSOAP')
    // implements the 'singleton' design pattern.
    {
        static $instances = array();  // array of instance names
        
        if (!array_key_exists($class, $instances)) {
            // instance does not exist, so create it
            $instances[$class] = new $class;
        } // if
        
        $instance =& $instances[$class];
        
        return $instance;
        
    } // getInstance
    
    //Constructor
    function __construct($user = MAGENTO_API_USER, $key = MAGENTO_API_KEY, $endpoint = MAGENTO_API_ENDPOINT) {
        
        $options = array(
            //'trace' => true,
            'connection_timeout' => 120,
            'wsdl_cache' => WSDL_CACHE_BOTH
        );
        
        $this->client = new SoapClient($endpoint, $options);
        $this->session = $this->client->login(array(
            'username' => $user,
            'apiKey' => $key,
        ));
        
        $this->sessionId = $this->session->result;
        
        return $this;
    }
    
    //
    //Utility functions
    //
    
    //Inits return vars
    function init() {
        
        $this->response = null;
        return $this;
    }
    
    //End
    function end() {
        
        echo "\n";
        
        //End SOAP Session
        try {
            $this->client->endSession(array(
                'sessionId' => $this->sessionId
            ));
        } catch(Exception $e) {
            echo "ERROR:".$e->getMessage()."\n";
        }
        
        return $this;
    }
    
    function success($errorCallback = null, $successCallback = null) {
        
//        if(isset($this->response['error'])) {
//            echo $this->response['error']['msg'];
//        } else {
//            var_dump($this->response);
//        }
        
        return $this->response['success'];
    }
    
    function error() {
        return $this->response['error'];
    }
    
    function duplicate() {
        return ( strpos($this->response['error']['msg'], 'The value of attribute "SKU" must be unique') > -1 );
    }
    
    function httpHeaders() {
        return ( strpos($this->response['error']['msg'], 'Error Fetching http headers') > -1 );
    }
    
    function serverError() {
        return ( strpos($this->response['error']['msg'], 'Internal Server Error') > -1 );
    }
    
    function dbLock() {
        return ( strpos($this->response['error']['msg'], '1213 Deadlock found') > -1 );
    }
    
    //
    //
    // Data Formating functions
    //
    //
    
    //Converts the array of attributes to an entity
    function attrsToEntity(&$id,$attributes) {
        
        $entity = new stdClass();
        
        $id = isset($attributes['id']) ? $attributes['id'] : null;
        
        if(!$id) {
            $this->response = array('success'=> false, 'error' => array('msg' => 'An ID must be specified.'));
            return null;
        }
        
        unset($attributes['id']);
        
        //return $attributes;
        
        foreach ($attributes as $key => $val) {
            $entity->$key = $val;
        }
        
        return $entity;
    }
    
    //Deals with the SOAP error
    function dealWithError($err) {
        
        $this->response = array('success'=> false, 'error' => array('msg' => $err->getMessage())); 
        
        //var_dump($err);
        
        return $this;
    }
    
    //
    //
    // API Implementation
    //
    //
    
    
    /***
    * catalogProductCreate
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="type" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="set" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="sku" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="productData" type="typens:catalogProductCreateEntity"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="store" type="xsd:string"/>
    
    *
    *
    ***/
    function catalogProductCreate($attributes, $attr_set = ATTR_SET_DEFAULT, $type = 'simple', $store = null) {
        
        $entity = $this->init()->attrsToEntity($sku,$attributes);
        echo "[".date('Y-m-d H:i:s')."] Create $sku ... ";
        try {
            $this->response = array('success' => $this->client->catalogProductCreate(array_merge(array(
                'sessionId' =>   $this->sessionId,
                'type' =>        $type,
                'set' =>         $attr_set,
                'sku' =>         $sku,
                'productData' => $entity,
            ),( $store ? array('store' => $store) : array() ) )));
            
            echo "OK\n";
            
        } catch (SoapFault $err) {
            
            $e = $this->dealWithError($err);
            
            if($e->duplicate()) {
                echo "OK (skipped)\n";
            } else {
                echo $err->getMessage()."\n";
            }
            
            if($e->httpHeaders() || $e->serverError() || $e->dbLock()) {
                return $this->catalogProductCreate($attributes,$attr_set,$type,$store);
            }
        }
        
        return $this;
    }
    
    
    /***
    * catalogProductUpdate
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="productId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="productData" type="typens:catalogProductCreateEntity"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="store" type="xsd:string"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="identifierType" type="xsd:string"/>
    
    *
    *
    ***/
    function catalogProductUpdate($attributes = array(), $store = null) {
        
        $entity = $this->init()->attrsToEntity($sku,$attributes);
        echo "[".date('Y-m-d H:i:s')."] Update $sku ... ";
        try {
        
            $this->response = array('success' => $this->client->catalogProductUpdate(array_merge(array(
                'sessionId' =>   $this->sessionId,
                'productId' =>   $sku,
                'productData' => $entity,
                'identifierType'=>'sku'
            ),( $store ? array('store' => $store) : array() ) )));
            
            echo "OK\n";
            
        } catch (SoapFault $err) {
        
            echo $err->getMessage()."\n";
            
            if($this->dealWithError($err)->httpHeaders() || $this->dealWithError($err)->serverError() || $this->dealWithError($err)->dbLock()) {
                //return call_user_func($this->{__FUNCTION__},$attributes,$attr_set,$type,$store);
                return $this->catalogProductUpdate($attributes,$store);
            }
        }
        
        return $this;
    }
    
    
    /***
    * catalogProductLinkAssign
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="type" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="productId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="linkedProductId" type="xsd:string"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="data" type="typens:catalogProductLinkEntity"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="identifierType" type="xsd:string"/>
    
    *
    *
    ***/
    function catalogProductLinkAssign($attributes = array(), $type = /*'related'*/'grouped') {
        
        $entity = $this->init()->attrsToEntity($sku,$attributes);
        
        try {
        
            $this->response = array('success' => $this->client->catalogProductLinkAssign(array(
                'sessionId' =>   $this->sessionId,
                'type' =>        $type,
                'productId' =>   $sku,
                'linkedProductId'=> $entity->linkedProductId,
                'data' => $entity,
                'identifierType'=>'sku'
            )));
        
        } catch (SoapFault $err) { $this->dealWithError($err); }
        
        return $this;
    }
    
    
    /***
    * catalogCategoryInfo
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="categoryId" type="xsd:int"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="store" type="xsd:string"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="attributes" type="typens:ArrayOfString"/>
    
    *
    *
    ***/
    function catalogCategoryInfo($attributes = array()) {
        
        $entity = $this->init()->attrsToEntity($id,$attributes);
        
        try {
            
            $this->response = array('success' => $this->client->catalogCategoryInfo(array(
                'sessionId' =>   $this->sessionId,
                'id' =>          $id,
                'categoryData' => $entity,
            )));
            
        } catch (SoapFault $err) { $this->dealWithError($err); }
        
        return $this;
    }
    
    
    /***
    * catalogCategoryCreate
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="parentId" type="xsd:int"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="categoryData" type="typens:catalogCategoryEntityCreate"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="store" type="xsd:string"/>
    
    *
    *
    ***/
    function catalogCategoryCreate($attributes = array()) {
        
        $entity = $this->init()->attrsToEntity($id,$attributes);
        
        try {
        
           $this->response = array('success' => $this->client->catalogCategoryCreate(array(
                'sessionId' =>   $this->sessionId,
                'id' =>          $id,
                'categoryData' => $entity,
            )));
            
        } catch (SoapFault $err) { $this->dealWithError($err); }
        
        return $this;
    }
    
    
    /***
    * catalogProductAttributeMediaUpdate
    *
    
    <xsd:element minOccurs="1" maxOccurs="1" name="sessionId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="productId" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="file" type="xsd:string"/>
    <xsd:element minOccurs="1" maxOccurs="1" name="data" type="typens:catalogProductAttributeMediaCreateEntity"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="store" type="xsd:string"/>
    <xsd:element minOccurs="0" maxOccurs="1" name="identifierType" type="xsd:string"/>
    
    *
    *
    ***/
    function catalogProductAttributeMediaCreate($sku, $img) {
        
        echo "[".date('Y-m-d H:i:s')."] Update $sku ... ";
        
        try {
            
            $this->response = array('success' => $this->client->catalogProductAttributeMediaCreate(array(
                'sessionId' =>   $this->sessionId,
                'productId' =>   $sku,
                'file' =>        $img['name'],
                'data' =>        (object)array(
                    'file' => (object)array(
                        'content' => base64_encode(file_get_contents($img['url'])),
                        'mime'    => 'image/jpeg',
                        'name'    => $img['name']
                    ),
                    'position' => 1,
                    'types' => ( $img['main'] ? array('image','small_image','thumbnail') : array() ) /* Makes it the main image for the product */
                ),
                //'store' =>       '?',
                'identifierType'=>'sku'
            )));
            
        } catch (SoapFault $err) {
        
            echo $err->getMessage()."\n";
            
            if($this->dealWithError($err)->httpHeaders() || $this->dealWithError($err)->serverError() || $this->dealWithError($err)->dbLock()) {
                return $this->catalogProductAttributeMediaCreate($sku, $img);
            }
        }
        
        return $this;
    }
    //http://www.magentocommerce.com/api/soap/catalog/catalogProductCustomOption/product_custom_option.update.html
}