<?php
require_once(dirname(__FILE__) . '/nusoap.php');

class EuleoRequest
{
	var $rows;
	var $client;
	var $usercode;
	var $handle;
	var $clientVersion = 2.0;
	var $cms = 'joomla';

	function EuleoRequest($customer, $usercode){
		$this->rows = array();
		$this->customer = $customer;
		$this->usercode = $usercode;

		if ($_SERVER['HTTP_HOST']=="ianus" || $_SERVER['SERVER_ADDR']=="192.168.1.10") {
			$this->host="http://192.168.1.10/euleoneu/public";
		} else {
			$this->host="https://www.euleo.com/";
		}
		
		$this->client  = new nusoapclient($this->host . '/soap/index?wsdl=1', true);
		
		$this->client->soap_defencoding="UTF-8";
		$this->client->decode_utf8 = false;
		
		if (!empty($customer) && !empty($usercode)) {
			$request = array();
			$request['customer'] = $this->customer;
			$request['usercode'] = $this->usercode;
			$request['clientVersion'] = $this->clientVersion;
			
			$requestXml = $this->_createRequest($request, 'connect');
			$responseXml = $this->client->call('connect', array('xml' => $requestXml));
			$response = $this->_parseXml($responseXml);
	
			if ($response['handle']) {
				$this->handle = $response['handle'];
			} else {
				$this->handle = false;
			}
		}
	}
	
	/**
	 * Returns a register token.
	 *
	 * Specify your cms-root and a return-url, to which you will be redirected after connecting
	 *
	 * @param string $cmsroot
	 * @param string $returnUrl
	 * @param string $callbackUrl
	 *
	 * @return string $token
	 */
	function getRegisterToken ($cmsroot, $returnUrl, $callbackUrl = '')
	{
		$request = array();
		$request['clientVersion'] = $this->clientVersion;
		$request['cms'] = $this->cms;
		$request['cmsroot'] = $cmsroot;
		$request['returnUrl'] = $returnUrl;
		$request['callbackUrl'] = $callbackUrl;
	
		$requestXml = $this->_createRequest($request, 'getRegisterToken');
	
		$responseXml = $this->client->call('getRegisterToken',
			array(
				'xml' => $requestXml
			));
	
		$response = $this->_parseXml($responseXml);
	
		return $response['token'];
	}
	
	/**
	 * Use this after the user has confirmed the connection prompt and been redirected back to get the customer info.
	 *
	 * @param string $token
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	function install ($token)
	{
		$request = array();
		$request['token'] = $token;
	
		$requestXml = $this->_createRequest($request, 'install');
	
		$responseXml = $this->client->call('getTokenInfo',
			array(
				'xml' => $requestXml
			));
	
		$response = $this->_parseXml($responseXml);
	
		if (empty($response['data'])) {
			throw new Exception($response['errors']);
		}
	
		return $response['data'];
	}
	
	
	/**
	 * Returns the data of the currently connected Euleo customer.
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	function getCustomerData ()
	{
		$request = array();
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'getCustomerData');
	
		$responseXml = $this->client->call('getCustomerData',
			array(
				'xml' => $requestXml
			));
	
		$response = $this->_parseXml($responseXml);
	
		if (empty($response['data'])) {
			throw new Exception($response['errors']);
		}
	
		return $response['data'];
	}
	
	function connected()
	{
		return $this->handle != false;
	}
	
	function setLanguageList($languageList)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['languageList'] = $languageList;
		
		$requestXml = $this->_createRequest($request, 'setLanguageList');
		$responseXml = $this->client->call('setLanguageList', array('xml' => $requestXml));
		$response = $this->_parseXml($responseXml);

		return $response;
	}


	function addRow($row){
		$this->rows[] = $row;
	}

	function startEuleoWebsite(){
		header("Location: " . $this->host . "/business/auth/index/handle/" . $this->handle);
		exit;
	}

	function getRows(){
	    $request = array();
	    $request['handle'] = $this->handle;

	    $requestXml = $this->_createRequest($request, 'getRows');
	    
		$responseXml = $this->client->call('getRows', array('xml' => $requestXml));

		$response = $this->_parseXml($responseXml);

		if (!$response['rows']){			
			echo 'No translations available at this time.';
		}

		return $response['rows'];
	}

	function sendRows(){
		if ($this->rows) {
		    $request = array();
		    $request['handle'] = $this->handle;
		    $request['cms'] = $this->cms;
		    $request['rows'] = $this->rows;

		    $requestXml = $this->_createRequest($request, 'sendRows');
		    
		    $responseXml = $this->client->call('sendRows', array('xml' => $requestXml));
		    
		    $response = $this->_parseXml($responseXml);
		    
		   	return $response;
		}
	}

	function confirmDelivery($translationids){
		$request['handle'] = $this->handle;
		$request['translationIdList'] = implode(',', $translationids);
		$requestXml = $this->_createRequest($request, 'confirmDelivery');
		
		$responseXml = $this->client->call('confirmDelivery', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}

	
	function getCart()
	{
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'getCart');
		
		$responseXml = $this->client->call('getCart', array('xml' => $requestXml));
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}
	
	function clearCart()
	{
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'clearCart');
		
		$responseXml = $this->client->call('clearCart', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		return $response;
	}
	
	function addLanguage($code, $language)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['code'] = $code;
		$request['language'] = $language;
		
		$requestXml = $this->_createRequest($request, 'addLanguage');
		
		$responseXml = $this->client->call('addLanguage', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		return $response;
	}
	
	function removeLanguage($code, $language)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['code'] = $code;
		$request['language'] = $language;
		
		$requestXml = $this->_createRequest($request, 'removeLanguage');
		
		$responseXml = $this->client->call('removeLanguage', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}
	
	function identifyLanguages($texts)
    {
    	$request = array();
		$request['handle'] = $this->handle;
		$request['texts'] = $texts;
		
		$requestXml = $this->_createRequest($request, 'getLanguages');
		
		$responseXml = $this->client->call('identifyLanguages', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
    }
	
	function _createRequest($data, $action)
    {
        if (!is_array($data)) {
            return false;
        }
        
        $xml = array();
        $xml[] = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml[] = '<request action="' . $action . '">';
        
        $xml[] = EuleoRequest::_createRequest_sub($data);
        
        $xml[] = '</request>';
        
        
        $xmlstr = implode("\n", $xml);
        
        return $xmlstr;
    }
    
    function _createRequest_sub($data, $parentKey = '')
    {
        $xml = array();
        
        foreach ($data as $key => $value) {
            if (!is_numeric($key)) {
	            $xml[] = '<' . $key . '>';
	            if (is_array($value)) {
	                $xml[] = EuleoRequest::_createRequest_sub($value, $key);
	            } else {
	                $xml[] = '<![CDATA[' . trim($value) . ']]>';
	            }
	            $xml[] = '</' . $key . '>';
            } else {
                $xml[] = EuleoRequest::_rowToXml_sub($value);
            }
        }
        
        $xmlstr = implode("\n", $xml);
        return $xmlstr;
    }
    
    function _rowToXml_sub($row)
    {
        $lines=array();
                
        $lines[] = '<row id="' . htmlspecialchars($row['code'], ENT_COMPAT, 'UTF-8') . 
                   '" label="' . htmlspecialchars($row['label'], ENT_COMPAT, 'UTF-8') . 
                   '" title="' . htmlspecialchars($row['title'], ENT_COMPAT, 'UTF-8') . 
                   ($row['url'] ? '" url="' . htmlspecialchars($row['url'], ENT_COMPAT, 'UTF-8') : '') .
        		   '">';
        
        foreach ($row as $key => $value) {
            if ($key != 'fields' && $key != 'rows') {
                $lines[] = '<' . $key . '><![CDATA[' . $value . ']]></' .$key .'>';
            }
        }
        
        if ($row['fields']) {
            $lines[] = '<fields>';
    
            foreach ($row['fields'] as $fieldname => $field) {
    
                $label = $field['label'];
    
                if ($label == '') {
                    $label = ucfirst($fieldname);
                }
                $lines[] = '<field name="' . $fieldname . '" label="' . $label . '" type="' . 
                           $field['type'] . '">';
    
                $lines[] = '<![CDATA[';
                $lines[] = $field['value'];
                $lines[] = ']]>';
    
                $lines[]='</field>';
    
            }
            $lines[]='</fields>';
        }
    
        if (isset($row['rows'])) {
            $lines[] = '<rows>';
            foreach ($row['rows'] as $childrow) {
                $lines[] = EuleoRequest::_rowToXml_sub($childrow);
            }
            $lines[] = '</rows>';
        }
    
        $lines[] = '</row>';

        $xmlstr = implode("\n", $lines);

        return $xmlstr;
    }
    
    function _parseXml($xml)
    {
        if (!$xml) {
            return false;
        }

        $return = array();

		require_once('simplexml.class.php');
		
		$sxml = new simplexml();
		$node = $sxml->xml_load_file($xml, 'array', 'utf-8');
        
        $return = EuleoRequest::_parseXml_sub($node);
                
        return $return;
    }
    
    function _parseXml_sub($rownode)
    {
    	if (is_array($rownode['@attributes'])) {
    		foreach ($rownode['@attributes'] as $key => $value) {
    			$row[$key] = trim((string) $value);
    		}
    	}
        
        foreach ($rownode as $name => $child) {
            if ($name != 'fields' && $name != 'rows' && $name != 'requests' && $name != '@attributes') {
            	if (is_array($child)) {
            		$row[$name] = $child;
            	} else {
            		$row[$name] = trim((string)$child);
            	}
            }
        }
        
        if ($rownode['fields']['field']) {
        	if (isset($rownode['fields']['field'][0])) {
        		$fields = $rownode['fields']['field'];
        	} else {
        		$fields = array($rownode['fields']['field']);
        	}
        	foreach ($fields as $fieldnode) {
        		$field = array();
	            foreach ($fieldnode['@attributes'] as $key => $value) {
	                $field[$key] = trim((string) $value);
	                if ($key == 'name'){
	                    $fieldname = trim((string) $value);
	                }
	            }
	            $field['value'] = trim((string) $fieldnode['@content']);
	            $row['fields'][$fieldname] = $field;
        	}
        }
        if ($rownode['rows']) {
            $row['rows'] = array();
            
            if (isset($rownode['rows']['row'][0])) {
            	$rows = $rownode['rows']['row'];
            } else {
            	$rows = array($rownode['rows']['row']);
            }
            
            foreach ($rows as $childrownode) {
                $row['rows'][] = EuleoRequest::_parseXml_sub($childrownode);
            }
        }
        if ($rownode['requests']) {
            $row['requests'] = array();
            
            if (isset($rownode['requests']['request'][0])) {
            	$rows = $rownode['requests']['request'];
            } else {
            	$rows = array($rownode['requests']['request']);
            }
            
            foreach ($rows as $childrownode) {
                $row['requests'][] = EuleoRequest::_parseXml_sub($childrownode);
            }
        }

        return $row;
    }
}
