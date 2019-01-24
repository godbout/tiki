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

	function action_client_modify($request)
	{
		$access = TikiLib::lib('access');
		$request = Helpers::tiki2Psr7Request($request);
		$access->check_permission('tiki_p_admin');
		$params = array('delete' => null);

		if ($request->getMethod() !== 'POST') {
			$response = new JsonResponse(405, [], '');
			return Helpers::processPsr7Response($response);
		}

		$oauthserverlib = TikiLib::lib('oauthserver');
		$repo = $oauthserverlib->getClientRepository();
		$params = array_merge($params, $request->getQueryParams());
		$client = ClientEntity::build($params);

		$response_content = null;
		$response_code = null;

		if($client->getIdentifier()) {
			if ($repo->exists($client)) {
				if ($params['delete'] === '1') {
					$repo->delete($client);
				} else {
					$repo->update($client);
				}
				$response_code = 200;
				$response_content = $client->toArray();
			} else {
				$response_code = 404;
				$response_content = ['error' => 'Client not found'];
			}
		} else if($params['delete'] !== '1') {
			$repo->create($client);
			$response_content = $client->toArray();
			$response_code = 201;
		} else {
			$response_code = 400;
			$response_content = ['error' => 'Bad request'];
		}

		$response = new JsonResponse($response_code, [], $response_content);
		return Helpers::processPsr7Response($response);
	}
}