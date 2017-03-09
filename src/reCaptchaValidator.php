<?php
namespace Tvaliasek\Utils;
/**
 * Simple utility class for reCaptcha validation
 *
 * @author tvaliasek
 */
final class reCaptchaValidator {
	
	private $secret;
	const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';
	
	/**
	 * Google reCaptcha service secret
	 * @param string $secret
	 */
	public function __construct($secret) {
		$this->secret = $secret;
	}
	
	/**
	 * Validate reCaptcha response
	 * @param string $response
	 * @param string $remoteIp
	 * @return boolean true on success
	 */
	public function validate($response, $remoteIp = null){
		$client = new \GuzzleHttp\Client();
		$params = [
			'secret'=>$this->secret,
			'response'=>$response
		];
		if($remoteIp!==null){
			$params['remoteip'] = $remoteIp;
		}
		$apiResponse = $client->post(self::ENDPOINT, ['form_params'=>$params]);
		if($apiResponse->getStatusCode()==200){
			$json = json_decode((string) $apiResponse->getBody(), true);
			return ($json!==false && $json['success']==true) ? true : false;
		}
		return false;
	}
}
