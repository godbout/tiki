<?php
include TIKI_PATH . '/lib/oauthserver/helpers.php';
include TIKI_PATH . '/lib/core/Services/OAuthServer/JsonResponse.php';

class Services_OAuthServer_Controller
{
	public function setUp()
	{
		Services_Exception_Disabled::check('auth_token_access');
	}

	/*
	 * OAuth protocol actions
	 */

 	/**
	 * Attempt to validate client app request for authorization. On success,
	 * it redirect client to an URL with access_token or authorization_code
	 * informed in querystring
	 */
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


	function action_client_update($request)
	{
		$access = TikiLib::lib('access');
		$request = Helpers::tiki2Psr7Request($request);
		$access->check_permission('tiki_p_admin');

		if ($request->getMethod() !== 'POST') {
			$response = new JsonResponse(405, [], '');
			return Helpers::processPsr7Response($response);
		}

		$oauthserverlib = TikiLib::lib('oauthserver');
		$repo = $oauthserverlib->getClientRepository();
		$client = ClientEntity::build($request->getQueryParams());

		if(! $client->getIdentifier()) {
			$response = new JsonResponse(400, [], '');
			return Helpers::processPsr7Response($response);
		}

		$response = new JsonResponse(200, [], $client->toArray());
		return Helpers::processPsr7Response($response);
	}
}