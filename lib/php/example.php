<?php
	require('premium-api.php');

	define('API_KEY', '1234567890abcdef');
	define('API_CAMPAIGN', 'demo');

	function error_output(PremiumAPI $APIObject)
	{
		if ($APIObject -> ErrNo)
		{
			echo 'Error #'.$APIObject -> ErrNo.': '.$APIObject -> Error;
		}
	}

	function results_output($Data)
	{
		echo '<pre>'.print_r($Data, 1).'</pre>';
		echo '<hr />';
	}

	function debug_output(PremiumAPI $APIObject)
	{
		echo '<pre>'.print_r($APIObject -> Debug, 1).'</pre>';
	}

	$PremiumAPI = new PremiumAPI(API_KEY, API_CAMPAIGN);

	// Retrieving campaign information
	echo '<h2>Campaign information</h2>';
	$Info = $PremiumAPI -> Info_Get();
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Info);

	// Statistics
	echo '<h2>Campaign statistics</h2>';
	$Stats = $PremiumAPI -> Statistics_General();
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Stats);

	// Messages
	echo '<h2>Message list retrieval</h2>';
	$Messages = $PremiumAPI -> Messages_List([
		'Time' => date('c', strtotime('2010-02-07'))
	], [
		'Time' => date('c', strtotime('2010-02-09'))
	]);
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Messages);

	echo '<h2>Single message</h2>';
	$Message = $PremiumAPI -> Messages_Get(93248);
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Message);

	echo '<h2>Create a new message</h2>';
	$Message = $PremiumAPI -> Messages_Create([
		'Phone' => 21234567,
		'FirstName' => 'George',
		'LastName' => 'Brown',
		'ReceiptUnique' => '123/456',
		'IP' => $_SERVER['REMOTE_ADDR']
	],
	[ // Attachments
		['tmp_name' => getcwd().'/example.php', 'type' => 'text/php', 'name' => 'example.php'],
		['tmp_name' => getcwd().'/premium-api.php', 'type' => 'text/php', 'name' => 'premium-api.php'],
	]);
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Message);

	echo '<h2>Create a new message with multiple codes</h2>';
	$Message = $PremiumAPI -> Messages_Create([
		'Phone' => 21234567,
		'FirstName' => 'George',
		'LastName' => 'Brown',
		'ReceiptUnique' => ['123/456', '321/654', '654/321'],
		'IP' => $_SERVER['REMOTE_ADDR']
	],
	[ // Attachments
		['tmp_name' => getcwd().'/example.php', 'type' => 'text/php', 'name' => 'example.php'],
		['tmp_name' => getcwd().'/premium-api.php', 'type' => 'text/php', 'name' => 'premium-api.php'],
	]);
	debug_output($PremiumAPI);
	error_output($PremiumAPI);
	results_output($Message);
