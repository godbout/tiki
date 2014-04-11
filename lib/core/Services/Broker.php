<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Services_Broker
{
	private $container;

	function __construct(array $container)
	{
		$this->container = $container;
	}

	function process($controller, $action, JitFilter $request)
	{
		$access = TikiLib::lib('access');

		try {
			$this->preExecute();

			$output = $this->attemptProcess($controller, $action, $request);

			if (isset($output['FORWARD'])) {
				$output['FORWARD'] = array_merge(
					array(
						'controller' => $controller,
						'action' => $action,
					),
					$output['FORWARD']
				);
			}

			if ($access->is_serializable_request()) {
				echo $access->output_serialized($output);
			} else {
				echo $this->render($controller, $action, $output, $request);
			}
		} catch (Exception $e) {
			if ($request->modal->int()) {
				// Special handling for modal dialog requests
				// Do not send an error code as bootstrap will just blank out
				// Render the error as a modal
				$smarty = TikiLib::lib('smarty');
				$smarty->assign('title', tr('Oops'));
				$smarty->assign('detail', ['message' => $e->getMessage()]);
				$smarty->display("extends:internal/modal.tpl|error-ajax.tpl");
			} else {
				$access->display_error(NULL, $e->getMessage(), $e->getCode());
			}
		}
	}

	function internal($controller, $action, $request = array())
	{
		if (! $request instanceof JitFilter) {
			$request = new JitFilter($request);
		}

		return $this->attemptProcess($controller, $action, $request);
	}

	function internalRender($controller, $action, $request)
	{
		if (! $request instanceof JitFilter) {
			$request = new JitFilter($request);
		}

		$output = $this->internal($controller, $action, $request);
		return $this->render($controller, $action, $output, $request, true);
	}

	private function attemptProcess($controller, $action, $request)
	{
		try {
			$handler = $this->container->get("tiki.controller.$controller");
			$method = 'action_' . $action;

			if (method_exists($handler, $method)) {
				if (method_exists($handler, 'setUp')) {
					$handler->setUp();
				}

				return $handler->$method($request);
			} else {
				throw new Services_Exception(tr('Action not found (%0 in %1)', $action, $controller), 404);
			}
		} catch (ServiceNotFoundException $e) {
			throw new Services_Exception(tr('Controller not found (%0)', $controller), 404);
		}
	}

	private function preExecute()
	{
		$access = TikiLib::lib('access');

		if ($access->is_xml_http_request() && ! $access->is_serializable_request()) {
			$headerlib = TikiLib::lib('header');
			$headerlib->clear_js(true); // Only need the partials
		}
	}

	private function render($controller, $action, $output, JitFilter $request, $internal = false)
	{
		if (isset($output['FORWARD'])) {
			$url = TikiLib::lib('service')->getUrl($output['FORWARD']);
			TikiLib::lib('access')->redirect($url);
		}

		$smarty = TikiLib::lib('smarty');

		$template = "$controller/$action.tpl";

		//if template doesn't exists, simply return the array given from the action
        //if noTemplate is specified in the query string, it will skip the template
		if (! $smarty->templateExists($template) || strpos($_SERVER['QUERY_STRING'], '&noTemplate') !== false) {
			return json_encode($output);
		}

		$access = TikiLib::lib('access');
		foreach ($output as $key => $value) {
			$smarty->assign($key, $value);
		}

		$layout = null;

		if ($internal) {
			$layout = "layouts/internal/layout_view.tpl";
		} elseif ($access->is_xml_http_request()) {
			$layout = $request->modal->int()
				? 'layouts/internal/modal.tpl'
				: 'layouts/internal/ajax.tpl';
		}

		if ($layout) {
			$out = $smarty->fetch("extends:$layout|$template");
		} else {
			$out = $smarty->fetch($template);
		}

		return $out;
	}
}

