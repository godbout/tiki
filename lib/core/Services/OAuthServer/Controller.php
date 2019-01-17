<?php
include TIKI_PATH . '/lib/oauthserver/helpers.php';
use \GuzzleHttp\Psr7\Response;

class Services_OAuthServer_Controller
{
	public function setUp()
	{
		Services_Exception_Disabled::check('auth_token_access');
	}

	function action_authorize($request)
	{
		$oauthserverlib = TikiLib::lib('oauthserver');

		$server = $oauthserverlib
			->determineServerGrant()
			->getServer();

		$userEntity = $oauthserverlib->getUserEntity();
		$request = Helpers::tiki2Psr7Request($request);

		$authRequest = $server->validateAuthorizationRequest($request);
		$authRequest->setUser($userEntity);
		$authRequest->setAuthorizationApproved(true);

		$response = new Response();
		$response = $server->completeAuthorizationRequest($authRequest, $response);
		Helpers::processPsr7Response($response);
	}
}