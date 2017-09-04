<?php

namespace GsJohn\Dysms\Core\Profile;

use GsJohn\Dysms\Core\Auth\Credential;
use GsJohn\Dysms\Core\Auth\ShaHmac1Signer;
use GsJohn\Dysms\Core\Regions\ProductDomain;
use GsJohn\Dysms\Core\Regions\Endpoint;
use GsJohn\Dysms\Core\Regions\EndpointProvider;

class DefaultProfile implements IClientProfile
{
	private static $profile;
	private static $endpoints;
	private static $credential;
	private static $regionId;
	private static $acceptFormat;
	
	private static $isigner;
	private static $iCredential;
	
	private function  __construct($regionId,$credential)
	{
	    static::$regionId = $regionId;
	    static::$credential = $credential;
	}
	
	public static function getProfile($regionId, $accessKeyId, $accessSecret)
	{
		$credential =new Credential($accessKeyId, $accessSecret);
		static::$profile = new DefaultProfile($regionId, $credential);
		return static::$profile;
	}
	
	public function getSigner()
	{
		if(null == static::$isigner)
		{
			static::$isigner = new ShaHmac1Signer(); 
		}
		return static::$isigner;
	}
	
	public function getRegionId()
	{
		return static::$regionId;
	}
	
	public function getFormat()
	{
		return static::$acceptFormat;
	}
	
	public function getCredential()
	{
		if(null == static::$credential && null != static::$iCredential)
		{
			static::$credential = static::$iCredential;
		}
		return static::$credential;
	}
	
	public static function getEndpoints()
	{
		if(null == static::$endpoints)
		{
			static::$endpoints = EndpointProvider::getEndpoints();
		}
		return static::$endpoints;
	}
	
	public static function addEndpoint($endpointName, $regionId, $product, $domain)
	{
		if(null == static::$endpoints)
		{
			static::$endpoints = static::getEndpoints();
		}
		$endpoint = static::findEndpointByName($endpointName);
		if(null == $endpoint)
		{
			static::addEndpoint_($endpointName, $regionId, $product, $domain);
		}
		else 
		{
			static::updateEndpoint($regionId, $product, $domain, $endpoint);
		}
	}
	
	public static function findEndpointByName($endpointName)
	{
		if (null === static::$endpoints) return null;

		foreach (static::$endpoints as $key => $endpoint)
		{
			if($endpoint->getName() == $endpointName)
			{
				return $endpoint;
			}
		}
	}
	
	private static function addEndpoint_($endpointName,$regionId, $product, $domain)
	{
		$regionIds = array($regionId);
		$productDomains = array(new ProductDomain($product, $domain));
		$endpoint = new Endpoint($endpointName, $regionIds, $productDomains);
		array_push(static::$endpoints, $endpoint);
	}
	
	private static function updateEndpoint($regionId, $product, $domain, $endpoint)
	{
		$regionIds = $endpoint->getRegionIds();
		if(!in_array($regionId,$regionIds))
		{
			array_push($regionIds, $regionId);
			$endpoint->setRegionIds($regionIds);
		}

		$productDomains = $endpoint->getProductDomains();
		if(null == static::findProductDomain($productDomains, $product, $domain))
		{
		 	array_push($productDomains, new ProductDomain($product, $domain));	
		}
		$endpoint->setProductDomains($productDomains);
	}
	
	private static function findProductDomain($productDomains, $product, $domain)
	{
		foreach ($productDomains as $key => $productDomain)
		{
			if($productDomain->getProductName() == $product && $productDomain->getDomainName() == $domain)
			{
				return $productDomain;
			}
		}
		return null;
	}

}