<?php
/**
 * Utility class for Premium API connection
 *
 * @version 1.0.2
 */
class PremiumAPI
{
	private static $Version = '1.0.1';
	private static $UserAgent = 'SalesLV/Premium-API';
	private static $VerifySSL = false;

	// Error constants
	// No error
	const ERROR_NONE = 0;
	// API key not recognized or not allowed with the current IP address and campaign
	const ERROR_UNAUTHORIZED = 1;
	// Response from Premium was invalid, cannot be parsed
	const ERROR_INVALID_RESPONSE = 2;
	// Response from Premium was empty, no data contained (something should be there always for valid requests)
	const ERROR_EMPTY_RESPONSE = 3;
	// Request from this library was empty for some reason
	const ERROR_EMPTY_REQUEST = 4;
	// Command was not recognized
	const ERROR_UNKNOWN_COMMAND = 5;
	// No data was found with specified parameters
	const ERROR_NO_DATA_FOUND = 6;
	// An error has happened with HTTP request to Premium
	const ERROR_REQUEST = 7;
	// No library for HTTP requests available
	const ERROR_CANNOT_MAKE_HTTP_REQUEST = 8;
	// Some or all of mandatory parameters were not provided in the API call
	const ERROR_INSUFFICIENT_PARAMETERS = 9;
	// A forbidden operation was tried
	const ERROR_FORBIDDEN = 10;
	// Request did not contain expected mandatory parameters
	const ERROR_REQUIRED_PARAMETERS_MISSING = 11;
	// For campaigns where value uniqueness is checked (for example, receipt numbers in lotteries) a supposedly unique value was already
	//	registered.
	const ERROR_UNIQUE_PARAM_NOT_UNIQUE = 12;
	// Message was submitted before campaign start
	const ERROR_BEFORE_CAMPAIGN_START = 13;
	// Message was submitted after campaign end
	const ERROR_AFTER_CAMPAIGN_END = 14;
	// Could not save transmitted data
	const ERROR_COULDNT_SAVE = 15;

	/**
	 * @var string API endpoint URL
	 */
	private static $URL = 'https://premium.sales.lv/API:1.0/';

	/**
	 * @var string Premium API key.
	 */
	private $APIKey = '';
	/**
	 * @var string Campaign code to use with API calls.
	 */
	private $CampaignCode = '';
	/**
	 * @var string Base URL for API calls
	 */
	private $APIURL = '';
	/**
	 * @var ERROR_* Error code, one of PremiumAPI::ERROR_* constants
	 */
	private $ErrNo = 0;
	/**
	 * @var string Human-readable error message
	 */
	private $Error = '';

	public $Debug = array(
		'LastHTTPRequest' => array(
			'URL' => '',
			'Request' => array(),
			'Response' => array()
		)
	);

	// !Public utility methods

	/**
	 * Constructor
	 * @var string API key, it should be provided to you along with the rest of the account data.
	 * @var string Campaign code, it too should be provided with the rest of the account data.
	 */
	public function __construct($Key, $CampaignCode)
	{
		$this -> APIKey = $Key;
		$this -> CampaignCode = $CampaignCode;

		$this -> APIURL = self::$URL.'Key:'.$this -> APIKey.'/Code:'.$this -> CampaignCode.'/';
	}

	public function __get($Name)
	{
		if ($Name == 'Error' || $Name == 'ErrNo' || $Name == 'Debug')
		{
			return $this -> {$Name};
		}
		return null;
	}

	// !API calls
	// !General information
	/**
	 * Retrieves general information about the campaign. No parameters required.
	 *
	 * @return array Associative array with details
	 */
	public function Info_Get()
	{
		$Data = $this -> HTTPRequest($this -> APIURL.'Info:Get');
		return $this -> ParseResponse($Data);
	}

	// !Statistics
	/**
	 * Retrieves general statistics and aggregate counts from the campaign, no parameters required
	 *
	 * @return array Associative array with data
	 */
	public function Statistics_General()
	{
		$Data = $this -> HTTPRequest($this -> APIURL.'Statistics:General');
		return $this -> ParseResponse($Data);
	}

	// !Messages
	/**
	 * Method for retrieving a single message
	 *
	 * @param int Message ID
	 *
	 * @return array Associative array with message data or boolean false if message was not found or inaccessible
	 */
	public function Messages_Get($ID)
	{
		$Data = $this -> HTTPRequest($this -> APIURL.'Messages:Get/ID:'.(int)$ID);
		return $this -> ParseResponse($Data);
	}


	/**
	 * Method for retrieving a message list from a campaign by the given parameters
	 *
	 * @param array Associative array with the parameters to filter by. Each value can be a single value or an array of multiple values.
	 * @param array Another array with parameters in case it is necessary to filter values in a specific range.
	 *	In that case the first array should contain the smaller value and the second one should contain the larger one (or the other way around).
	 *	If there is a value in both arrays and one or both of them are arrays (to retrieve multiple items), undefined behavior may occur.
	 * @param int Offset from the start of the list (the amount of messages returned in one turn is limited, hence the offset).
	 *
	 * @return array List of messages (an array of associative arrays)
	 */
	public function Messages_List(array $Parameters, array $Parameters2 = null, $Offset = 0)
	{
		$Data = $this -> HTTPRequest(
			$this -> APIURL.'Messages:List',
			array(
				'Filter1' => json_encode($Parameters),
				'Filter2' => $Parameters2 ? json_encode($Parameters2) : false,
				'Offset' => (int)$Offset
			)
		);

		return $this -> ParseResponse($Data);
	}

	/**
	 * Method for submitting a new message
	 *
	 * @param array Message parameters
	 *
	 * @return array Operation result
	 */
	public function Messages_Create(array $Parameters)
	{
		if (empty($Parameters['IP']))
		{
			$Parameters['IP'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		}

		$Result = $this -> HTTPRequest(
			$this -> APIURL.'Messages:Create',
			$Parameters
		);

		return $this -> ParseResponse($Result);
	}

	// !Public utility methods

	// !Private utility methods
	private function ParseResponse($Response)
	{
		if (!is_array($Response))
		{
			// $this -> SetError(self::ERROR_INVALID_RESPONSE, 'Invalid response from Premium, cannot parse');
			return false;
		}

		$this -> SetError(self::ERROR_NONE, '');

		$Body = false;
		if ($Response['Body'])
		{
			$Body = json_decode($Response['Body'], true);
		}

		if (!$Response['Body'])
		{
			$this -> SetError(self::ERROR_EMPTY_RESPONSE, 'Empty response from Premium');
		}
		elseif (!$Body)
		{
			$ErrorMessage = 'Invalid response from Premium, cannot parse';
			if (is_null($Body))
			{
				// JSON parsing error
				$ErrorMessage = 'JSON parsing error'.(function_exists('json_last_error') ? ' #'.json_last_error() : '');
			}

			$this -> SetError(self::ERROR_INVALID_RESPONSE, $ErrorMessage);
		}
		elseif (!empty($Body['ErrNo']))
		{
			$this -> SetError($Body['ErrNo'], $Body['Error']);
		}

		return $Body;
	}

	private function SetError($ErrorCode, $ErrorMessage)
	{
		$this -> ErrNo = $ErrorCode;
		$this -> Error = $ErrorMessage;

		return null;
	}

	// !HTTP request utilities
	/**
	 * Utility method for making HTTP requests (used to abstract the HTTP request implementation)
	 *	pecl_http extension is recommended, however, if it is not available, the request will be made by other means.
	 *
	 * @param string URL to make the request to
	 * @param array POST data if it is a POST request. If this is empty, a GET request will be made, if populated - POST. Optional.
	 * @param array Additional headers to pass to the service, optional.
	 *
	 * @return array Array containing response data: array(
	 *	'Code' => int HTTP status code (200, 403, etc.),
	 *	'Headers' => array Response headers
	 *	'Content' => string Response body 
	 * )
	 */
	private function HTTPRequest($URL, array $POSTData = null, array $Headers = null)
	{
		$this -> Debug['LastHTTPRequest']['URL'] = $URL;
		$this -> Debug['LastHTTPRequest']['Method'] = $POSTData ? 'POST' : 'GET';
		$this -> Debug['LastHTTPRequest']['Request'] = $POSTData;
		$this -> Debug['LastHTTPRequest']['Response'] = '';

		$Result = array();

		try
		{
			if (extension_loaded('http'))
			{
				$Result = self::HTTPRequest_http($URL, $POSTData, $Headers);
			}
			elseif (extension_loaded('curl'))
			{
				$Result = self::HTTPRequest_curl($URL, $POSTData, $Headers);
			}
			elseif (ini_get('allow_url_fopen'))
			{
				$Result = self::HTTPRequest_fopen($URL, $POSTData, $Headers);
			}
			else
			{
				return $this -> SetError(self::ERROR_CANNOT_MAKE_HTTP_REQUEST, 'No means to make a HTTP request are available (pecl_http, curl or allow_url_fopen)');
			}
		}
	  	catch (Exception $E)
	  	{
	  		$this -> SetError(self::ERROR_REQUEST, $E -> getMessage());

		  	return false;
	  	}

	  	$this -> Debug['LastHTTPRequest']['Response'] = $Result;

		return $Result;
	}

	/**
	 * Utility method for making HTTP requests with the pecl_http extension, see HTTPRequest for more information
	 */
	private static function HTTPRequest_http($URL, array $POSTData = null, array $Headers = null)
	{
		$Method = $POSTData ? HttpRequest::METH_POST : HttpRequest::METH_GET;

  		$Request = new HttpRequest($URL, $Method);
  		if ($Headers)
  		{
  			$Request -> setHeaders($Headers);
  		}
  		$Request -> setPostFields($POSTData);

  		$Request -> send();

  		return array(
  			'Headers' => array_merge(
  				array(
	  				'Response Code' => $Request -> getResponseCode(),
	  				'Response Status' => $Request -> getResponseStatus()
	  			),
	  			$Request -> getResponseHeader()
  			),
  			'Body' => $Request -> getResponseBody()
  		);
	}

	/**
	 * Utility method for making HTTP requests with CURL. See PremiumAPI::HTTPRequest for more information
	 */
	private static function HTTPRequest_curl($URL, array $POSTData = null, array $Headers = null)
	{
		// Preparing request content
		$POSTBody = $POSTData ? self::PrepareBody($POSTData) : '';

		// Preparing request headers
		$Headers = self::PrepareHeaders($Headers, $URL, strlen($POSTBody));

		// Making the request
		$cURLRequest = curl_init();
		curl_setopt_array($cURLRequest, array(
			CURLOPT_URL => $URL, 
			CURLOPT_HEADER => 1,
			CURL_HTTP_VERSION_1_0 => true,
			CURLOPT_POST => $POSTBody ? 1 : 0,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_USERAGENT => self::$UserAgent.'/'.self::$Version,
			CURLOPT_POSTFIELDS => $POSTBody,
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER => $Headers,
			CURLOPT_SSL_VERIFYPEER => self::$VerifySSL
		));
		$ResponseBody = curl_exec($cURLRequest);
		curl_close($cURLRequest);

		$ResponseBody = str_replace(array("\r\n", "\n\r", "\r"), array("\n", "\n", "\n"), $ResponseBody);
		$ResponseParts = explode("\n\n", $ResponseBody);

		$ResponseHeaders = array();
		if (count($ResponseParts) > 1)
		{
			$ResponseHeaders = self::ParseHeadersFromString($ResponseParts[0]);
		}

		$ResponseBody = isset($ResponseParts[1]) ? $ResponseParts[1] : $ResponseBody;
		//if (isset($ResponseHeaders['Content-Encoding']) && $ResponseHeaders['Content-Encoding'] == 'gzip')
		//{
			//$ResponseBody = gzinflate($ResponseBody);
		//}

		return array(
			'Headers' => $ResponseHeaders,
			'Body' => $ResponseBody
		);
	}

	/**
	 * Utility method for making the HTTP request with file_get_contents. See PremiumAPI::HTTPRequest for more information
	 */
	private static function HTTPRequest_fopen($URL, array $POSTData = null, array $Headers = null)
	{
		// Preparing reqiest body
		$POSTBody = $POSTData ? self::PrepareBody($POSTData) : '';

		// Preparing headers
		$Headers = self::PrepareHeaders($Headers, $URL, strlen($POSTBody));
		$Headers = implode("\r\n", $Headers)."\r\n";

		// Making the request
		$Context = stream_context_create(array(
			'http' => array(
				'method' => $POSTBody ? 'POST' : 'GET',
				'header' => $Headers,
				'content' => $POSTBody,
				'protocol_version' => 1.0
			)
		));

		$Content = file_get_contents($URL, false, $Context);

		$ResponseHeaders = $http_response_header;
		$ResponseHeaders = self::ParseHeadersFromArray($ResponseHeaders);

		return array(
			'Headers' => $ResponseHeaders,
			'Body' => $Content
		);
	}

	/**
	 * Utility for HTTP requests to prepare header arrays
	 *
	 * @param array Headers to send in addition to the default set (keys are names, values are content)
	 * @param string URL that will be used for the request (for the "Host" header)
	 * @param int Content length for the Content-Length header
	 *
	 * return array Headers in a numeric array. Each item in the array is a separate header string containing both name and content
	 */
	private static function PrepareHeaders(array $Headers = null, $URL, $ContentLength)
	{
		$URLInfo = parse_url($URL);
		$Host = $URLInfo['host'];

		$DefaultHeaders = array(
			'Host' => $Host,
			'Connection' => 'close',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Content-Length' => $ContentLength,
			'User-Agent' => self::$UserAgent.'/'.self::$Version
		);

		if ($Headers)
		{
			$Headers = array_merge($DefaultHeaders, $Headers);
		}
		else
		{
			$Headers = $DefaultHeaders;
		}

		$Result = array();
		foreach ($Headers as $Name => $Content)
		{
			$Result[] = $Name.': '.$Content;
		}
		return $Result;
	}

	/**
	 * Prepares POST request body content for sending
	 *
	 * @param array Data to send
	 *
	 * @return string Body content suitable for a HTTP request
	 */
	private static function PrepareBody(array $Data)
	{
		$POSTBody = array();
		foreach ($Data as $Key => $Value)
		{
			$POSTBody[] = $Key.'='.urlencode($Value);
		}
		return implode('&', $POSTBody);
	}

	/**
	 * Parses raw HTTP header text into an associative array
	 *
	 * @param string Raw header text
	 *
	 * @return array Associative array with header data. Two additional elements are created:
	 *	- Response Status: Status message, for example, "OK" for requests with 200 status code
	 *	- Response Code: The numeric status code - 200, 301, 401, 503, etc.
	 */
	private static function ParseHeadersFromString($HeaderString)
	{
		if (function_exists('http_parse_headers'))
		{
			$Result = http_parse_headers($HeaderString);
		}
		else
		{
			$Headers = explode("\n", $HeaderString);

			$Result = self::ParseHeadersFromArray($Headers);
		}
	
		return $Result;
	}

	/**
	 * Parses raw header array into an associative array.
	 *
	 * @param array Array containing the headers
	 *
	 * @return array Associative array with header data. Two additional elements are created:
	 *	- Response Status: Status message, for example, "OK" for requests with 200 status code
	 *	- Response Code: The numeric status code - 200, 301, 401, 503, etc.
	 */
	private static function ParseHeadersFromArray(array $Headers)
	{
		$Result = array();

		$CurrentHeader = 0;

		foreach ($Headers as $Index => $RawHeader)
		{
			if ($Index == 0 || strpos($RawHeader, 'HTTP/') === 0)
			{
				// HTTP status headers could be repeated on further lines if any redirects are encountered.
				list($Discard, $StatusCode, $Status) = explode(' ', $RawHeader, 3);
				$Result['Response Code'] = $StatusCode;
				$Result['Response Status'] = $Status;

				continue;
			}

			$RawHeader = explode(':', $RawHeader, 2);

			if (count($RawHeader) > 1)
			{
				$CurrentHeader = trim($RawHeader[0]);
				$Result[$CurrentHeader] = trim($RawHeader[1]);
			}
			elseif (count($RawHeader) == 1)
			{
				$Result[$CurrentHeader] .= ' '.trim($RawHeader[0]);
			}
			else
			{
				$CurrentHeader = false;
			}
		}

		return $Result;
	}
}
?>