<?php
	
	require_once(dirname(__FILE__)."/vendor/autoload.php");

	$apiresponse = array();
	$apiresponse["status"] = "ok";
	$apiresponse["fieldErrors"] = array();	
	$amount = 399;
	
	// Set your secret key: remember to change this to your live secret key in production
	// See your keys here: https://dashboard.stripe.com/account/apikeys
	\Stripe\Stripe::setApiKey("sk_test_xxxxxxxxxxxxxxxxxxxxxxx");


	try{
		$token = \Stripe\Token::create([
		  "card" => [
			"number" => $_POST["card"]['number'],
			"exp_month" => $_POST["card"]['exp_month'],
			"exp_year" => $_POST["card"]['exp_year'],
			"cvc" => $_POST["card"]['cvc'],
			"address_zip" => $_POST["card"]['zip'],
		  ]
		]);

		$charge = \Stripe\Charge::create([
			'amount' => $amount*100,
			'currency' => 'usd',
			'description' => 'Example charge',
			'source' => $token->id,
		]);
	}
	catch(\Stripe\Error\Card $e) {
	  // Since it's a decline, \Stripe\Error\Card will be caught
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (\Stripe\Error\RateLimit $e) {
	  // Too many requests made to the API too quickly
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (\Stripe\Error\InvalidRequest $e) {
	  // Invalid parameters were supplied to Stripe's API
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (\Stripe\Error\Authentication $e) {
	  // Authentication with Stripe's API failed
	  // (maybe you changed API keys recently)
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (\Stripe\Error\ApiConnection $e) {
	  // Network communication with Stripe failed
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (\Stripe\Error\Base $e) {
	  // Display a very generic error to the user, and maybe send
	  // yourself an email
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	} catch (Exception $e) {
	  // Something else happened, completely unrelated to Stripe
	  $apiresponse = genericHandleError($e);
	  $valid = false;
	}


	if($valid && $charge->outcome->network_status != 'approved_by_network')
	{			
		$apiresponse["status"] = "error";
		$apiresponse["message"] = $charge->outcome->reason;
		$apiresponse["response"] = $charge->outcome->seller_message;
		$apiresponse["transaction_id"] = $charge->balance_transaction;
		$valid = false;
	}

	function genericHandleError($e)
	{
		$apiresponse = array();
		$body = $e->getJsonBody();
		$err  = $body['error'];
		$apiresponse["status"] = "error";
		$apiresponse["message"] = $err['message'];
		$apiresponse["response"] = $err['type'];
		return $apiresponse;
	}

	echo json_encode($apiresponse);
	
?>