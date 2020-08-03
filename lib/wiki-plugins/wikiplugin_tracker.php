<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_tracker_info()
{
    return [
        'name' => tra('Tracker'),
        'documentation' => 'PluginTracker',
        'description' => tra('Embed a form to populate a tracker'),
        'tags' => [ 'basic' ],
        'prefs' => [ 'feature_trackers', 'wikiplugin_tracker' ],
        'body' => tra('Confirmation message after posting form'),
        'iconname' => 'trackers',
        'introduced' => 1,
        'format' => 'html',
        'params' => [
            'trackerId' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '1',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'fields' => [
                'required' => false,
                'name' => tra('Fields'),
                'description' => tr('Colon-separated list of field IDs to be displayed in the form as input fields.
					If empty, all fields will be shown. Example: %0', '<code>2:4:5</code>'),
                'since' => '1',
                'default' => '',
                'separator' => ':',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
            ],
            'values' => [
                    'required' => false,
                    'name' => tra('Values'),
                    'description' => tr('Colon-separated list of default values corresponding to the %0fields%1 parameter.
				First value corresponds to first field, second value to second field, etc. Default values can be
				set by using %0autosavefields%1 and %0autosavevalues%1 as URL parameters.', '<code>', '</code>'),
                    'since' => '2.0',
                    'default' => '',
            ],
            'action' => [
                'required' => false,
                'name' => tra('Action'),
                'description' => tr(
                    'Colon-separated labels for form submit buttons. Default is %0Save%1. When set to
					%0NONE%1, the save button will not appear and values will be saved dynamically.',
                    '<code>',
                    '</code>'
                ),
                'since' => '1',
                'separator' => ':',
                'default' => 'Save'
            ],
            'action_style' => [
                'required' => false,
                'name' => tra('Action Style'),
                'description' => tr(
                    'Sets button style classes for action buttons. If multiple buttons have been set
					in the %0 parameter, the same number of colon-separated styles must be set here. Example:',
                    '<code>action</code>'
                ) . "<code>btn btn-secondary:btn btn-success:btn btn-primary float-right</code>",
                'since' => '14.1',
                'separator' => ':',
                'default' => 'btn btn-secondary'
            ],
            'showtitle' => [
                'required' => false,
                'name' => tra('Show Title'),
                'description' => tra('Display the title of the tracker at the top of the form (not shown by default)'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showdesc' => [
                'required' => false,
                'name' => tra('Show Description'),
                'description' => tra('Show the tracker\'s description (not shown by default)'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showfieldsdesc' => [
                'required' => false,
                'name' => tra('Show Fields Descriptions'),
                'description' => tra('Show the tracker\'s field descriptions (shown by default)'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showmandatory' => [
                'required' => false,
                'name' => tra('Mark Mandatory'),
                'description' => tra('Indicate mandatory fields with an asterisk (shown by default).'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showstatus' => [
                'required' => false,
                'name' => tra('Show Status'),
                'description' => tra('Show the status of the items (not shown by default)'),
                'since' => '5.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'embedded' => [
                'required' => false,
                'name' => tra('Embedded'),
                'description' => tra('Embedded'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'email' => [
                'required' => false,
                'name' => tra('Email'),
                'description' => tr('To send an email once the tracker item has been created. Format: %0from', '<code>')
                        . '|' . tra('to') . '|' . tr('template%0', '</code> ') . tr(
                            'For %0from%1 and %0to%1, use an email address
						(separate multiple addresses with a comma), a username, a fieldId of a field containing either an email address or a username,
						a fieldId of a UserSelector or GroupSelector field, or "createdBy" or "lastModifBy" for the item creator or modifier.
						When username is being used, the email will be sent to the email address of the user on file.
						When sending to several emails using different template, provide the template name for the message body for each email;
						I.e., the first template will be used for the first to, the second template if exists will be used
						for the second from (otherwise the last given template will be used). Each template needs two files, one for the subject one for the body. The subject will be named
						template_subject.tpl. All the templates must be in the %0templates/mail%1 directory. Example:
						%0webmaster@my.com|a@my.com,b@my.com|templatea.tpl,templateb.tpl%1 (%0templates/mail/tracker_changed_notification.tpl%1
						is the default from which you can get inspiration).',
                            '<code>',
                            '</code>'
                        ),
                'since' => '2.0',
                'default' => '',
            ],
            'emailformat' => [
                'required' => false,
                'name' => tra('Email Format'),
                'description' => tra('Choose between values text or html, depending on the syntax in the template file
					that will be used'),
                'since' => '6.1',
                'default' => 'text',
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tr('URL the user is sent to after the form is submitted. The string %0itemId%1 will
					be replaced with %0itemId=xx%1 where %0xx%1 is the new (or current) itemId. This parameter can be used in combination with the TABS plugin using %0?cookietab=1&itemId%1 (change the cookietab parameter\'s value depending on the tab you want the user to be sent to).', '<code>', '</code>'),
                'since' => '1',
                'filter' => 'url',
                'separator' => ':',
                'default' => '',
            ],
            'urlparams' => [
                'required' => false,
                'name' => tr('Keep params in URL'),
                'description' => tr('Input form parameters (or field IDs) separated by a colon (%0:%1) to pass them in the URL after the form is submitted and redirected to the URL specified in the %0url%1 param. Example: %0urlparams="1:2:3:trackit"%1. Enter %0*%1 to keep all the submitted parameters in the URL.', '<code>', '</code>'),
                'since' => '19.0',
                'filter' => 'url',
                'separator' => ':',
                'default' => '',
                'advanced' => true,
            ],
            'target' => [
                'required' => false,
                'name' => tra('URL Target'),
                'description' => tra('Set the target parameter for the url (determines whether target will open in a
					new page, etc.)'),
                'since' => '4.0',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Blank'), 'value' => '_blank'],
                    ['text' => tra('Parent'), 'value' => '_parent'],
                    ['text' => tra('Self'), 'value' => '_self'],
                    ['text' => tra('Top'), 'value' => '_top']
                ],
                'advanced' => true,
            ],
            'overwrite' => [
                'required' => false,
                'name' => tra('Overwrite'),
                'description' => tr(
                    'Overwrite current field values of the item with the input values. Does not
					overwrite wiki pages and does not work when the %0discarditem%1 parameter is set to Yes (%0y%1).',
                    '<code>',
                    '</code>'
                ),
                'since' => '6.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'sort' => [
                'required' => false,
                'name' => tra('Sort'),
                'description' => tra('Display columns in the order listed in the fields parameter instead of by
					field ID (field ID order is used by default)'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'preview' => [
                'required' => false,
                'name' => tra('Preview'),
                'description' => tr('To add a preview button with the label set by this parameter. Default:
					%0Preview%1. Useful to preview the computed fields of an item.', '<code>', '</code>'),
                'since' => '2.0',
                'default' => 'Preview',
            ],
            'reset' => [
                'required' => false,
                'name' => tra('Reset'),
                'description' => tra('Label for the reset button, to return all fields to their default values.'),
                'since' => '4.2',
                'default' => tra('reset'),
            ],
            'view' => [
                'required' => false,
                'name' => tra('View'),
                'description' => tr('Determine which items will be affected byt the form. If set to %0user%1 and
					%0trackerId%1 is not set, then the user tracker associated with the default group will be affected.
					If %0trackerId%1 is set, then the item associated with the user in that tracker will be affected.
					 If set to %0page%1, the item associated with that page will be affected (%0trackerId%1 must be
					 set in this case).', '<code>', '</code>'),
                'since' => '1',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Group'), 'value' => 'group'],
                    ['text' => tra('Page'), 'value' => 'page'],
                    ['text' => tra('User'), 'value' => 'user'],
                    ['text' => tra('User&field'), 'value' => 'userandfield']
                ]
            ],
            'userfieldtofilter' => [
                'required' => false,
                'name' => tra('User field to filter'),
                'description' => tra('User field to filter user tracker items to:') . ' <code>view="user&field"</code>',
                'since' => '15.3',
                'default' => '',
                'advanced' => true,
            ],
            'fieldtofilter' => [
                'required' => false,
                'name' => tra('Field to filter'),
                'description' => tra('Field to filter user tracker items to:') . ' <code>view="user&field"</code>',
                'since' => '15.3',
                'default' => '',
                'advanced' => true,
            ],
            'fieldtofiltervalue' => [
                'required' => false,
                'name' => tra('Field to filter Value'),
                'description' => tra('Value to filter user tracker items to:') . ' <code>view="user&field"</code>',
                'since' => '15.3',
                'default' => '',
                'advanced' => true,
            ],
            'fieldtofiltercriteria' => [
                'required' => false,
                'name' => tra('Criteria'),
                'description' => tra('If more than one item found, will choose under this criteria. Used in combination with:') . '<code>view="user&field"</code>',
                'since' => '15.3',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    //array('text' => tra('lastModif - Ascending'), 'value' => 'lastModifAsc'),//Not working
                    //array('text' => tra('lastModif - Descending'), 'value' => 'lastModifDesc'),//Not working
                    ['text' => tra('creationDate - Ascending'), 'value' => 'creationAsc'],
                    ['text' => tra('creationDate - Descending'), 'value' => 'creationDesc']
                ],
                'advanced' => true,
            ],
            'status' => [
                'required' => false,
                'name' => tra('Status'),
                'description' => tra('Status of the item used in combination with:') . ' <code>view="user"</code>' . tra('or') . '<code>view="user&field"</code>',
                'since' => '6.0',
                'default' => '',
                'advanced' => true,
            ],
            'transactionName' => [
                'required' => false,
                'name' => tra('Transaction name'),
                'description' => tra('The transaction identifier. This identifier connects the various trackers into a
					single transaction. Must be unique per transaction. The multiple steps in a single transaction
					must share the same transaction name.'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
            ],
            'transactionStep' => [
                'required' => false,
                'name' => tra('Transaction Step'),
                'description' => tr('Transaction step number specifying the order of the transaction steps. The first
					step must be %0.', '<code>0</code>'),
                'since' => '15.0',
                'filter' => 'digits',
                'default' => '0',
                'advanced' => true,
            ],
            'transactionFinalStep' => [
                'required' => false,
                'name' => tra('Final Transaction Step'),
                'description' => tra('Indicate whether this is the final transaction step'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'transactionPreviousURL' => [
                'required' => false,
                'name' => tr('Transaction previous URL'),
                'description' => tr('The page to go back to when the "previous" button is clicked.'),
                'since' => '18.4',
                'filter' => 'url',
                'default' => '',
                'advanced' => true,
            ],
            'transactionPreviousLabel' => [
                'required' => false,
                'name' => tr('Transaction previous button label'),
                'description' => tr('Text for the "previous" button label.'),
                'since' => '18.4',
                'filter' => 'text',
                'default' => tr(''),
                'advanced' => true,
            ],
            'itemId' => [
                'required' => false,
                'name' => tra('ItemId'),
                'description' => tra('ItemId identifying the item to be edited.'),
                'since' => '3.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_item',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
            ],
            'ignoreRequestItemId' => [
                'required' => false,
                'name' => tra('Ignore ItemId'),
                'description' => tr(
                    'Do not filter on the parameter %0itemId%1 if in the url (default is to filter)',
                    '<code>',
                    '</code>'
                ),
                'since' => '6.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'tpl' => [
                'required' => false,
                'name' => tra('Template File'),
                'description' => tr(
                    'Name of the template used to display the tracker items.  In the template, the
					smarty variable %0{$f_id}%1 will be replaced with the appropriate input tag, with %0id%1 representing
					the field ID. The form tag and the submit button are generated by Tiki outside the template',
                    '<code>',
                    '</code>'
                ),
                'since' => '2.0',
                'default' => '',
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki'),
                'description' => tr(
                    'Name of the wiki page containing the template to display the tracker items. This
					page must have the permission %0tiki_p_use_as_template%1 assigned to the Anonymous group to be used
					as a template.',
                    '<code>',
                    '</code>'
                ),
                'since' => '2.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'newstatus' => [
                'required' => false,
                'name' => tra('New Status'),
                'description' => tra('Default status applied to newly created or saved items.'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Open'), 'value' => 'o'],
                    ['text' => tra('Pending'), 'value' => 'p'],
                    ['text' => tra('Closed'), 'value' => 'c']
                ]
            ],
            'colwidth' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Specify the width in pixels or percentage of the first column (the labels) in the
					tracker form.'),
                'since' => '3.0',
                'default' => '',
                'accepted' => '## or ##%',
                'advanced' => true,
            ],
            'autosavefields' => [
                'required' => false,
                'name' => tra('Autosave Fields'),
                'description' => tra('Colon-separated list of field IDs to be automatically filled with values upon
					save.'),
                'since' => '5.0',
                'filter' => 'digits',
                'separator' => ':',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
                'advanced' => true,
            ],
            'autosavevalues' => [
                'required' => false,
                'name' => tra('Autosave Values'),
                'description' => tr(
                    'Colon-separated values corresponding to %0. Special syntax cases:',
                    '<code>autosavefields</code>'
                ) . '<br />'
                    . '<code>categories(x)</code> - ' . tr('selects the first child category under a category with ID
						%0x%1 for use in a category field', '<code>', '</code>') . '<br />'
                    . '<code>preference(x)</code> - ' . tr('inserts the value of the preference with %0x%1 being the
						preference name.', '<code>', '</code>'),
                'since' => '5.0',
                'filter' => 'text',
                'separator' => ':',
                'default' => '',
                'advanced' => true,
            ],
            'levelupfields' => [
                'required' => false,
                'name' => tra('Increase-only Fields'),
                'description' => tr(
                    'Used with the %0  and %1 parameters. Colon-separated list of field IDs being
					auto-saved where the specified auto-save value will not take effect if it is less than or equal to
					the current value of the field',
                    '<code>autosavefields</code>',
                    '<code>autosavevalues</code>'
                ),
                'since' => '8.0',
                'filter' => 'digits',
                'separator' => ':',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
                'advanced' => true,
            ],
            'registration' => [
                'required' => false,
                'name' => tra('Registration Fields'),
                'description' => tra('Add registration fields such as Username and Password for use in registration
					trackers'),
                'since' => '6.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'chosenGroup' => [
                'required' => false,
                'name' => tra('Register to group'),
                'description' => tra('The user enters this group via the registration (only a single group name
					is supported)'),
                'since' => '15.0',
                'filter' => 'text',
                'default' => 'Registered',
            ],
            'validateusers' => [
                'required' => false,
                'name' => tra('Validate users'),
                'description' => tra('Here one can overrule the default validate users by e-mail preference.'),
                'since' => '15.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'outputtowiki' => [
                'required' => false,
                'name' => tra('Output To Wiki'),
                'description' => tra('Output result to a new wiki page with the name taken from the input for the
					specified fieldId'),
                'since' => '6.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
                'advanced' => true,
            ],
            'discarditem' => [
                'required' => false,
                'name' => tra('Discard After Output'),
                'description' => tr('Used with %0 - whether to discard the tracker item itself
					once the wiki page is created, so that, in effect, the tracker is just a vehicle to create form
					fields to facilitate creating wiki pages.', '<code>outputtowiki</code>'),
                'since' => '6.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'outputwiki' => [
                'required' => false,
                'name' => tra('Template Page'),
                'description' => tr(
                    'Name of the wiki page containing the template to format the output to wiki page.
					Must be set for %0 to work. The template can contain variables to represent fields, for example
					%1 would result in the value of fieldId 6. Also %2 can be used for the itemId and if you have set
					%3 to yes, you can use %4 and %5.',
                    '<code>outputtowiki</code>',
                    '<code>{$f_6}</code>',
                    '<code>{$f_itemId}</code>',
                    '<code>register</code>',
                    '<code>{$register_login}</code>',
                    '<code>{$register_email}</code>'
                ),
                'since' => '6.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
                'advanced' => true,
            ],
            'outputwikinamespace' => [
                'required' => false,
                'name' => tra('Output Wiki Namespace'),
                'description' => tra('Name of namespace that is used for the wiki page that is created when outputting
					to a wiki page.'),
                'since' => '13.0',
                'filter' => 'pagename',
                'default' => '',
                'advanced' => true,
            ],
            'outputwikirelation' => [
                'required' => false,
                'name' => tra('Store Relation'),
                'description' => tr(
                    'Store %0 and %1 relation from the created
					wiki page when outputting to a wiki page. Optionally, (separate feature to be turned on in admin
					panel) these relations are used to sync page renames with the field specified in %2,
					and also optionally to redirect page viewing to the tracker item instead (where you can then
					include the page if needed).',
                    '<code>tiki.wiki.linkeditem</code>',
                    '<code>tiki.wiki.linkedfield</code>',
                    '<code>outputtowiki</code>'
                ),
                'since' => '13.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'fieldsfill' => [
                'required' => false,
                'name' => tra('Multiple Fill Fields'),
                'description' => tr('Colon-separated list of field IDs to be filled with multiple values, to create
					multiple items in one save. If empty, only one item will be created. Only for
					item creation. Example: %0', '<code>2:4:5</code>'),
                'since' => '9.0',
                'default' => '',
                'separator' => ':',
                'profile_reference' => 'tracker_field',
                'parent' => 'input[name="params[trackerId]"]',
                'parentkey' => 'tracker_id',
                'advanced' => true,
            ],
            'fieldsfillseparator' => [
                'required' => false,
                'name' => tra('Fill Fields Separator'),
                'description' => tr('Choose separator between fields in each line of the Multiple Fill text area.
					Default is pipe (%0|%1).', '<code>', '</code>'),
                'since' => '9.0',
                'default' => '|',
                'advanced' => true,
            ],
            'fieldsfilldefaults' => [
                'required' => false,
                'name' => tra('Fill Fields Defaults'),
                'description' => tra('Colon-separated list of default values for Multiple Fill Fields.'),
                'since' => '9.0',
                'default' => '',
                'advanced' => true,
            ],
            'formtag' => [
                'required' => false,
                'name' => tra('Embed the tracker in a form tag'),
                'description' => tr(
                    'If set to Yes (%0), the tracker is contained in a <form> tag and has action buttons',
                    '<code>y</code>'
                ),
                'since' => '6.4',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'ajax' => [
                'required' => false,
                'name' => tra('Use ajax to create/update tracker items'),
                'description' => tra('Use ajax to create and update tracker items instead of form submission via request variables.'),
                'default' => 'n',
            ],
            'rules' => [
                'required' => false,
                'name' => tra('Use field rules'),
                'description' => tra('Set up rules on field definitions to show and hide fields conditionally.'),
                'default' => 'n',
                'advanced' => true,
                'filter' => 'alpha',
            ],
            'rulesparent' => [
                'required' => false,
                'name' => tra('Field rules parent selector'),
                'description' => tra('JQuery selector for the parent object to show or hide when executing field rules.'),
                'default' => '.form-group:first',
                'advanced' => true,
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_tracker_name($fieldId, $name, $field_errors)
{
    foreach ($field_errors['err_mandatory'] as $f) {
        if ($fieldId == $f['fieldId']) {
            return '<span class="highlight">' . htmlspecialchars($name) . '</span>';
        }
    }
    foreach ($field_errors['err_value'] as $f) {
        if ($fieldId == $f['fieldId']) {
            return '<span class="highlight">' . htmlspecialchars($name) . '</span>';
        }
    }

    return htmlspecialchars($name);
}

function wikiplugin_tracker($data, $params)
{
    global $user, $group, $page, $prefs;
    $parserlib = TikiLib::lib('parser');
    $trklib = TikiLib::lib('trk');
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $captchalib = TikiLib::lib('captcha');

    $smarty->loadPlugin('smarty_function_ticket');

    static $iTRACKER = 0;
    ++$iTRACKER;
    if (isset($params['itemId']) && empty($params['itemId'])) {
        return;
    }
    $smarty->assign('trackerEditFormId', $iTRACKER);
    $default = ['overwrite' => 'n', 'embedded' => 'n', 'showtitle' => 'n', 'showdesc' => 'n',
                'showfieldsdesc' => 'y', 'sort' => 'n', 'showmandatory' => 'y', 'status' => '',
                'transactionFinalStep' => '', 'registration' => 'n', 'chosenGroup' => 'Registered',
                'validateusers' => '', 'emailformat' => 'text', 'ajax' => 'n',
                'rules' => 'n', 'rulesparent' => '.form-group:first'];
    $params = array_merge($default, $params);
    $item = [];

    extract($params, EXTR_SKIP);

    $thisIsThePlugin = isset($_REQUEST['iTRACKER']) && $_REQUEST['iTRACKER'] == $iTRACKER;

    if (isset($fields)) {
        $fields = array_filter((array) $fields);
    }

    $nameExist = isset($transactionName) && strlen($transactionName) > 0;
    $stepExist = isset($transactionStep) && strlen($transactionStep) > 0;
    if ($nameExist xor $stepExist) {
        return WikiParser_PluginOutput::error(
            tr('Transactions'),
            tr('You need to define both transaction name and transaction step, or none of the two.')
        );
    } elseif ($nameExist && $stepExist) {
        if (empty($transactionFinalStep)) {
            return WikiParser_PluginOutput::error(
                tr('Transactions'),
                tr('You need to choose transactionFinalStep (y/n).')
            );
        }
        if ((empty($transactionPreviousURL) && ! empty($transactionPreviousLabel))
            || (! empty($transactionPreviousURL)
                && empty($transactionPreviousLabel))
        ) {
            return WikiParser_PluginOutput::error(
                tr('Transactions'),
                tr('Both transactionPreviousURL and transactionPreviousLabel are needed for a "previous" button.')
            );
        }
        if (! isset($_SESSION[$transactionName])) {
            $_SESSION[$transactionName] = [];
        }
        if (! isset($_SESSION[$transactionName]['values'])) {
            $_SESSION[$transactionName]['values'] = [];
        }
        if (! isset($_SESSION[$transactionName]['transactionStep'])) {
            $_SESSION[$transactionName]['transactionStep'] = 0;
        }
        if (! empty($_REQUEST['itemId'])
            && ! (isset($_REQUEST['ok'])
                && $thisIsThePlugin)
        ) {    // not if item has been saved
            if (! empty($_SESSION[$transactionName]['itemId'])
                && $_SESSION[$transactionName]['itemId'] != $_REQUEST['itemId']
            ) {
                // changed itemId since last transaction edit - so clear session values
                $_SESSION[$transactionName]['values'] = [];
            }
            $_SESSION[$transactionName]['itemId'] = $_REQUEST['itemId'];
        } elseif (! empty($_SESSION[$transactionName]['itemId'])) {
            $_REQUEST['itemId'] = $_SESSION[$transactionName]['itemId'];
            // jquery validation needs the itemId in the $jitRequest
            global $jitRequest;
            $jitRequest = new JitFilter($_REQUEST);
        }
    }

    if ($prefs['feature_trackers'] != 'y') {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    if (empty($trackerId) || ! ($definition = Tracker_Definition::get($trackerId))) {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    $tracker = $definition->getInformation();
    if (empty($trackerId) && ! empty($view) && $view == 'user' && $prefs['userTracker'] == 'y') { // the user tracker item
        $utid = $userlib->get_tracker_usergroup($user);
        if (! empty($utid) && ! empty($utid['usersTrackerId'])) {
            $itemId = $trklib->get_item_id($utid['usersTrackerId'], $utid['usersFieldId'], $user);
            $trackerId = $utid['usersTrackerId'];
            $usertracker = true;
        }
    } elseif (! empty($trackerId) && ! empty($view) && $view == 'user') {// the user item of a tracker
        $itemId = $trklib->get_user_item($trackerId, $tracker, null, null, strlen($status) == 1 ? $status : '');
        $usertracker = true;
    } elseif (! empty($trackerId) && ! empty($view) && $view == 'page' && ! empty($_REQUEST['page']) && $f = $trklib->get_page_field($trackerId)) {// the page item
        $itemId = $trklib->get_item_id($trackerId, $f['fieldId'], $_REQUEST['page']);
    } elseif (! empty($trackerId) && ! empty($_REQUEST['view_user'])) {
        $itemId = $trklib->get_user_item($trackerId, $tracker, $_REQUEST['view_user']);
    } elseif (! empty($_REQUEST['itemId']) && (empty($ignoreRequestItemId) || $ignoreRequestItemId != 'y')) {
        $itemId = $_REQUEST['itemId'];
        $item = $trklib->get_tracker_item($itemId);
        $trackerId = $item['trackerId'];
    } elseif (! empty($view) && $view == 'group') {
        $gtid = $userlib->get_grouptrackerid($group);
        if (isset($gtid['groupTrackerId'])) {
            $trackerId = $gtid['groupTrackerId'];
            $itemId = $trklib->get_item_id($trackerId, $gtid['groupFieldId'], $group);
            $grouptracker = true;
        }
    } elseif (! empty($view) && $view == 'userandfield' && $userfieldtofilter && $fieldtofilter && $fieldtofiltervalue) {
        $trackerinfo = Tracker_Query::tracker($trackerId)
            ->filter(['field' => $userfieldtofilter, 'value' => $user])
            ->filter(['field' => $fieldtofilter, 'value' => $fieldtofiltervalue])
            ->status(strlen($status) >= 1 ? $status : 'opc')
            ->lastModif($fieldtofiltercriteria == 'lastModifAsc' || $fieldtofiltercriteria == 'lastModifDesc' ? true : false)//Not Working
            ->created($fieldtofiltercriteria == 'creationAsc' || $fieldtofiltercriteria == 'creationDesc' ? true : false)
            ->desc($fieldtofiltercriteria == 'creationDesc' || $fieldtofiltercriteria == 'creationDesc' ? true : false)
            ->query();
        $itemIds = array_keys($trackerinfo);
        $itemId = $itemIds[0];
    }
    if (! isset($trackerId)) {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    //test for validation errors for registration tracker calls
    if (isset($_REQUEST['register']) && ($_REQUEST['register'] == 'Register' || $_REQUEST['register'] == 'register')) {
        $regtracker = $userlib->get_usertrackerid('Registered');
        if ($trackerId == $regtracker['usersTrackerId'] && $_REQUEST['valerror'] !== false) {
            if (is_array($_REQUEST['valerror'])) {
                foreach ($_REQUEST['valerror'] as $valerror) {
                    if (is_a($valerror, 'RegistrationError')) {
                        return false;

                        break;
                    }
                }
            } elseif (is_a($_REQUEST['valerror'], 'RegistrationError')) {
                return false;
            }
        }
    }

    if (! isset($action)) {
        $action = ['Save'];
    }
    if (! is_array($action)) {
        $action = [ $action ];
    }

    $dynamicSave = false;
    if (count($action) == 1 && reset($action) == 'NONE') {
        $action = [];
        $dynamicSave = true;
    }

    if (! isset($action_style)) {
        $action_style = [];
        foreach ($action as $ac) {
            $action_style[] = 'btn btn-secondary';
        }
    }

    if (isset($preview)) {
        if (empty($preview)) {
            $preview = 'Preview';
        }
    } else {
        unset($_REQUEST['tr_preview']);
    }
    if (isset($reset)) {
        if (empty($reset)) {
            $reset = 'reset';
        }
    } else {
        unset($_REQUEST['tr_reset']);
    }
    $smarty->assign('showmandatory', empty($wiki) && empty($tpl) ? 'n' : $showmandatory);
    if (! empty($wiki)) {
        if (preg_match('/^wiki:(.+)$/', $wiki, $wiki_matches)) {
            $wiki = $wiki_matches[1];
        }

        $wiki = trim($wiki);
    }

    if (! isset($params['formtag'])) {
        $params['formtag'] = 'y';
    }

    $fields_prefix = 'ins_';

    if (isset($values)) {
        if (! is_array($values)) {
            $values = explode(':', $values);
            foreach ($values as $i => $v) {
                $values[$i] = preg_replace('/^"(.*)"$/', '$1', $v);
            }
        }
    }
    if (isset($_REQUEST['values'])) {
        if (is_array($_REQUEST['values'])) {
            foreach ($_REQUEST['values'] as $i => $k) {
                $_REQUEST['values'][$i] = urldecode($k);
            }
        } else {
            $_REQUEST['values'] = urldecode($_REQUEST['values']);
        }
    }

    $perms = $tikilib->get_perm_object($trackerId, 'tracker', $tracker, false);

    if (empty($_SERVER['SCRIPT_NAME']) || strpos($_SERVER['SCRIPT_NAME'], 'tiki-register.php') === false) {
        if ($perms['tiki_p_create_tracker_items'] == 'n' && empty($itemId)) {
            return '<b>' . tra("You do not have permission to insert an item") . '</b>';
        } elseif (! empty($itemId)) {
            $item_info = $trklib->get_tracker_item($itemId);
            if (empty($item_info)) {
                return '<b>' . tra("Incorrect item") . '</b>';
            }
            $itemObject = Tracker_Item::fromInfo($item_info);
            if (! $itemObject->canModify()) {
                return '<b>' . tra("You do not have permission to modify an item") . '</b>';
            }
        }
    }

    if (! empty($itemId)) {
        $logslib = TikiLib::lib('logs');
        $logslib->add_action('Viewed', $itemId, 'trackeritem', $_SERVER['REQUEST_URI']);
    }

    if (isset($_REQUEST['removeattach']) && $tracker['useAttachments'] == 'y') {
        $owner = $trklib->get_item_attachment_owner($_REQUEST['removeattach']);
        if ($perms['tiki_p_admin_trackers'] == 'y' || ($user && $user == $owner)) {
            $trklib->remove_item_attachment($_REQUEST["removeattach"]);
            unset($_REQUEST['removeattach']);
        }
    }
    if (isset($_REQUEST['removeImage']) && ! empty($_REQUEST['trackerId']) && ! empty($_REQUEST['itemId']) && ! empty($_REQUEST['fieldId']) && ! empty($_REQUEST['fieldName'])) {
        $img_field = ['data' => []];
        $img_field['data'][] = ['fieldId' => $_REQUEST['fieldId'], 'type' => 'i', 'name' => $_REQUEST['fieldName'], 'value' => 'blank'];
        $trklib->replace_item($_REQUEST['trackerId'], $_REQUEST['itemId'], $img_field);
    }
    $back = '';

    if (! isset($_REQUEST["ok"]) || $_REQUEST["ok"] == "n" || ! $thisIsThePlugin || isset($_REQUEST['tr_preview'])) {
        $field_errors = ['err_mandatory' => [], 'err_value' => []];

        $notificationlib = TikiLib::lib('notification');
        $tracker = $trklib->get_tracker($trackerId);
        $tracker = array_merge($tracker, $trklib->get_tracker_options($trackerId));
        if ((! empty($tracker['start']) && $tikilib->now < $tracker['start']) || (! empty($tracker['end']) && $tikilib->now > $tracker['end'])) {
            return;
        }
        $outf = [];
        $auto_fieldId = [];
        $hidden_fieldId = [];
        if (! empty($fields) || ! empty($wiki) || ! empty($tpl) || ! empty($prefs['user_register_prettytracker_tpl'])) {
            if (isset($registration) && $registration == 'y' && $prefs["user_register_prettytracker"] == 'y' && ! empty($prefs["user_register_prettytracker_tpl"])) {
                $registrationlib = TikiLib::lib('registration');
                $smarty->assign('listgroups', $registrationlib->merged_prefs['choosable_groups']);
                $smarty->assign('register_login', $smarty->fetch('register-login.tpl'));
                $smarty->assign('register_email', $smarty->fetch('register-email.tpl'));
                $smarty->assign('register_pass', $smarty->fetch('register-pass.tpl'));
                $smarty->assign('register_pass2', $smarty->fetch('register-pass2.tpl'));
                $smarty->assign('register_passcode', $smarty->fetch('register-passcode.tpl'));
                $smarty->assign('register_groupchoice', $smarty->fetch('register-groupchoice.tpl'));
                if ($prefs['feature_antibot'] == 'y') {
                    $smarty->assign('showantibot', true);
                    $smarty->assign('form', 'register');
                    $smarty->assign('register_antibot', $smarty->fetch('antibot.tpl'));
                }
                if (empty($wiki) && empty($tpl)) {	// no template in params?
                    if (preg_match('/\.tpl$/i', $prefs['user_register_prettytracker_tpl'])) {	// ends in .tpl?
                        $tpl = $prefs['user_register_prettytracker_tpl'];
                    } else {
                        $wiki = $prefs['user_register_prettytracker_tpl'];
                    }
                }
            }
            if (! empty($wiki)) {
                $outf = $trklib->get_pretty_fieldIds($wiki, 'wiki', $prettyModifier, $trackerId);
            } elseif (! empty($tpl)) {
                $outf = $trklib->get_pretty_fieldIds($tpl, 'tpl', $prettyModifier, $trackerId);
            } elseif (! empty($fields)) {
                $outf = $fields;
            }
            if (! empty($_REQUEST['autosavefields'])) {
                $autosavefields = explode(':', $_REQUEST['autosavefields']);
                $autosavevalues = explode(':', $_REQUEST['autosavevalues']);
                if (isset($params['autosavefields'])) {
                    $autosavefields = array_merge($autosavefields, $params['autosavefields']);
                    $autosavevalues = array_merge($autosavevalues, $params['autosavevalues']);
                }
            }
            if (! empty($autosavefields)) {
                $auto_fieldId = array_merge($auto_fieldId, $autosavefields);
            }
            foreach ($definition->getFields() as $field) {
                // User and group on autoassign create/modify
                if (($user || $registration == 'y' || (isset($transactionName) && isset($_SESSION[$transactionName]) && isset($_SESSION[$transactionName]['registrationName'])))
                   && ($field['type'] == 'u' || $field['type'] == 'g')) {
                    $autoassign = $field['options_map']['autoassign'];
                    if ($autoassign == 1 || $autoassign == 2) {
                        if ($user) {
                            $hidden_fieldId[] = $field['fieldId'];
                        }
                        $userField = $field['fieldId'];
                    }
                }

                // IP and page on autoassign
                if ($field['type'] == 'I' || $field['type'] == 'k') {
                    $autoassign = $field['options_map']['autoassign'];
                    if ($autoassign == 1) {
                        $hidden_fieldId[] = $field['fieldId'];
                    }
                }

                // Auto-increment
                if ($field['type'] == 'q') {
                    $auto_fieldId[] = $field['fieldId'];
                }
                // Wiki Page
                if ($field['type'] == 'wiki') {
                    $auto_fieldId[] = $field['fieldId'];
                }
            }
            foreach ($auto_fieldId as $k => $v) {
                if (empty($v) || in_array($v, $outf)) {
                    unset($auto_fieldId[$k]);
                } else {
                    $outf[] = $v;
                }
            }
            foreach ($hidden_fieldId as $k => $v) {
                if (empty($v) || in_array($v, $outf)) {
                    unset($hidden_fieldId[$k]);
                } else {
                    $outf[] = $v;
                }
            }
        }

        $definition = Tracker_Definition::get($trackerId);
        $item_info = isset($item_info) ? $item_info : [];
        $factory = $definition->getFieldFactory();

        if (empty($item_info)) {
            $itemObject = Tracker_Item::newItem($trackerId);
        } elseif (! isset($itemObject)) {
            $itemObject = Tracker_Item::fromInfo($item_info);
        }

        if (empty($outf)) {
            $unfiltered = ['data' => $definition->getFields()];
        } else {
            $unfiltered = ['data' => []];
            foreach ($outf as $fieldId) {
                $fieldTemp = $definition->getField($fieldId);
                if ($fieldTemp) {
                    $unfiltered['data'][] = $fieldTemp;
                } else {
                    Feedback::error(tr('Tracker: Field #%0 not found in fields parameter or template', $fieldId));
                }
            }
        }

        $flds = ['data' => []];
        foreach ($unfiltered['data'] as $f) {
            if ($itemObject->canModifyField($f['fieldId']) || $registration == 'y' && empty($item_info)) {
                $flds['data'][] = $f;
            } elseif ($itemObject->canViewField($f['fieldId']) && ! empty($item_info)) {
                $flds['data'][] = [
                    'fieldId' => $f['fieldId'],
                    'trackerId' => $f['trackerId'],
                    'permName' => $f['permName'],
                    'type' => $f['type'],
                    'readonly' => true,
                ];
            }
        }

        // If we create multiple items, get field Ids, default values and separator
        if (! empty($fieldsfill)) {
            $fill_fields = $fieldsfill;	// Allow for superfluous spaces and ignore them
            $fill_flds = ['data' => []];
            $fill_defaults = [];
            $fill_flds_defaults = [];	// May be different from fill_defaults if some fields are not editable
            $fieldsfillnames = [];
            if (trim($fieldsfilldefaults) != '') {
                $fill_defaults = preg_split('/ *: */', $fieldsfilldefaults);
            }
            foreach ($fill_fields as $k => $fieldId) {
                if ($itemObject->canModifyField($fieldId)) {
                    $tmp = $definition->getField($fieldId);
                    $fill_flds['data'][] = $tmp;
                    if (isset($fill_defaults[$k])) {
                        $fill_flds_defaults[] = $fill_defaults[$k];
                    } else {
                        $fill_flds_defaults[] = '';
                    }
                    $fieldsfillnames[] = $tmp['name'];
                }
            }
            $fill_line_cant = count($fill_flds['data']);
            if ($fieldsfillseparator == '') {
                $fieldsfillseparator = '|';
            }
        }

        $bad = [];
        $embeddedId = false;
        $onemandatory = false;
        $full_fields = [];
        $mainfield = '';

        if ($thisIsThePlugin) {
            /* ------------------------------------- Recup all values from REQUEST -------------- */
            if (! empty($autosavefields)) {
                foreach ($autosavefields as $i => $f) {
                    if (! $ff = $trklib->get_field($f, $flds['data'])) {
                        continue;
                    }
                    if (preg_match('/categories\(([0-9]+)\)/', $autosavevalues[$i], $matches)) {
                        if (ctype_digit($matches[1]) && $matches[1] > 0) {
                            $filter = ['identifier' => $matches[1], 'type' => 'descendants'];
                        } else {
                            $filter = null;
                        }
                        $categlib = TikiLib::lib('categ');
                        $categs = $categlib->getCategories($filter, true, false);
                        $_REQUEST["$fields_prefix$f"][] = $categs[0]['categId'];
                    } elseif (preg_match('/preference\((.*)\)/', $autosavevalues[$i], $matches)) {
                        $_REQUEST["$fields_prefix$f"] = $prefs[$matches[1]];
                    } elseif (isset($transactionName) && preg_match('/#TSTEP\[(\d+)\]\[(\d+|name|pass)\]/', $autosavevalues[$i], $matches)) {
                        $traStep = $matches[1];
                        $traStepInsField = $matches[2];
                        if (preg_match('/\d+/', $matches[2])) {
                            $traStepInsField = "$fields_prefix$traStepInsField";
                        }
                        // FIXME replace $traStep with 'values'
                        $_REQUEST["$fields_prefix$f"] = str_replace($matches[0], $_SESSION[$transactionName][$traStep]['request'][$traStepInsField], $autosavevalues[$i]);
                    } elseif ($ff['type'] == 'e') {
                        $_REQUEST["$fields_prefix$f"][] = $autosavevalues[$i];
                    } else {
                        if (isset($params['levelupfields']) && in_array($f, $params['levelupfields'])) {
                            $current_levelup_val = $trklib->get_item_value($trackerId, $itemId, $f);
                            if ($autosavevalues[$i] <= $current_levelup_val) {
                                continue;
                            }
                        }
                        $_REQUEST["$fields_prefix$f"] = $autosavevalues[$i];
                    }
                }
            }
            if ($registration == 'y' && isset($userField) && isset($_REQUEST['name'])) {
                $_REQUEST["$fields_prefix$userField"] = $_REQUEST['name'];
            }

            foreach ($flds['data'] as $k => $field) {
                $handler = $factory->getHandler($field, $item_info);
                if ($handler) {
                    $value_field = $handler->getFieldData($_REQUEST);
                    $ins_fields['data'][$k] = array_merge($field, $value_field);
                    if (isset($ins_fields['data'][$k]['value'])) {		// add ins value into field if creating or editing item
                        $flds['data'][$k] = $ins_fields['data'][$k];	// to keep user input in case of errors (not only value)
                    }
                }
            }
            $cpt = 0;
            if (isset($fields)) {
                $fields_plugin = $fields;
            }
            if (! isset($itemId) && $tracker['oneUserItem'] == 'y' && $registration != 'y') {
                $itemId = $trklib->get_user_item($trackerId, $tracker);
            }

            if ($embedded == 'y' && isset($_REQUEST['page'])) {
                $ins_fields["data"][] = ['fieldId' => $embeddedId, 'value' => $_REQUEST['page']];
            }

            if (isset($userField)
              && (($registration == 'y' && isset($_REQUEST['name']))
                || (isset($transactionName) && isset($_SESSION[$transactionName]) && isset($_SESSION[$transactionName]['registrationName'])))) {
                $userFieldDef = $definition->getField($userField);
                if (isset($_REQUEST['name'])) {
                    $userFieldDef['value'] = $_REQUEST['name'];
                    if (isset($transactionName) && isset($_SESSION[$transactionName])) {
                        $_SESSION[$transactionName]['registrationName'] = $_REQUEST['name'];
                    }
                } elseif (isset($transactionName) && isset($_SESSION[$transactionName]) && isset($_SESSION[$transactionName]['registrationName'])) {
                    $userFieldDef['value'] = $_SESSION[$transactionName]['registrationName'];
                }
                $ins_fields['data'][] = $userFieldDef;
            }

            $ins_categs = 0; // important: non-array ins_categs means categories should remain unchanged
            $parent_categs_only = [];
            foreach ($ins_fields['data'] as $current_field) {
                if ($current_field['type'] == 'e' && isset($current_field['selected_categories'])) {
                    if (! is_array($ins_categs)) {
                        $ins_categs = [];
                    }
                    $ins_categs = array_merge($ins_categs, $current_field['selected_categories']);
                    $parent_categs_only[] = $current_field['options_array'][0];
                }
            }
            $categorized_fields = $definition->getCategorizedFields();
            /* ------------------------------------- End recup all values from REQUEST -------------- */

            /* ------------------------------------- Check field values for each type and presence of mandatory ones ------------------- */
            $field_errors = $trklib->check_field_values($ins_fields, $categorized_fields, $trackerId, empty($itemId) ? '' : $itemId);

            if ($prefs['feature_antibot'] === 'y' && $registration === 'y' && isset($_REQUEST['valerror'])) {
                // in_tracker session var checking is for tiki-register.php
                if (isset($_REQUEST['valerror'])) {
                    $rve = $_REQUEST['valerror'];
                    if (is_array($rve)) {
                        foreach ($rve as $ve) {
                            if (is_a($ve, 'RegistrationError')) {
                                if (isset($ve->field) && $ve->field == 'antibotcode') {
                                    $field_errors['err_antibot'] = 'y';

                                    continue;
                                }
                            }
                        }
                    } elseif (is_a($rve, 'RegistrationError')) {
                        if (isset($rve->field) && $rve->field == 'antibotcode') {
                            $field_errors['err_antibot'] = 'y';
                        }
                    }
                } else {
                    if (! $captchalib->validate()) {
                        $field_errors['err_antibot'] = 'y';
                    }
                }
            }

            // Check if antibot question was submited with the form (for anonymous users)
            if ($prefs['feature_antibot'] == 'y' && empty($user) && ($registration != 'y' || $prefs["user_register_prettytracker"] != 'y')) {
                if (! $captchalib->validate()) {
                    Feedback::error($captchalib->getErrors());
                    $field_errors['err_antibot'] = 'y';
                }
            }

            // check valid page name for wiki output if requested
            if (isset($outputtowiki) && ! empty($outputwiki)) {
                $newpagename = '';
                foreach ($ins_fields["data"] as $fl) {
                    if ($fl["fieldId"] == $outputtowiki) {
                        $newpagename = $fl["value"];
                    }
                    if ($fl["type"] == 'F') {
                        $newpagefreetags = $fl["value"];
                    }
                    $newpagefields[] = $fl["fieldId"];
                }
                if ($newpagename) {
                    if ($prefs['namespace_enabled'] == 'y' && ! empty($outputwikinamespace)) {
                        $newpagename = $outputwikinamespace . $prefs['namespace_separator'] . $newpagename;
                    }
                    if ($tikilib->page_exists($newpagename)) {
                        $field_errors['err_outputwiki'] = tra('The page to output the results to already exists. Try another name.');
                    }
                    $page_badchars_display = TikiLib::lib('wiki')->get_badchars();
                    if (TikiLib::lib('wiki')->contains_badchars($newName)) {
                        $field_errors['err_outputwiki'] = tr("The page to output the results to contains the following prohibited characters: %0. Try another name.", $page_badchars_display);
                    }
                } else {
                    unset($outputtowiki);
                }
            }
            if (count($field_errors['err_mandatory']) == 0 && count($field_errors['err_value']) == 0
                && empty($field_errors['err_antibot']) && empty($field_errors['err_outputwiki'])
                && ! isset($_REQUEST['tr_preview'])) {
                if (isset($_REQUEST['status'])) {
                    $status = $_REQUEST['status'];
                } elseif (isset($newstatus) && ($newstatus == 'o' || $newstatus == 'c' || $newstatus == 'p')) {
                    $status = $newstatus;
                } elseif (empty($itemId) && isset($tracker['newItemStatus'])) {
                    $status = $tracker['newItemStatus'];
                } else {
                    $status = '';
                }

                $saveThis = [	'trackerId' => $trackerId, 'request' => $_REQUEST, 'chosenGroup' => $chosenGroup,
                                    'registration' => $registration, 'registrationTrackerId' => $registrationTrackerId,
                                    'validateusers' => $validateusers, 'status' => $status,
                                    'ins_fields' => $ins_fields, 'itemId' => $itemId,
                                    'ins_categs' => $ins_categs, 'newItemRate' => $newItemRate,
                                    'skipUserCreation' => ! empty($skipUserCreation) && $skipUserCreation == 'y',
                                    'fieldsfill' => $fieldsfill, 'fieldsfillseparator' => $fieldsfillseparator, 'fill_line_cant' => $fill_line_cant,
                                    'fill_flds' => $fill_flds, 'fill_flds_defaults' => $fill_flds_defaults,
                                    ];
                //-- check if we are in a transaction
                if (isset($transactionName)) {
                    $transactionValues = [];
                    foreach ($ins_fields['data'] as $insField) {
                        if (isset($insField['value'])) {
                            $transactionValues['ins_' . $insField['fieldId']] = $insField['value'];
                        }
                    }
                    // NB array key needs a string ins_ prepended so array merge preserves the field id's
                    $_SESSION[$transactionName]['values'] = array_merge(
                        $_SESSION[$transactionName]['values'],
                        $transactionValues
                    );

                    if ($transactionFinalStep == 'y' && ! isset($_REQUEST['tr_previous'])) {
                        //-- final step: commit the transaction of registrations and tracker changes of all the transaction steps
                        foreach ($_SESSION[$transactionName]['values'] as $key => $val) {
                            $done = false;
                            $key = str_replace('ins_', '', $key);
                            foreach ($saveThis['ins_fields']['data'] as & $saveThisField) {
                                if ($saveThisField['fieldId'] == $key) {
                                    $saveThisField['value'] = $val;
                                    $done = true;

                                    break;
                                }
                            }
                            if (! $done) {
                                $fieldTemp = $definition->getField($key);
                                if ($fieldTemp) {
                                    $fieldTemp['value'] = $val;
                                    $saveThis['ins_fields']['data'][] = $fieldTemp;
                                } else {
                                    Feedback::error(
                                        tr(
                                            'Tracker Transaction: Field #%0 not found in fields parameter or template',
                                            $key
                                        )
                                    );
                                }
                            }
                        }
                        $rid = wikiplugin_tracker_save_item($saveThis);
                        unset($_SESSION[$transactionName]); // the tracker transaction can be closed
                    } else {
                        if (isset($_REQUEST['tr_previous'])) {
                            $_SESSION[$transactionName]['transactionStep'] = $transactionStep
                                - 1; // switch to the previous step
                            TikiLib::lib('access')->redirect($transactionPreviousURL);
                        } else {
                            $_SESSION[$transactionName]['transactionStep'] = $transactionStep
                                + 1; // switch to the next step
                            TikiLib::lib('access')->redirect($url[0]);
                        }
                    }
                } else {
                    // no transaction is used
                    $rid = wikiplugin_tracker_save_item($saveThis);
                }
                // now for wiki output if desired
                if (isset($outputtowiki) && ! empty($outputwiki)) {
                    // note that values will be raw - that is the limit of the capability of this feature for now
                    $newpageinfo = $tikilib->get_page_info($outputwiki);
                    $wikioutput = $newpageinfo["data"];
                    $newpagefields = $trklib->get_pretty_fieldIds($outputwiki, 'wiki', $prettyModifier, $trackerId);
                    $tracker_definition = Tracker_Definition::get($trackerId);
                    $wikioutput = str_replace('{$f_itemId}', $rid, $wikioutput);
                    foreach ($newpagefields as $lf) {
                        $field = $tracker_definition->getField($lf);
                        $lfpermname = $field['permName'];
                        $wikioutput = str_replace('{$f_' . $lf . '}', $trklib->get_item_value($trackerId, $rid, $lf), $wikioutput);
                        $wikioutput = str_replace('{$f_' . $lfpermname . '}', $trklib->get_item_value($trackerId, $rid, $lf), $wikioutput);
                    }
                    if (isset($registration)) {
                        $wikioutput = str_replace('{$register_login}', $user, $wikioutput);
                        $wikioutput = str_replace('{$register_email}', $_REQUEST['email'], $wikioutput);
                    }
                    $tikilib->create_page($newpagename, 0, $wikioutput, $tikilib->now, '', $user, $tikilib->get_ip_address());
                    $cat_desc = '';
                    $cat_type = 'wiki page';
                    $cat_name = $newpagename;
                    $cat_objid = $newpagename;
                    $cat_href = "tiki-index.php?page=" . urlencode($newpagename);
                    if (count($ins_categs)) {
                        $_REQUEST['cat_categories'] = $ins_categs;
                        $_REQUEST['cat_categorize'] = 'on';
                        include_once("categorize.php");
                    }
                    if (isset($newpagefreetags) && $newpagefreetags) {
                        $_REQUEST['freetag_string'] = $newpagefreetags;
                        include_once("freetag_apply.php");
                    }
                    if ($discarditem == 'y') {
                        $trklib->remove_tracker_item($rid);
                    } elseif ($outputwikirelation == 'y') {
                        TikiLib::lib('relation')->add_relation('tiki.wiki.linkeditem', 'wiki page', $newpagename, 'trackeritem', $rid);
                        TikiLib::lib('relation')->add_relation('tiki.wiki.linkedfield', 'wiki page', $newpagename, 'trackerfield', $outputtowiki);
                    }
                    if (empty($url)) {
                        $wikilib = TikiLib::lib('wiki');
                        $url[0] = $wikilib->sefurl($newpagename);
                    }
                }
                // end wiki output

                // send emails if email param is set and tracker_always_notify or something was changed (mail_data is set in \TrackerLib::send_replace_item_notifications)
                if (! empty($email) && ($prefs['tracker_always_notify'] === 'y' || ! empty($smarty->getTemplateVars('mail_data')))) {
                    // expose the pretty tracker fields to the email tpls
                    $item = $trklib->get_tracker_item($rid);	// get the new item values
                    foreach ($flds['data'] as $f) {
                        $prettyout = strip_tags(wikiplugin_tracker_render_value($f, $item));
                        $smarty->assign('f_' . $f['fieldId'], $prettyout);
                        $smarty->assign('f_' . $f['permName'], $prettyout);
                    }
                    // $email param in the form of: "from|to|template"
                    $emailOptions = preg_split("#\|#", $email);

                    // from:
                    $emailOptions[0] = reset(wikiplugin_tracker_process_email_recipients($emailOptions[0], $flds['data'], $item, $trackerId, $rid));

                    if (empty($emailOptions[0])) { // from is empty
                        $emailOptions[0] = $prefs['sender_email'];
                    }

                    // to:
                    if (empty($emailOptions[1])) { // to is empty?
                        $emailOptions[1][0][0] = $prefs['sender_email'];
                    } else {
                        // multiple recipients can be separated by a comma
                        $emailOptions[1] = explode(',', $emailOptions[1]);
                        foreach ($emailOptions[1] as $key => $recipient) {
                            $parsed = wikiplugin_tracker_process_email_recipients(trim($recipient), $flds['data'], $item, $trackerId, $rid);
                            $emailOptions[1][$key] = array_unique($parsed);
                        }
                    }

                    include_once('lib/webmail/tikimaillib.php');
                    $mail = new TikiMail();
                    $mail->setFrom($emailOptions[0]);

                    // collect the subject templates if they exist
                    if (! empty($emailOptions[2])) { //tpl
                        $emailOptions[2] = preg_split('/ *, */', $emailOptions[2]);
                        foreach ($emailOptions[2] as $ieo => $eo) {
                            if (strpos($eo, 'wiki:') !== 0) {
                                if (! preg_match('/\.tpl$/', $eo)) {		// template file
                                    $emailOptions[2][$ieo] = $eo . '.tpl';
                                }
                                $tplSubject[$ieo] = str_replace('.tpl', '_subject.tpl', $emailOptions[2][$ieo]);
                            } else {	// wiki template
                                if (! $tikilib->page_exists(substr($eo, 5))) {
                                    Feedback::error(tr('Missing wiki email template page "%0"', htmlspecialchars($eo)));
                                    $emailOptions[2][$ieo] = 'tracker_changed_notification.tpl';
                                } else {
                                    $subject_name = str_replace('tpl', 'subject tpl', $emailOptions[2][$ieo]);
                                    if ($tikilib->page_exists(substr($subject_name, 5))) {
                                        $tplSubject[$ieo] = $subject_name;
                                    }
                                }
                            }
                        }
                    } else {
                        $emailOptions[2] = ['tracker_changed_notification.tpl'];
                    }
                    if (empty($tplSubject)) {
                        $tplSubject = ['tracker_changed_notification_subject.tpl'];
                    }
                    $templateCounter = 0;
                    $subjectCounter = 0;

                    // Set all fields documented in https://doc.tiki.org/PluginTracker
                    $smarty->assign('mail_date', $tikilib->now);
                    $smarty->assign('mail_itemId', $rid);
                    $smarty->assign('mail_user', $user);
                    $smarty->assign('mail_item_desc', $trklib->get_isMain_value($trackerId, $rid));
                    $smarty->assign('mail_trackerId', $trackerId);
                    $smarty->assign('mail_trackerName', $tracker['name']);
                    $foo = parse_url($_SERVER["REQUEST_URI"]);
                    $mail_machine = $trklib->httpPrefix(true) . $foo["path"];
                    $smarty->assign('mail_machine', $mail_machine);
                    $parts = explode('/', $foo['path']);
                    if (count($parts) > 1) {
                        unset($parts[count($parts) - 1]);
                    }
                    $mail_machine_raw = $trklib->httpPrefix(true) . implode('/', $parts);
                    $smarty->assign('mail_machine_raw', $mail_machine_raw);
                    if (! isset($_SERVER["SERVER_NAME"])) {
                        $server_name = $_SERVER["SERVER_NAME"];
                    } else {
                        $server_name = $_SERVER["HTTP_HOST"];
                    }
                    $smarty->assign('server_name', $server_name);
                    if (empty($status)) {
                        $mail_status = $item_info['status'];
                    } else {
                        $mail_status = $status;
                    }
                    $smarty->assign('status', $mail_status);

                    $sentMails = [];

                    foreach ($emailOptions[1] as $ieo => $ueos) {
                        $mailDir = strpos($tplSubject[$subjectCounter], 'wiki:') !== 0 ? 'mail/' : '';
                        @$mail_data = $smarty->fetch($mailDir . $tplSubject[$subjectCounter]);
                        if (empty($mail_data)) {
                            $mail_data = tra('Tracker was modified at ') . $_SERVER['SERVER_NAME'];
                        } else {
                            $mail_data = trim(str_replace('&nbsp;', ' ', strip_tags($mail_data)));	// tidy
                        }
                        $mail->setSubject($mail_data);
                        $mailDir = strpos($emailOptions[2][$templateCounter], 'wiki:') !== 0 ? 'mail/' : '';	// wiki pages dont start with wiki:
                        $mail_data = $smarty->fetch($mailDir . $emailOptions[2][$templateCounter]);
                        if (isset($emailformat) && $emailformat === 'html') {
                            $mail->setHtml($mail_data, strip_tags($mail_data));
                        } else {
                            if (strpos($emailOptions[2][$templateCounter], 'wiki:') === 0) {
                                $mail_data = str_replace('&nbsp;', ' ', strip_tags($mail_data));
                            }
                            $mail->setText($mail_data);
                        }

                        $tplKey = '-' . $tplSubject[$subjectCounter] . '-' . $emailOptions[2][$templateCounter];

                        foreach ($ueos as $ueo) {
                            if (! in_array($ueo . $tplKey, $sentMails)) {
                                try {
                                    $mail->send($ueo);
                                    $sentMails[] = $ueo . $tplKey;
                                    $title = 'mail';
                                } catch (Laminas\Mail\Exception\ExceptionInterface $e) {
                                    $title = 'mail error';
                                }
                                if ($title == 'mail error') {
                                    // Log the email error at the tiki syslog
                                    $logslib = TikiLib::lib('logs');
                                    $logslib->add_log('mail error', 'plugin tracker email error / ' . $ueo . ' / item' . $rid);
                                } elseif ($title == 'mail' && $prefs['log_mail'] == 'y') {
                                    // Log the email at the tiki syslog
                                    $logslib = TikiLib::lib('logs');
                                    $logslib->add_log('mail', 'plugin tracker email sent / ' . $ueo . ' / item' . $rid);
                                }
                            }
                        }
                        if (isset($emailOptions[2][$templateCounter + 1])) {
                            ++$templateCounter;
                        }
                        if (isset($tplSubject[$subjectCounter + 1])) {
                            ++$subjectCounter;
                        }
                    }
                }
                if (empty($url)) {
                    if (! empty($_REQUEST['ajax_add'])) {	// called by tracker ItemLink fields when adding new list items
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        if ($prefs['feature_obzip'] == 'y') {
                            ob_start('ob_gzhandler');
                        } else {
                            ob_start();
                        }
                        // Need to add newly created itemId for item link selector
                        $ins_fields['itemId'] = $rid;
                        $access = TikiLib::lib('access');
                        $access->output_serialized($ins_fields);
                        ob_end_flush();
                        die;
                    } elseif (! empty($page)) {
                        $url = "tiki-index.php?page=" . urlencode($page);
                        if (! empty($itemId)) {
                            $url .= "&itemId=" . $itemId;
                        }
                        $url .= "&ok=y&iTRACKER=$iTRACKER";
                        $url .= "#wikiplugin_tracker$iTRACKER";
                        TikiLib::lib('access')->redirect($url);
                        exit;
                    }

                    return '';
                }
                $key = 0;
                foreach ($action as $key => $act) {
                    if (! empty($_REQUEST["action$key"])) {
                        break;
                    }
                }
                $itemIdPos = strpos($url[$key], 'itemId');
                if ($itemIdPos !== false) {
                    if (strstr($url[$key], '#itemId')) {
                        $url[$key] = str_replace('#itemId', $rid, $url[$key]);
                    } elseif (($itemIdPos + strlen('itemId') >= strlen($url[$key]) - 1) || (substr($url[$key], $itemIdPos + strlen('itemId'), 1) == "&")) {
                        // replace by the itemId if in the end (or -1: for backward compatibility so that "&itemId=" also works) or if it is followed by an '&'
                        $url[$key] = str_replace('itemId', 'itemId=' . $rid, $url[$key]);
                    }
                }

                if (isset($urlparams)) {
                    $i = 0;
                    foreach ($_REQUEST as $kk => $vv) {
                        $ins = preg_replace('/^(ins_)/', '', $kk); // replace the ins_ from the input field names to match with e.g. urlparams="1:2:3"
                        $vv = urlencode($vv);
                        if ($urlparams[0] === '*' || in_array($ins, $urlparams)) {
                            $ss = strstr($url[$key], '?') ? '&' : '?'; // if there is "?" already in the URL, use "&" to separate the params
                            $url[$key] .= "$ss$kk=$vv";
                        }
                        $i++;
                    }
                }

                $msg = trim($data);
                if (empty($msg)) {
                    $msg = tr('Form saved successfully.');
                }
                Feedback::success($msg);
                TikiLib::lib('access')->redirect($url[$key]);
                exit;
            } elseif (isset($_REQUEST['trackit']) and $_REQUEST['trackit'] == $trackerId) {
                $smarty->assign('wikiplugin_tracker', $trackerId);//used in vote plugin
            }
        } elseif ((empty($itemId) || $overwrite == 'y') && ! empty($values) || (! empty($_REQUEST['values']) and empty($_REQUEST['prefills']))) { // assign default values for each filedId specify
            if (empty($values)) { // url with values[]=x&values[] witouth the list of fields
                $values = $_REQUEST['values'];
            }
            if (! is_array($values)) {
                $values = [$values];
            }
            if (isset($fields)) {
                $fl = $fields;
                for ($j = 0, $count_fl = count($fl); $j < $count_fl; $j++) {
                    for ($i = 0, $count_flds = count($flds['data']); $i < $count_flds; $i++) {
                        if ($flds['data'][$i]['fieldId'] == $fl[$j] && isset($values[$j])) {
                            $flds['data'][$i]['value'] = $values[$j];
                        }
                    }
                }
            } else { // values contains all the fields value in the default order
                $i = 0;
                foreach ($values as $value) {
                    $flds['data'][$i++]['value'] = $value;
                }
            }
        } elseif (! empty($itemId)) {
            if (isset($fields) && empty($wiki) && empty($tpl)) {
                $fl = $fields;
                $filter = [];
                foreach ($flds['data'] as $f) {
                    if (in_array($f['fieldId'], $fl)) {
                        $filter[] = $f;
                    }
                }
            } else {
                $filter = &$flds['data'];
            }
            if (! empty($filter)) {
                foreach ($filter as $f) {
                    $filter2[$f['fieldId']] = $f;
                }
                $flds['data'] = $trklib->get_item_fields($trackerId, $itemId, $filter2, $itemUsers, true);
            }
            // todo: apply the values for fields with no values
        } else {
            if (isset($_REQUEST['values']) && isset($_REQUEST['prefills'])) { //url:prefills=1:2&values[]=x&values[]=y
                if (! is_array($_REQUEST['values'])) {
                    $_REQUEST['values'] = [$_REQUEST['values']];
                }
                $fl = preg_split('/:/', $_REQUEST['prefills']);
            } else {
                unset($fl);
            }
            for ($i = 0, $count_flds2 = count($flds['data']); $i < $count_flds2; $i++) {
                if (isset($fl) && ($j = array_search($flds['data'][$i]['fieldId'], $fl)) !== false) {
                    $flds['data'][$i]['value'] = $_REQUEST['values'][$j];
                } else {
                    // setting default value prevent dropdown default value working
                    $options = $flds['data'][$i]['options_array'];
                    if (! in_array($flds['data'][$i]['type'], ['d', 'D', 'R', 'M']) || count($options) === count(array_unique($options))) {
                        $flds['data'][$i]['value'] = ''; // initialize fields with blank values
                    }
                }
            }
        }

        // Check that individual fields are in the tracker
        if (! empty($fields)) {
            $fl = $fields;
            if ($sort == 'y') {
                $flds = $trklib->sort_fields($flds, $fl);
            }
            foreach ($fl as $l) {
                $ok = false;
                foreach ($flds['data'] as $f) {
                    if ($f['fieldId'] == $l) {
                        $ok = true;

                        break;
                    }
                }
                if (! $ok) {
                    $back .= '<div class="alert alert-warning"><strong>' . tra('Incorrect fieldId:') . ' ' . $l . '</strong>.<br> ' . tra("Please ensure you are using the correct field ID and that it is properly included in the template, if any.") . '</div>';
                }
            }
        } elseif (empty($fields) && empty($wiki) && empty($tpl)) {
            // in this case outf still be blank and needs to be filled
            foreach ($flds['data'] as $f) {
                $outf[] = $f['fieldId'];
            }
        }

        // if we have a transaction going on merge the session values with the stored item field values
        if (! empty($transactionName) && ! empty($_SESSION[$transactionName]['itemId'])) {
            foreach ($flds['data'] as $k => $field) {
                if (isset($_SESSION[$transactionName]['values']['ins_' . $field['fieldId']])) {
                    $flds['data'][$k]['value'] = $_SESSION[$transactionName]['values']['ins_' . $field['fieldId']];
                }
            }
        }
        // Check that multiple fill fields are in the tracker
        if (! empty($fieldsfill)) {
            foreach ($fill_fields as $l) {
                $ok = false;
                foreach ($fill_flds['data'] as $f) {
                    if ($f['fieldId'] == $l) {
                        $ok = true;

                        break;
                    }
                }
                if (! $ok) {
                    $back .= '<div class="alert alert-warning">' . tra('Incorrect fieldId:') . ' ' . $l . '</div>';
                }
            }
        }

        // Display warnings when needed

        if (count($field_errors['err_mandatory']) > 0 || count($field_errors['err_value']) > 0) {
            $back .= $smarty->fetch('tracker_error.tpl');
            $_REQUEST['error'] = 'y';

            if (count($field_errors['err_mandatory']) > 0) {
                $msg = tra('The following mandatory fields are missing');
                foreach ($field_errors['err_mandatory'] as $err) {
                    $msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' . $err['name'];
                }
                Feedback::error($msg);
            }
            if (count($field_errors['err_value']) > 0) {
                $msg = tra('Following fields are incorrect');
                foreach ($field_errors['err_value'] as $err) {
                    $msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' . $err['name'];
                }
                Feedback::error($msg);
            }

            if ($registration && ! empty($userField) && isset($_REQUEST['name'])
                && $_REQUEST['name'] === $userField['value'] && $_REQUEST['name'] === $user) {
                // if in registration and creating a user tracker item for the new user
                // remove the user if they did not complete the tracker correctly
                $userlib->remove_user($userField['value']);
                if ($prefs['eponymousGroups'] == 'y') {
                    // eponymous group will contain only this (former) user so remove that too
                    $userlib->remove_group($userField['value']);
                }

                $user = '';								// needed to re-include the captcha inputs
                $hidden_fieldId = [];				// remove hidden user fields which are otherwise required
                foreach ($flds['data'] as $k => $v) {	// remove the login field otherwise it gets rendered in the form also required
                    if ($v['fieldId'] == $userField['fieldId']) {
                        unset($flds['data'][$k]);
                    }
                }
            }
            if (isset($field_errors['err_antibot'])) {
                $_REQUEST['error'] = 'y';
            }
            if (isset($field_errors['err_outputwiki'])) {
                $smarty->loadPlugin('smarty_function_icon');
                $icon = smarty_function_icon(['name' => 'warning'], $smarty->getEmptyInternalTemplate());
                $back .= '<div class="alert alert-warning">' . $icon . ' ';
                $back .= $field_errors['err_outputwiki'];
                $back .= '</div><br />';
                $_REQUEST['error'] = 'y';
            }
            if (count($field_errors['err_mandatory']) > 0 || count($field_errors['err_value']) > 0 || isset($field_errors['err_antibot']) || isset($field_errors['err_outputwiki'])) {
                $smarty->assign('input_err', 'y');
            }
        }
        if (! empty($page)) {
            $smarty->assign_by_ref('tiki_p_admin_trackers', $perms['tiki_p_admin_trackers']);
        }
        if (! empty($params['_ajax_form_ins_id'])) {
            $headerlib = TikiLib::lib('header');
            $old_js['js'] = $headerlib->js;						// of tracker form JS into a function to initialise it when the dialog is created
            $old_js['jq_onready'] = $headerlib->jq_onready;
            $headerlib->clear_js();								// so store existing js for later and clear
        }

        if ($prefs['feature_jquery'] == 'y' && $prefs['feature_jquery_validation'] == 'y') {
            $validatorslib = TikiLib::lib('validators');
            $customvalidation = '';
            $customvalidation_m = '';
            if ($registration == 'y') {
                // email validation
                $customvalidation .= 'email: { ';
                if ($prefs['user_unique_email'] == 'y') {
                    $customvalidation .= '
						remote: {
								url: "validate-ajax.php",
								type: "post",
								data: {
									validator: "uniqueemail",
									input: function() { return $("#email").val(); }
								}
							},
					';
                }
                $customvalidation .= 'required: true, ';
                $customvalidation .= 'email: true }, ';
                $customvalidation_m .= 'email: { email: "' . tra("Invalid email")
                            . '", required: "' . tra("This field is required")
                            . '"}, ';
                // password validation
                $customvalidation .= 'pass: { ';
                $customvalidation .= 'required: true, ';
                $customvalidation .= 'remote: { ';
                $customvalidation .= 'url: "validate-ajax.php", ';
                $customvalidation .= 'type: "post", ';
                $customvalidation .= 'data: { ';
                $customvalidation .= 'validator: "password", ';
                $customvalidation .= 'input: function() { ';
                $customvalidation .= 'return $("#pass1").val(); ';
                $customvalidation .= '} } } ';
                $customvalidation .= '}, ';
                $customvalidation_m .= 'pass: { required: "' . tra("This field is required") . '"}, ';
                // password repeat validation
                $customvalidation .= 'passAgain: { equalTo: "#pass1" }, ';
                $customvalidation_m .= 'passAgain: { equalTo: "' . tra("Passwords do not match") . '"}, ';
                // username validation
                $customvalidation .= 'name: { ';
                $customvalidation .= 'required: true, ';
                $customvalidation .= 'remote: { ';
                $customvalidation .= 'url: "validate-ajax.php", ';
                $customvalidation .= 'type: "post", ';
                $customvalidation .= 'data: { ';
                $customvalidation .= 'validator: "username", ';
                $customvalidation .= 'input: function() { ';
                $customvalidation .= 'return $("#name").val(); ';
                $customvalidation .= '} } } ';
                $customvalidation .= '}, ';
                $customvalidation_m .= 'name: { required: "' . tra("This field is required") . '"}, ';
                if (extension_loaded('gd') && function_exists('imagepng') && function_exists('imageftbbox') && $prefs['feature_antibot'] == 'y' && empty($user) && $prefs['recaptcha_enabled'] != 'y') {
                    // antibot validation
                    $customvalidation .= '"captcha[input]": { ';
                    $customvalidation .= 'required: true, ';
                    $customvalidation .= 'remote: { ';
                    $customvalidation .= 'url: "validate-ajax.php", ';
                    $customvalidation .= 'type: "post", ';
                    $customvalidation .= 'data: { ';
                    $customvalidation .= 'validator: "captcha", ';
                    $customvalidation .= 'parameter: function() { ';
                    $customvalidation .= 'return $("#captchaId").val(); ';
                    $customvalidation .= '}, ';
                    $customvalidation .= 'input: function() { ';
                    $customvalidation .= 'return $("#antibotcode").val(); ';
                    $customvalidation .= '} } } ';
                    $customvalidation .= '}, ';
                    $customvalidation_m .= '"captcha[input]": { required: "' . tra("This field is required") . '"}, ';
                }
                if ($prefs['useRegisterPasscode'] == 'y') {
                    $customvalidation .= 'passcode: {
								required: true,
								remote: {
									url: "validate-ajax.php",
									type: "post",
									data: {
										validator: "passcode",
										input: function() {
											return $("#passcode").val();
											}
										}
									}
								}, ';
                    $customvalidation_m .= 'passcode: { required: "' . tra("This field is required") . '"}, ';
                }
            }
            $validationjs = $validatorslib->generateTrackerValidateJS($flds['data'], $fields_prefix, $customvalidation, $customvalidation_m);

            if (! empty($params['_ajax_form_ins_id']) && $params['_ajax_form_ins_id'] === 'group') {
                $headerlib->add_jq_onready("var ajaxTrackerValidation_group={validation:{" . $validationjs . '}};');		// return clean rules and messages object for ajax
            } else {
                $smarty->assign('validationjs', $validationjs);
                $back .= $smarty->fetch('tracker_validator.tpl');
            }
        }
        if ($params['rules'] === 'y' && $prefs['tracker_field_rules'] === 'y') {
            $js = TikiLib::lib('vuejs')->generateTrackerRulesJS($definition->getFields(), $params['rulesparent']);
            TikiLib::lib('header')->add_jq_onready($js);
        }

        if ($params['formtag'] == 'y') {
            // if we're using ajax, we need to know whether we're updating or creating
            $ajax_action = ! empty($itemId) ? 'data-ajax_action="update" data-item_id="' . $itemId . '"' : 'data-ajax_action="create"';

            //if using ajax, set data-attributes in the form so that we can retrieve them in javascript later
            $ajax_datas = $params['ajax'] == 'y' ? 'data-ajax="true" ' . $ajax_action . ' data-tracker_id="' . $params['trackerId'] . '"' : "";

            //check if tracker has custom form classes, else default to form-horizontal
            $formClasses = $tracker['useFormClasses'] == 'y' ? $tracker['formClasses'] : "form-horizontal";
            $back .= '<form class="' . $formClasses . '" name="editItemForm' . $iTRACKER . '" id="editItemForm' . $iTRACKER . '" enctype="multipart/form-data" method="post"' . (isset($target) ? ' target="' . $target . '"' : '') . ' action="' . $_SERVER['REQUEST_URI'] . '" ' . $ajax_datas . '><input type="hidden" name="trackit" value="' . $trackerId . '" />';
            $back .= '<input type="hidden" name="refresh" value="1" />';
        }
        $back .= smarty_function_ticket([], $smarty);
        $back .= '<input type="hidden" name="iTRACKER" value="' . $iTRACKER . '" />';
        if (isset($_REQUEST['page'])) {
            $back .= '<input type="hidden" name="page" value="' . $_REQUEST["page"] . '" />';
        }
        // for registration
        if (isset($_REQUEST['name'])) {
            $back .= '<input type="hidden" name="name" value="' . $_REQUEST["name"] . '" />';
        }
        if (isset($_REQUEST['pass'])) {
            $back .= '<input type="hidden" name="pass" value="' . $_REQUEST["pass"] . '" />';
            $back .= '<input type="hidden" name="passAgain" value="' . $_REQUEST["pass"] . '" />';
        }
        if (isset($_REQUEST['email'])) {
            $back .= '<input type="hidden" name="email" value="' . $_REQUEST["email"] . '" />';
        }
        if (isset($_REQUEST['antibotcode'])) {
            $back .= '<input type="hidden" name="antibotcode" value="' . $_REQUEST["antibotcode"] . '" />';
        }
        if (isset($_REQUEST['chosenGroup'])) { // for registration
            $back .= '<input type="hidden" name="chosenGroup" value="' . $_REQUEST["chosenGroup"] . '" />';
        }
        if (isset($_REQUEST['register'])) {
            $back .= '<input type="hidden" name="register" value="' . $_REQUEST["register"] . '" />';
        }
        if ($showtitle == 'y') {
            $back .= '<div class="h1">' . htmlspecialchars($tracker["name"]) . '</div>';
        }
        if ($showdesc == 'y' && $tracker['description']) {
            if ($tracker['descriptionIsParsed'] == 'y') {
                $back .= '<div class="wikitext">' . TikiLib::lib('parser')->parse_data($tracker['description']) . '</div><br />';
            } else {
                $back .= '<div class="wikitext">' . htmlspecialchars(tra($tracker["description"])) . '</div><br />';
            }
        }
        if (isset($_REQUEST['tr_preview'])) { // use for the computed and join fields
            $assocValues = [];
            $assocNumerics = [];
            foreach ($flds['data'] as $f) {
                if (empty($f['value']) && ($f['type'] == 'u' || $f['type'] == 'g' || $f['type'] == 'I') && ($f['options_array'][0] == '1' || $f['options_array'][0] == '2')) { //need to fill the selector fields for the join
                    $f['value'] = ($f['type'] == 'I') ? $tikilib->get_ip_address() : (($f['type'] == 'g') ? $group : $user);
                }
                $assocValues[$f['fieldId']] = $f['value'];
                $assocNumerics[$f['fieldId']] = preg_replace('/[^0-9\.\+]/', '', $f['value']); // get rid off the $ and such unit
            }
        }

        if (! empty($itemId)) {
            $item = ['itemId' => $itemId, 'trackerId' => $trackerId];
        } else {
            $item = ['itemId' => ''];
        }
        foreach ($flds['data'] as $i => $f) { // collect additional infos
            if (in_array($f['fieldId'], $outf)) {
                $flds['data'][$i]['ins_id'] = ($f['type'] == 'e') ? 'ins_' . $f['fieldId'] : $fields_prefix . $f['fieldId'];
                if (($f['isHidden'] == 'c' || $f['isHidden'] == 'p' || $f['isHidden'] == 'a') && ! empty($itemId) && ! isset($item['creators'])) {
                    $item['creators'] = $trklib->get_item_creators($trackerId, $itemId);
                }

                // if we are doing a transaction then pick up values "saved" so far
                if (! empty($transactionName) && isset($_SESSION[$transactionName]['values']['ins_' . $f['fieldId']])) {
                    $item[$f['fieldId']] = $_SESSION[$transactionName]['values']['ins_' . $f['fieldId']];
                } else {
                    $item[$f['fieldId']] = $f['value'];
                }
            }
        }
        if (! empty($showstatus) && $showstatus == 'y') {
            $status_types = $trklib->status_types();
            $smarty->assign_by_ref('status_types', $status_types);
            $smarty->assign('form_status', 'status');
            $smarty->assign_by_ref('tracker', $tracker);
            if (! empty($item_info)) {
                $smarty->assign_by_ref('item', $item_info);
            }
            $status_input = $smarty->fetch('tracker_status_input.tpl');
        }

        $labelclass = 'col-md-3';
        $inputclass = 'col-md-9';
        $buttonclass = 'col-md-9 offset-3';

        if ($registration == "y") {
            $back .= '<input type="hidden" name="register" value="Register">';
            $labelclass = 'col-sm-4';
            $inputclass = 'col-sm-8';
            $buttonclass = 'col-sm-8 offset-4';
        }

        // Loop on tracker fields and display form
        if (empty($tpl) && empty($wiki)) {
            $back .= '<div class="wikiplugin_tracker">';
            if (! empty($showstatus) && $showstatus == 'y') {
                $back .= '<div class="alert alert-info">' . tra('Status') . $status_input . '</div>'; // <tr><td>'.tra('Status').'</td><td>'.$status_input.'</td></tr>
            }
            if ($registration == 'y' && $prefs["user_register_prettytracker"] != 'y') {
                $back .= $smarty->fetch('register-form.tpl');
            }
        } else {
            $back .= '<div class="wikiplugin_tracker">';
            if (! empty($showstatus) && $showstatus == 'y') {
                $smarty->assign_by_ref('f_status_input', $status_input);
            }
        }
        $backLength0 = strlen($back);

        $datepicker = false;

        // https://doc.tiki.org/Pretty+Tracker states that also internal trackerfield names can be used
        /*
        {$f_created}: created date
        {$f_status_input}: status input field (Value already set above)
        {$f_status}: status (output)
        {$f_itemId}: the item id
        {$f_lastmodif}: last modified date (this will display unix date, for human readable date look below)
        (In Tiki 8 onwards) {$itemoff}: the iteration number of each item
        {$tr_offset}: the offset of the item, i.e. this is the nth item of the total number of x items (TODO)
        */
        if (! empty($item_info)) {
            $smarty->assign('f_created', $item_info['created']);
            $smarty->assign('f_status', $item_info['status']);
            $smarty->assign('f_itemId', $item_info['itemId']);
            $smarty->assign('f_lastmodif', $item_info['lastModif']);
        }

        foreach ($flds['data'] as $f) {
            if (! empty($transactionName)) {
                $renderedField = wikiplugin_tracker_render_input(
                    $f,
                    $item,
                    $dynamicSave,
                    $_SESSION[$transactionName]['values']
                );
            } else {
                $renderedField = wikiplugin_tracker_render_input($f, $item, $dynamicSave);
            }
            if (! in_array($f['fieldId'], $auto_fieldId) && in_array($f['fieldId'], $hidden_fieldId)) {
                // Show in hidden form
                $back .= '<span style="display:none;">' . $renderedField . '</span>';
            } elseif (! in_array($f['fieldId'], $auto_fieldId) && in_array($f['fieldId'], $outf)) {
                if ($showmandatory == 'y' and $f['isMandatory'] == 'y') {
                    $onemandatory = true;
                }
                if ($f['type'] == 'A') {
                    $smarty->assign_by_ref('tiki_p_attach_trackers', $perms['tiki_p_attach_trackers']);
                }
                if (! empty($tpl) || ! empty($wiki)) {
                    if ($prettyModifier[$f['fieldId']] == "output") { //check if modifier is set to "output" ( set in getPrettyFieldIds() in trackerlib )
                        $prettyout = '<span class="outputPretty" id="track_' . $f['fieldId'] . '" name="track_' . $f['fieldId'] . '">' . wikiplugin_tracker_render_value($f, $item) . '</span>';
                        $smarty->assign('f_' . $f['fieldId'], $prettyout);
                        $smarty->assign('f_' . $f['permName'], $prettyout);
                    } elseif (! empty($f['readonly'])) {
                        $prettyout = '<span class="outputPretty" id="track_' . $f['fieldId'] . '" name="track_' . $f['fieldId'] . '">' . wikiplugin_tracker_render_value($f, $item) . '</span>';
                        $smarty->assign('f_' . $f['fieldId'], $prettyout);
                        $smarty->assign('f_' . $f['permName'], $prettyout);
                    } else {
                        $mand = ($showmandatory == 'y' and $f['isMandatory'] == 'y') ? "&nbsp;<strong class='mandatory_star text-danger tips' title=':" . tra("This field is mandatory") . "'>*</strong>&nbsp;" : '';
                        if ($showfielddesc === 'y' && ! empty($f['description'])) {
                            $desc = $f['descriptionIsParsed'] == 'y' ? TikiLib::lib('parser')->parse_data($f['description']) : htmlspecialchars(tra($f['description']));
                            $desc = '<div class="trackerplugindesc">' . $desc . '</div>';
                        } else {
                            $desc = '';
                        }
                        if (! empty($prettyModifier[$f['fieldId']])) { // check if a template was specified in prettyModifier
                            $smarty->assign("field_name", $f['name']);
                            $smarty->assign("field_id", $f['fieldId']);
                            $smarty->assign("permname", $f['permName']);
                            $smarty->assign("mandatory_sym", $mand);
                            $smarty->assign("field_input", $renderedField);
                            $smarty->assign("description", $desc);
                            $smarty->assign("field_type", $f['type']);
                            $prettyout = $smarty->fetch($prettyModifier[$f['fieldId']]); //fetch template identified in prettyModifier
                        } else {
                            $prettyout = $renderedField . $mand . $desc;
                        }
                        $smarty->assign('f_' . $f['fieldId'], $prettyout);
                        $smarty->assign('f_' . $f['permName'], $prettyout);
                    }
                } else {
                    $back .= '<div class="form-group row tracker_input_label tracker_field' . $f['fieldId'] . '"'; // <tr><td class="tracker_input_label"

                    // If type is has a samerow param and samerow is "No", show text on one line and the input field on the next
                    $isTextOnSameRow = true;
                    switch ($f['type']) {
                        case 't':	// Text field
                        case 'n':	// Numeric field
                        case 'b':	// Currency
                            if (empty($f['options_array']) || (isset($f['options_array']['0']) && strlen($f['options_array']['0']) == 0)) {
                                // Use default
                                //	Pending: Unable to locate the object to check to determine the default (in the tracker field definitions). Hardcode true. Arild
                                $isTextOnSameRow = true;
                            } else {
                                $isTextOnSameRow = (int)$f['options_array']['0'] != 0;
                            }

                            break;
                        case 'a':	// Text area
                            $isTextOnSameRow = true;
                            if (isset($f['options_array']['8'])) {
                                if (empty($f['options_array']) || (isset($f['options_array']['8']) && strlen($f['options_array']['8']) == 0)) {
                                    // Use default
                                    //	Pending: Unable to locate the object to check to determine the default (in the tracker field definitions). Hardcode true. Arild
                                    $isTextOnSameRow = true;
                                } else {
                                    $isTextOnSameRow = (int)$f['options_array']['8'] != 0;
                                }
                            }

                            break;
                    }

                    if (! empty($colwidth)) {
                        $back .= " width='" . $colwidth . "'";
                    }

                    // If on a different row the label should use all available width
                    $back .= '><label class="';
                    if ($isTextOnSameRow) {
                        $back .= $labelclass;
                        if ($f['type'] == 'h') {
                            $back .= ' h' . $f['options_map']['level'];
                        } else {
                            $back .= ' col-form-label';
                        }
                    } else {
                        $back .= 'col-md-12';
                    }
                    $back .= '" for="' . $f['ins_id'] . '">' . wikiplugin_tracker_name($f['fieldId'], tra($f['name']), $field_errors);

                    if ($showmandatory == 'y' and $f['isMandatory'] == 'y') {
                        $back .= " <strong class='mandatory_star text-danger tips' title=':" . tra('This field is mandatory') . "' >*</strong> ";
                    }
                    $back .= '</label>';
                    // If use different lines, add a line break.
                    // Otherwise a new column
                    if (! $isTextOnSameRow) {
                        $back .= "<br/>";
                    } else {
                        $back .= '<div class="' . $inputclass . ' tracker_input_value tracker_field' . $f['fieldId'] . '">';
                    }

                    $back .= $renderedField;
                    $back .= '</div>'; // chibaguy added /divs

                    if ($f['type'] === 'j') {
                        $datepicker = true;
                    }

                    if ($isTextOnSameRow) {
                        $back .= '</div>';
                    }
                }

                if ($f['type'] != 'S' && empty($tpl) && empty($wiki)) {
                    if ($showfieldsdesc == 'y' && $f['description']) {
                        $back .= '<div class="form-group row tracker-form-text tracker_field' . $f['fieldId'] . ' ">';
                        {
                            $back .= '<div class="col-md-9 offset-md-3 trackerplugindesc form-text">';
                        if ($f['descriptionIsParsed'] == 'y') {
                            $back .= TikiLib::lib('parser')->parse_data($f['description']);
                        } else {
                            $back .= htmlspecialchars(tra($f['description']));
                        }
                            $back .= '</div>';
                        }
                        $back .= '</div>';
                    }
                }
            }
        }

        if ($datepicker) {
            $smarty->loadPlugin('smarty_function_js_insert_icon');
            $back .= smarty_function_js_insert_icon(['type' => "jscalendar"], $smarty->getEmptyInternalTemplate());
        }

        if (! empty($tpl)) {
            $smarty->security = true;
            $back .= $smarty->fetch($tpl);
        } elseif (! empty($wiki)) {
            $smarty->security = true;
            if ($tikilib->page_exists($wiki)) {
                $back .= $smarty->fetch('wiki:' . $wiki);
            } else {
                $back .= '<span class="alert-warning">' . tr('Missing wiki template page "%0"', htmlspecialchars($wiki)) . '</span>';
            }
        }
        if (isset($params['fieldsfill']) && ! empty($params['fieldsfill']) && empty($itemId)) {
            $back .= '<div class="form-group row ins_fill"><label class="col-md-3 col-form-label" for="ins_fill">' . tra("Insert one item per line:")
                . '<br />'
                . '<br />'
                . '<br />'
                . '</label>';
            $back .= <<<FILL
<div class="col-md-9 tracker_input_value" >
	<input type="hidden" value="" name="mode_wysiwyg"/>
	<input type="hidden" value="" name="mode_normal"/>
	<div class="edit-zone">
		<textarea id="ins_fill" name="ins_fill" class="wikiedit form-control" data-syntax="" data-codemirror="" onkeyup="" rows="15" >
		</textarea>
	</div>
	<input type="hidden" value="n" name="wysiwyg"/>
	<div name="ins_fill_desc" class="trackerplugindesc" >
FILL;
            $back .= sprintf(tra('Each line is a list of %d field values separated with: %s'), $fill_line_cant, htmlspecialchars($fieldsfillseparator));
            $back .= '	</div>';
            $back .= '	<div name="ins_fill_desc2" class="trackerplugindesc" >' . htmlspecialchars(implode($fieldsfillseparator, $fieldsfillnames));
            $back .= '	</div>';
            $back .= '</div>';
        }
        if ($prefs['feature_antibot'] == 'y' && (empty($user) || (! empty($user) && isset($_REQUEST['error']) && $_REQUEST['error'] == 'y'))) {
            $smarty->assign('showantibot', true);
        }

        $smarty->assign('showmandatory', $showmandatory);

        if ($prefs['feature_antibot'] == 'y' && empty($user)
            && (! isset($transactionStep) || $transactionStep == 0)
            && $params['formtag'] != 'n'
            && ($registration != 'y' || $prefs["user_register_prettytracker"] != 'y')
            ) {
            // in_tracker session var checking is for tiki-register.php
            $smarty->assign('antibot_table', empty($wiki) && empty($tpl) ? 'n' : 'y');
            $captchalib = TikiLib::lib('captcha');
            $smarty->assign('captchalib', $captchalib);
            if ($registration == 'y') {
                $smarty->assign('form', 'register');
            }
            $back .= $smarty->fetch('antibot.tpl');
        }
        $back .= '</div>';

        if ($params['formtag'] == 'y') {
            if (empty($wiki) && empty($tpl)) {
                $back .= '<div class="form-group row"><div class="input_submit_container btn-bar ' . $buttonclass . '">';
            } else {
                $back .= '<div class="form-group row"><div class="input_submit_container btn-bar">';
            }

            if (! empty($transactionPreviousLabel) && ! empty($transactionPreviousURL)) {
                $back .= '<input class="btn btn-secondary button submit previous" type="submit" name="tr_previous" value="'
                    . tra($transactionPreviousLabel) . '"/> ';
            }
            if (! empty($reset)) {
                $back .= '<input class="btn btn-warning button reset" type="reset" name="tr_reset" value="' . tra($reset) . '" />';
            }
            if (! empty($preview)) {
                $back .= '<input class="btn btn-primary button submit preview" type="submit" name="tr_preview" value="' . tra($preview) . '" />';
            }
            foreach ($action as $key => $act) {
                $back .= '<input class="button submit mx-2 ' . $action_style[$key] . '" type="submit" name="action' . $key . '" value="' . tra($act) . '" onclick="needToConfirm=false" />';
            }
            $back .= '</div></div>';
        }
        if ($showmandatory == 'y' and $onemandatory) {
            $back .= "<br><br><div class='form-group'>";
            if (empty($wiki) && empty($tpl)) {
                $back .= "<div class='" . $buttonclass . "'><div class='text-center alert alert-danger'><em>" . tra("Fields marked with an * are mandatory.") . "</em></div></div>";
            } else {
                $back .= "<div class='text-center alert alert-danger'><em>" . tra("Fields marked with an * are mandatory.") . "</em></div>";
            }
            $back .= "</div>";
        }
        if ($params['formtag'] == 'y') {
            $back .= '</form>';
        }

        if (! empty($params['_ajax_form_ins_id'])) {	// save new js in a function for the form init fn
            $headerlib->add_js(' var ajaxTrackerFormInit_' . $params['_ajax_form_ins_id'] . ' = function() {' . $headerlib->output_js(false) . '}', 10);

            // put back the pre-existing js
            $headerlib->js = array_merge($headerlib->js, $old_js['js']);
            $headerlib->jq_onready = array_merge($headerlib->jq_onready, $old_js['jq_onready']);
        }

        return $back;
    }
    if (isset($_REQUEST['trackit']) and $_REQUEST['trackit'] == $trackerId) {
        $smarty->assign('wikiplugin_tracker', $trackerId);//used in vote plugin
    }
    $id = ' id="wikiplugin_tracker' . $iTRACKER . '"';
    if ($showtitle == 'y') {
        $back .= '<div class="h1"' . $id . '>' . htmlspecialchars($tracker["name"]) . '</div>';
        $id = '';
    }
    if ($showdesc == 'y') {
        $back .= '<div class="wikitext"' . $id . '>' . htmlspecialchars($tracker["description"]) . '</div><br />';
        $id = '';
    }
    $back .= "<div$id>" . $parserlib->parse_data($data) . '</div>';

    return $back;
}

function wikiplugin_tracker_render_input($f, $item, $dynamicSave, $requestData = [])
{
    $definition = Tracker_Definition::get($f['trackerId']);

    if (! $definition) {
        return '';
    }

    $handler = $definition->getFieldFactory()->getHandler($f, $item);

    if (! $handler) {
        return '';
    }

    if (! $item['itemId']) {
        // Non-selected items have not been processed
        $f = array_merge($f, $handler->getFieldData($requestData));
        $handler = TikiLib::lib("trk")->get_field_handler($f, $item);
    }

    $input = $handler->renderInput(['inTable' => 'n', 'pluginTracker' => 'y']);

    if ($dynamicSave && $item['itemId']) {
        $input = new Tiki_Render_Editable(
            $input,
            [
                'layout' => 'block',
                'object_store_url' => [
                    'controller' => 'tracker',
                    'action' => 'update_item',
                    'trackerId' => $f['trackerId'],
                    'itemId' => $item['itemId'],
                ],
            ]
        );
    }

    return $input;
}

function wikiplugin_tracker_render_value($f, $item)
{
    $trklib = TikiLib::lib('trk');

    $handler = $trklib->get_field_handler($f, $item);

    return $handler->renderOutput(['inTable' => 'n']);
}

/**
 * Convert an email parameter component into a real email address or addresses.
 *
 * @param string $emailOrField   int for a fieldId of UserSelector, GroupSelector, ItemsList, Email field, string for a username or email already
 * @param array $fields          tracker fields
 * @param array $item            item field values
 * @param int $trackerId
 * @param int $itemId
 *
 * @return array of emails
 */
function wikiplugin_tracker_process_email_recipients($emailOrField, $fields, $item, $trackerId, $itemId)
{
    $output = $emailOrField;

    // numeric so is a fieldId
    if (is_numeric($emailOrField)) {
        $f = [];
        foreach ($fields as $f) {
            if ($f['fieldId'] == $emailOrField) {
                break;
            }
        }
        if (empty($f)) {
            $f['type'] = '';
        }
        switch ($f['type']) {
            case 'l':
                $output = wikiplugin_tracker_render_value($f, $item);

                break;
            case 'u':
                $users = empty($item[$emailOrField]) ? '' : $item[$emailOrField];
                $output = TikiLib::lib('trk')->parse_user_field($users);

                break;
            case 'g':
                $group = empty($item[$emailOrField]) ? '' : $item[$emailOrField];
                $output = TikiLib::lib('user')->get_members($group);

                break;
            default:
                $output = TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $emailOrField);
        }
    }

    if (! is_array($output)) {
        $output = [$output];
    }

    foreach ($output as &$single) {
        $single = trim($single);
        // string but not an email yet, therefore a username
        if (! empty($single) && ! strstr($single, '@')) {
            $email = TikiLib::lib('user')->get_user_email($single);
            if ($email) {
                $single = $email;
            } elseif ($single === 'createdBy' || $single === 'lastModifBy') {
                $email = TikiLib::lib('user')->get_user_email($item[$single]);
                if ($email) {
                    $single = $email;
                }
            }
        }
    }

    $output = array_filter($output);

    return $output;
}

function wikiplugin_tracker_save_item($trackerSavedState)
{
    global $user;
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');

    $numVarOk = extract($trackerSavedState, EXTR_SKIP);
    if (! isset($trackerId)) {
        $trackerId = null;
    }
    if (! isset($itemId)) {
        $itemId = null;
    }
    if (! isset($ins_fields)) {
        $ins_fields = null;
    }
    if (! isset($status)) {
        $status = "";
    }
    if (! isset($ins_categs)) {
        $ins_categs = 0;
    }

    /* ---------------- check registration and create new user if requested ---------------- */
    if (isset($registration) && $registration == 'y' && isset($request['register']) && empty($skipUserCreation)) {
        $registrationlib = TikiLib::lib('registration');
        $req = $request;
        // if $chosenGroup was empty, we could try to guess it
        // for that one should implement the inverse of usrlib->get_tracker_usergroup()
        $req['chosenGroup'] = $chosenGroup;
        if (isset($validateusers) && ($validateusers != $registrationlib->merged_prefs['validateUsers'])) {
            $auxValidateUsers = $registrationlib->merged_prefs['validateUsers'];
            $registrationlib->merged_prefs['validateUsers'] = $validateusers;
            $result = $registrationlib->register_new_user($req);
            $registrationlib->merged_prefs['validateUsers'] = $auxValidateUsers;
        } else {
            $result = $registrationlib->register_new_user($req);
        }
        if (is_a($result, "RegistrationError")) {
            $smarty->assign('msg', $result->msg);
            $smarty->assign('errortype', 0);
            $smarty->display("error.tpl");
            die;
        }
    }
    /* ------------------------------------- save the item ---------------------------------- */
    $tx = TikiDb::get()->begin();
    //tracker item created here
    if (! empty($fieldsfill) && ! empty($request['ins_fill'])) {	// We create multiple items
        $fill_lines = explode("\n", $request['ins_fill']);
        foreach ($fill_lines as $fill_line) {
            if (trim($fill_line) == '') {	// Ignore blank lines
                continue;
            }
            $fill_line_item = explode($fieldsfillseparator, $fill_line, $fill_line_cant);	// Extra fields are merged with the last field. this avoids data loss and permits a last text field with commas or whichever separator is chosen
            $rid = $trklib->replace_item($trackerId, $itemId, $ins_fields, $status, $ins_categs);
            for ($i = 0; $i < $fill_line_cant; $i++) {
                if ($fill_line_item[$i] != '') {
                    $fill_item = trim($fill_line_item[$i]);
                } else {
                    $fill_item = $fill_flds_defaults[$i];
                }
                $fill_rid = $trklib->modify_field($rid, $fill_flds['data'][$i]['fieldId'], $fill_item);
            }
            if (is_array($ins_categs)) {
                if ($registration == 'y' && empty($item_info)) {
                    $override_perms = true;
                } else {
                    $override_perms = false;
                }
                $trklib->categorized_item($trackerId, $rid, $mainfield, $ins_categs, $parent_categs_only, $override_perms);
            }
            if (isset($newItemRate)) {
                $trklib->replace_rating($trackerId, $rid, $newItemRateField, $user, $newItemRate);
            }
        }
    } else {
        if (isset($registration) && $registration == 'y' && $_SERVER['REQUEST_METHOD'] != 'POST') {
            return false;
        }
        $rid = $trklib->replace_item($trackerId, $itemId, $ins_fields, $status, $ins_categs);
        if (is_array($ins_categs)) {
            if ($registration == 'y' && empty($item_info)) {
                $override_perms = true;
            } else {
                $override_perms = false;
            }
            $trklib->categorized_item($trackerId, $rid, $mainfield, $ins_categs, $parent_categs_only, $override_perms);
        }
        if (isset($newItemRate)) {
            $trklib->replace_rating($trackerId, $rid, $newItemRateField, $user, $newItemRate);
        }
    }
    $tx->commit();

    return $rid;
    /* ------------------------------------- end save the item ---------------------------------- */
}
