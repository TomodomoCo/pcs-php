<?php

namespace Tomodomo\PcsPhp;

use function Stringy\create as s;

class Client
{

	/**
	 * @var string
	 */
	var $url = 'https://subscribe.pcspublink.com/WSStatus_v1_1_7/WSStatus_v1_1_7.asmx';

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
	 * Shorthand to see if a user is active
	 *
	 * @param string $email
	 * @param string $password
	 * @return bool
	 */
	public function isUserActive($email, $password)
	{
		$response = $this->authenticateUser($email, $password);

		// Check the status
		if ($response['Status'] === 'Success') {
			return true;
		}

		// Default to returning false
		return false;
	}

	/**
	 * Authenticate a user given an email and password
	 *
	 * @param string $email
	 * @param string $password
	 * @return array
	 */
	public function authenticateUser($email, $password)
	{
		$args = [
			'UserName'     => $email,
			'UserPassword' => $password,
		];

		// Add args/auth header
		$header = $this->getSoapHeader('ProfileAuthenticationHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetIssuesFromProfile', $header);

		// Handle failures
		if ($response['Status'] === 'Failed') {
			throw new ClientException($this->formatErrorMessage($response['errMsg']));
		}

		return $response;
	}

	/**
	 * Get a customer number from an email address, or 0 if
	 * no customer number is available
	 *
	 * @param string $email
	 * @return string
	 */
	public function getCustomerNumber($email)
	{
		$numbers = $this->getCustomerNumbers($email);

		// Return the first customer number for the email
		return $numbers[0];
	}

	/**
	 * Get all customer numbers for an email
	 *
	 * @param string $email
	 * @return array
	 */
	public function getCustomerNumbers($email)
	{
		$args = [
			'EmailAddr' => $email,
		];

		// Add args/auth header
		$header = $this->getSoapHeader('EmailAuthenticationHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetCustomerNumbersFromEmail', $header);

		// Skip directly to the customer numbers, maybe
		$numbers = $response['CustomerNumbers']['Customer'] ?? [];

		// If there aren't any customer numbers, return an empty array
		if (count($numbers) < 1) {
			throw new ClientException($this->formatErrorMessage($response['errMsg']));
		}

		// Extract the customer numbers
		if (isset($numbers['CustomerNumber'])) {
			$numbers = [ $numbers['CustomerNumber'] ];
		} else {
			// Multiple items so map the array
			$numbers = array_map(function ($number) {
				return $number['CustomerNumber'];
			}, $numbers);
		}

		// Return it all
		return $numbers;
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
		$header = $this->getSoapHeader('svc7CustInetAuthHeader', $args);

		// Get the response
		$response = $this->makeSoapCall('GetStatusAndCustInfo', $header);

		return $response;
	}

	/**
	 * Format an error message
	 *
	 * @param string $message
	 * @return string
	 */
	private function formatErrorMessage($message)
	{
		return (string) s($message)->toLowerCase()->upperCaseFirst();
	}
}
