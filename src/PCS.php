<?php

namespace Tomodomo\Client;

class PCS
{

	/**
	 * @var string
	 */
	var $url = 'https://subscribe.pcspublink.com/WSStatus_v1_1_6/WSStatus_v1_1_6.asmx';

	/**
	 * @param array $pubArgs
	 * @return void
	 */
	public function __construct( $pubcode, $password )
	{
		// WSDL URL
		$this->wsdl = $this->url . '?WSDL';

		// SOAP Client
		$this->client = new \SoapClient($this->wsdl);

		// Set pubArgs to be used in SOAP headers
		$this->pubArgs = [
			'PubCode'  => $pubcode,
			'Password' => $password,
		];

		return;
	}

	/**
	 * Get a SoapHeader with our arguments
	 *
	 * @param array $args
	 * @return SoapHeader
	 */
	public function getSoapHeader($name, $args = array())
	{
		$args = array_merge($this->pubArgs, $args);

		return new \SoapHeader($this->url, $name, $args);
	}

	/**
	 * Make a SOAP call and return a nice array
	 *
	 * @param string $name
	 * @param SoapHeader $header
	 * @return array
	 */
	public function makeSoapCall($name, $header)
	{
		// Make the SOAP API call to the client, with a header
		$response = $this->client->__soapCall($name, [], [], [ $header ]);

		// Get the Result
		// @todo this is probably v fragile
		$response = $response->{$name . 'Result'};

		// Return a parsed response
		return $this->getResponseAsArray($response);
	}

	/**
	 * Get the response as an array instead of an XML string
	 *
	 * @param string $response
	 * @return array
	 */
	public function getResponseAsArray($response = '')
	{
		// Load the XML
		$xml = simplexml_load_string($response);

		// Encode it as JSON
		$json = json_encode($xml);

		// Return it as an array
		return json_decode($json, true);
	}

	/**
	 * Check if a user is active from an email and password
	 *
	 * @param string $email
	 * @param string $password
	 * @return bool
	 */
	public function isUserActive($email, $password)
	{
		$args = [
			'UserName'     => $email,
			'UserPassword' => $password,
		];

		// Add args/auth header
		$header = $this->getSoapHeader('ProfileAuthenticationHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetIssuesFromProfile', $header);

		return $response['Status'] ? true : false;
	}

	/**
	 * Get a customer number from an email address, or 0 if
	 * no customer number is available
	 *
	 * @param string $email
	 * @return int
	 */
	public function getCustomerNumber($email)
	{
		$args = [
			'EmailAddr' => $email,
		];

		// Add args/auth header
		$header = $this->getSoapHeader('EmailAuthenticationHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetCustomerNumberFromEmail', $header);

		if (isset($response['CustomerNumber']) && is_numeric($response['CustomerNumber'])) {
			return $response['CustomerNumber'];
		}

		return 0;
	}

	/**
	 * Get a user's account info from their email address. User
	 * must have an assigned customer number
	 *
	 * @param string $email
	 * @return array
	 */
	public function getUserInfoFromEmail($email)
	{
		$customerNumber = $this->getCustomerNumber($email);

		return $this->getUserInfoFromCustomerNumber($customerNumber);
	}

	/**
	 * Get user info from a customer number
	 *
	 * @param string $customerNumber
	 * @return array
	 */
	public function getUserInfoFromCustomerNumber($customerNumber)
	{
		$args = [
			'CustomerInetNumber' => $customerNumber,
		];

		// Add args/auth header
		$header = $this->getSoapHeader('svc6CustInetAuthHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetStatusExpDateIssRem', $header);

		return $response;
	}

}
