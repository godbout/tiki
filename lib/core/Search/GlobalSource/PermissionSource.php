<?php

class Search_GlobalSource_PermissionSource implements Search_GlobalSource_Interface
{
	private $perms;
	private $additionalCheck;

	function __construct(Perms $perms, $additionalCheck = null)
	{
		$this->perms = $perms;
		$this->additionalCheck = $additionalCheck;
	}

	function getProvidedFields()
	{
		return array('allowed_groups');
	}

	function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = array())
	{
		$groups = array();

		if (isset($data['view_permission'])) {
			$viewPermission = $data['view_permission']->getValue();

			$groups = array_merge($groups, $this->getAllowedGroups($objectType, $objectId, $viewPermission));
		}
		
		if (isset($data['parent_view_permission'], $data['parent_object_id'], $data['parent_object_type'])) {
			$viewPermission = $data['parent_view_permission']->getValue();
			$objectId = $data['parent_object_id']->getValue();
			$objectType = $data['parent_object_type']->getValue();

			$groups = array_merge($groups, $this->getAllowedGroups($objectType, $objectId, $viewPermission));
		}

		return array(
			'allowed_groups' => $typeFactory->multivalue(array_unique($groups)),
		);
	}
	
	private function getAllowedGroups($objectType, $objectId, $viewPermission)
	{
		$accessor = $this->perms->getAccessor(array(
			'type' => $objectType,
			'object' => $objectId,
		));

		$groups = array();
		foreach ($this->getCheckList($accessor) as $groupName) {
			$accessor->setGroups(array($groupName));
			
			if ($accessor->$viewPermission) {
				$groups[] = $groupName;
			}
		}

		return $groups;
	}

	private function getCheckList($accessor)
	{
		$toCheck = $accessor->getResolver()->applicableGroups();

		if ($this->additionalCheck) {
			$toCheck[] = $this->additionalCheck;
		}

		return $toCheck;
	}
}

