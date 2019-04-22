<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

include TIKI_PATH . '/lib/auth/tokens.php';
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

		$response = new JsonResponse();
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
		$validation_errors = $repo->validate($client);

		if ($client->getId()) {
			if ($repo->exists($client)) {
				if ($params['delete'] === '1') {
					$repo->delete($client);
					$response_code = 200;
					$response_content = true;
				} else if (empty($validation_errors)) {
					$repo->update($client);
					$response_code = 200;
					$response_content = $client->toArray();
				} else {
					$response_code = 400;
					$response_content = $validation_errors;
				}
			} else {
				$response_code = 404;
				$response_content = ['error' => 'Client not found'];
			}
		} else if ($params['delete'] !== '1' && empty($validation_errors)) {
			$client->setClientId($repo::generateSecret(32));
			$client->setClientSecret($repo::generateSecret(64));
			$repo->create($client);
			$response_content = $client->toArray();
			$response_code = 201;
		} else {
			$response_code = 400;
			$response_content = $validation_errors;
		}

		$response = new JsonResponse($response_code, [], $response_content);
		return Helpers::processPsr7Response($response);
	}

	public function action_check($request)
	{
		global $prefs;
		$request = Helpers::tiki2Psr7Request($request);
		$params = $request->getQueryParams();
		$oauthserverlib = TikiLib::lib('oauthserver');

		if ($request->getMethod() !== 'GET') {
			$response = new JsonResponse(405, [], '');
			return Helpers::processPsr7Response($response);
		}

		$tokenlib = AuthTokens::build($prefs);
		$authorization = $request->getHeaderLine('Authorization') ?: '';
		$authorization = preg_split('/  */', $authorization);

		$valid = ! empty($params['auth_token'])
			&& count($authorization) === 2
			&& strcasecmp($authorization[0], 'Basic') === 0
			&& ! empty($authorization = base64_decode($authorization[1]));

		if (! $valid) {
			$response = new JsonResponse(400, [], 'Missing content');
			return Helpers::processPsr7Response($response);
		}

		list($client_id, $client_secret) = explode(':', $authorization);
		$repo = $oauthserverlib->getClientRepository();
		$client = $repo->get($client_id);

		if (! $client || $client->getClientSecret() !== trim($client_secret)) {
			$response = new JsonResponse(403, [], 'Invalid client');
			return Helpers::processPsr7Response($response);
		}

		$repo = $oauthserverlib->getAccessTokenRepository();
		$token = $repo->get($params['auth_token']);

		$valid = ! empty($token);
		$valid = $valid
			&& $token->getClient()->getIdentifier() == $client->getIdentifier();

		if (! $valid) {
			$response = new JsonResponse(403, [], 'Invalid token');
			return Helpers::processPsr7Response($response);
		}

		$response = new JsonResponse(200, [], 'ok');
		return Helpers::processPsr7Response($response);
	}
}
