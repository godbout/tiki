<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for WebService
 *
 * Letter key: ~W~
 *
 */
class Tracker_Field_WebService extends Tracker_Field_Abstract
{
    public static function getTypes()
    {
        return [
            'W' => [
                'name' => tr('Webservice'),
                'description' => tr('Display the result of a registered webservice call.'),
                'readonly' => true,
                'help' => 'Webservice+tracker+field',
                'prefs' => ['trackerfield_webservice', 'feature_webservices'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'service' => [
                        'name' => tr('Service Name'),
                        'description' => tr('Webservice name as registered in Tiki.'),
                        'filter' => 'word',
                        'legacy_index' => 0,
                    ],
                    'template' => [
                        'name' => tr('Template Name'),
                        'description' => tr('Template name to use for rendering as registered with the webservice.'),
                        'filter' => 'word',
                        'legacy_index' => 1,
                    ],
                    'params' => [
                        'name' => tr('Parameters'),
                        'description' => tr('URL-encoded list of parameters to send to the webservice. %field_name% can be used in the string to be replaced with the values in the tracker item by field permName, Id or Name.'),
                        'filter' => 'url',
                        'legacy_index' => 2,
                    ],
                    'requireParams' => [
                        'name' => tr('Require parameters'),
                        'description' => tr('Do not execute the request if parameters are missing or empty'),
                        'filter' => 'word',
                        'options' => [
                            '' => tra('All required') . ' ' . tra('(default)'),
                            'first' => tr('First only required'),
                            'none' => tr('No parameters required'),
                        ],
                    ],
                    'cacheSeconds' => [
                        'name' => tr('Cache time'),
                        'description' => tr('Time in seconds to cache the result for before trying again.'),
                        'filter' => 'digits',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        return [];
    }

    public function renderInput($context = [])
    {
        return '<div class="text-muted">' . tr('Read only') . '</div>';
    }

    public function renderOutput($context = [])
    {
        $name = $this->getOption('service');
        $tpl = $this->getOption('template');

        if (! $name || ! $tpl) {
            return false;
        }

        require_once 'lib/webservicelib.php';

        if (! ($webservice = Tiki_Webservice::getService($name))) {
            Feedback::error(tr('Webservice %0 not found', $name));

            return false;
        }
        if (! $template = $webservice->getTemplate($tpl)) {
            Feedback::error(tr('Webservice template %0 not found', $tpl));

            return false;
        }

        $oldValue = $this->getValue();
        $decoded = json_decode($oldValue, true);
        if ($decoded !== null) {
            $oldData = $decoded;
        } else {
            $oldData = [];
        }

        $cacheSeconds = $this->getOption('cacheSeconds');
        $lastRefreshed = empty($oldData['tiki_updated']) ? 0 : strtotime($oldData['tiki_updated']);
        unset($oldData['tiki_updated']);
        $itemId = 0;	// itemId once saved after updating data

        if (! $cacheSeconds || TikiLib::lib('tiki')->now > $lastRefreshed + $cacheSeconds) {
            $ws_params = [];
            $definition = $this->getTrackerDefinition();

            if ($this->getOption('params')) {
                // FIXME replacements such as %facebookId% get url encoded using this so fail
                parse_str($this->getOption('params'), $ws_params);

                $count = 0;
                $requireParams = $this->getOption('requireParams');

                foreach ($ws_params as $ws_param_name => &$ws_param_value) {
                    if (preg_match('/(.*)%(.*)%(.*)/', $ws_param_value, $matches)) {
                        $ws_param_field_name = $matches[2];

                        $field = $definition->getField($ws_param_field_name);
                        if (! $field) {
                            $field = $definition->getFieldFromName($ws_param_field_name);
                        }
                        if ($field) {
                            $itemData = $this->getItemData();

                            if (isset($itemData[$field['fieldId']])) {
                                $value = TikiLib::lib('trk')->get_field_value($field, $itemData);
                            } else {
                                $itemUsers = [];

                                if (empty($itemData['itemId'])) {
                                    $itemData['itemId'] = $_REQUEST['itemId'];	// when editing an item the itemId doesn't seem to be available?
                                }

                                $value = TikiLib::lib('trk')->get_item_fields(
                                    $definition->getConfiguration('trackerId'),
                                    $itemData['itemId'],
                                    [$field],
                                    $itemUsers
                                );
                                $value = isset($value[0]['value']) ? $value[0]['value'] : '';
                            }
                            $ws_params[$ws_param_name] = preg_replace('/%' . $ws_param_field_name . '%/', $value, $ws_param_value);
                        }
                        if (empty($ws_params[$ws_param_name])) {
                            if (empty($requireParams) || ($count === 0 && $requireParams === 'first')) {
                                return '';
                            }
                        }
                        $count++;
                    }
                }
            }

            $response = $webservice->performRequest($ws_params);

            // deal with various types of error coming from different types of webservice
            $error = '';
            if ($response->errors) {
                $error = implode(',', $response->errors);
            } elseif (! empty($response->data['error'])) {
                if (isset($response->data['error']['message'])) {
                    $error = $response->data['error']['message'];	// e.g. facebook graph api
                } else {
                    $error = $response->data['error'];
                }
            } elseif (isset($response->data['status']) && $response->data['status'] !== 'OK') {
                $error = $response->data['status'];					// e.g. google places api
                if (! empty($response->data['error_message'])) {
                    $error .= ' : ' . $response->data['error_message'];
                }
            } elseif (! empty($response->data['hasErrors'])) {
                if (! empty($response->data['errorCode'])) {			// others
                    $error = tr('Unknown webservice error (code: %0)', $response->data['errorCode']);
                } else {
                    $error = tr('Unknown webservice error');
                }
            }
            if ($error) {
                Feedback::error($error);
            } elseif (empty($context['search_render']) || $context['search_render'] !== 'y') {
                if ($template->engine === 'index') {
                    $source = new Search_ContentSource_WebserviceSource();
                    $indexData = $source->getData($name, $tpl, $ws_params);	// preforms request again but should be cached

                    $newData = [];

                    if (is_array($indexData['mapping'])) {
                        foreach ($indexData['mapping'] as $topObject => $topValue) {
                            $dataObject = $indexData['data'][$topObject];
                            if (is_array($dataObject)) {
                                foreach ($dataObject as $key => $val) {
                                    if (is_int($key) && $template->output === 'mindex') {	// multi-doc data
                                        $val = $dataObject[$key];

                                        if (! empty($val) && is_array($val)) {
                                            $newData[$key] = [];
                                            foreach ($val as $key2 => $val2) {
                                                if (! empty($val2) && ! empty($indexData['mapping'][$topObject][0][$key2])) {
                                                    if (! is_array($val2)) {
                                                        $newData[$key][$key2] = $val2;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if (! empty($val) && ! empty($indexData['mapping'][$topObject][$key])) {
                                            if (! is_array($val)) {
                                                $newData[$key] = $val;
                                            } else {
                                                $newData[$key] = [];
                                                foreach ($val as $key2 => $val2) {
                                                    if (! empty($val2) && ! empty($indexData['mapping'][$topObject][$key][$key2])) {
                                                        if (! is_array($val2)) {
                                                            $newData[$key][$key2] = $val2;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $newData = $response->data;
                }
                if (strlen(json_encode($newData)) >= 65535) {	// Limit to size of TEXT field
                    // try and render the json template if it's set to index output type
                    $template = $webservice->getTemplate($tpl);
                    if ($template->output === 'index') {
                        $newData = $template->render($response, 'index');
                    }

                    if (strlen(json_encode($newData)) >= 65535) {	// Limit to size of TEXT field
                        $newData = $oldData;
                        Feedback::error(
                            tr(
                                'Data too long for Webservice field %0 with %1',
                                $this->getConfiguration('permName'),
                                http_build_query($ws_params)
                            )
                        );
                    }
                }

                if ($newData != $oldData) {
                    $thisField = $definition->getField($this->getConfiguration('fieldId'));
                    $newData['tiki_updated'] = gmdate('c');
                    $thisField['value'] = json_encode($newData);

                    $itemId = TikiLib::lib('trk')->replace_item(
                        $definition->getConfiguration('trackerId'),
                        empty($this->getItemId()) ? $_REQUEST['itemId'] : $this->getItemId(),
                        ['data' => [$thisField]]
                    );

                    if (! $itemId) {
                        Feedback::error(tr('Error updating Webservice field %0', $this->getConfiguration('permName')));
                        // try and restore previous data
                        $response->data = json_decode($this->getValue());
                    }
                }
            }
        }
        if (! $itemId) {
            $response = OIntegrate_Response::create($oldData, false);
            unlink($template->getTemplateFile());
            $template = $webservice->getTemplate($tpl);
        }

        if (! empty($response)) {
            $output = $template->render($response, 'html');
        } else {
            $output = '';
        }

        return $output;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();
        $value = json_decode($this->getValue(), true);

        if (isset($value['result'])) {
            $value = $value['result'];
        } elseif (isset($value['data'])) {
            $value = $value['data'];
        } else {
            unset($value['tiki_updated']);	// index the whole response
        }
        if (! is_array($value)) {
            $value = [];
        }

        return [
            $baseKey => $typeFactory->multivalue(array_filter($value, 'is_string')),
            "{$baseKey}_text" => $typeFactory->plaintext(		// ignore nested arrays and remove html for plain text
                    strip_tags(
                        implode(' ', array_filter($value, 'is_string'))
                    )
            ),
            "{$baseKey}_json" => $typeFactory->json($value),
        ];
    }

    public function getProvidedFields()
    {
        $baseKey = $this->getBaseKey();

        return [$baseKey, "{$baseKey}_text", "{$baseKey}_json"];
    }
}
