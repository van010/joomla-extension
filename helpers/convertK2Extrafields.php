<?php 

class convertK2ExtraFieldValue{

    function convertExtraField()
	{
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);

		// get the field will be reset and make new file xml.
		if (isset($syncParams->extraGroup) && is_array($syncParams->extraGroup) && count($syncParams->extraGroup)) {
			$count = 0;
			foreach ($syncParams->extraGroup as $g => $gv) {
				//$assoc = $this->checkAssociation($g, 'extraGroup');
				//if(!$assoc) {
				if ($gv != 'ignore') {
					$this->createContentTypeManifest($g, $syncParams);
					$count++;
				}
				//}
			}
			if ($count) {
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $count));
			}
		}
	}

    // convert with extrafield
	function convertExtraField2()
	{
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
        $migrator = new JADataMigrator();
		// get the field will be reset and make new file xml.
		if (isset($syncParams->extraGroup) && count((array)$syncParams->extraGroup)) {
			$count = 0;
			foreach ($syncParams->extraGroup as $g => $gv) {
				// $assoc = $this->checkAssociation($g, 'extraGroup');
				$assoc = $migrator->checkExtraGroupAssoc($g);
				if (!$assoc) {
					if ($gv != 'ignore') {
						$this->createContentExtraField($g, $syncParams);
						$count++;
					}
				}
			}
			if ($count) {
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $count));
			}
		}
	}

    function createContentTypeManifest($groupid, $syncParams)
	{
		// Processing to make a new xml file. always make to check for new field.
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields_groups'))->where('id=' . $db->quote($groupid));
		$db->setQuery($query);
		$group = $db->loadObject();
		if (!$group) return;

		// select extra fields.
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields', 'a'));
		$query->where('a.group =' . $db->quote($groupid));
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		if ($syncParams->extraGroup->{$groupid} != 'auto') {
			$filename = $syncParams->extraGroup->{$groupid};
		} else {
			$filename = $syncParams->exgipName->{$groupid};
		}
		$cttname = JADataMigrator::utf8tolatin(trim($filename) == '' ? $group->name : trim($filename));

		$exfieldgroupname = JFile::makeSafe($cttname . '.xml');
		$buffer = '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
			'<form>' . "\n" .
			"\t" . '<type>' . $cttname . '</type>' . "\n" .
			"\t" . '<title><![CDATA[' . $group->name . ']]></title>' . "\n" .
			"\t" . '<fields name="attribs">' . "\n" .
			"\t" . "\t" . '<fieldset name="content_meta" label="' . (htmlentities($group->name)) . '">' . "\n";
		$buffer .= "
			<!--IS REQUIRED FIELD-->
			<field name=\"ctm_content_type\"
						type=\"hidden\"
						default=\"{$cttname}\"
						label=\"PLG_JACONTENT_TYPE_CONTENT_TYPE_LABEL\"
						description=\"PLG_JACONTENT_TYPE_CONTENT_TYPE_DESC\"/>
				<!--IS REQUIRED FIELD--> \n";

		$languageTexts = array();
		for ($i = 0; $i < count($fields); $i++) {
			$field = $fields[$i];
			if (isset($syncParams->extraField->{$groupid}->{$field->id}) && $syncParams->extraField->{$groupid}->{$field->id} == 'ignore')
				continue;

			if ($field->type == 'header') {
				continue;
			}

			if (array_key_exists($field->id, (array) $syncParams->extraField->{$groupid})) {
				if ($syncParams->extraField->{$groupid}->{$field->id} != 'auto') {
					$fieldname = trim($syncParams->extraField->{$groupid}->{$field->id});
				} else {
					$fieldname = trim(@$syncParams->exfipName->{$groupid}->{$field->id});
				}
			}
			if (empty($fieldname)) $fieldname = $field->name;

            $migrator = new JADataMigrator();
			$migrator->addAssociation($field->id, 'extraField', $fieldname);

			$field->value = json_decode($field->value, false);

			switch ($field->type) {
				case 'textfield':
				case 'labels':
					$field->type = 'text';
					break;
				case 'select':
					$field->type = 'list';
					$field->multiple = 'false';
					break;
				case 'multipleSelect':
					$field->type = 'list';
					$field->multiple = '1';
					break;
				case 'radio':
					break;
				case 'image':
					$field->type = 'media';
					break;
				case 'link':
					$field->type = 'url';
					break;
				case 'date':
					$field->type = 'calendar';
					break;
				case 'csv':
					$field->type = 'file';
					break;
			}
			$fieldname = JADataMigrator::utf8tolatin($fieldname);
			$label = 'PLG_JACONTENT_TYPE_' . strtoupper($fieldname) . '_LABEL';
			$desc = 'PLG_JACONTENT_TYPE_' . strtoupper($fieldname) . '_DESC';
			$languageTexts[] = $label . '="' . $field->name . '"';
			$languageTexts[] = $desc . '="' . $field->name . '"';
			$buffer .= "\t" . "\t" . "\t" . '<field' . "\n" .
				"\t" . "\t" . "\t" . "\t" . 'name="ctm_' . str_replace('ctm_', '', $fieldname) . '"' . "\n" .
				"\t" . "\t" . "\t" . "\t" . 'type="' . $field->type . '"' . "\n" .
				"\t" . "\t" . "\t" . "\t" . 'label="' . $label . '"' . "\n" .
				"\t" . "\t" . "\t" . "\t" . 'description="' . $desc . '"' . "\n";

			for ($k = 0; $k < count($field->value); $k++) {
				if ($field->type == 'text') {
					$buffer .= "\t" . "\t" . "\t" . "\t" . 'default="' . (string)$field->value[$k]->value . '"' . "\n";
				}
				if (isset($field->value[$k]->required)) {
					if ($field->value[$k]->required = 1) {
						$buffer .= "\t" . "\t" . "\t" . "\t" . 'required="true"' . "\n";
						break;
					}
				}
			}
			if (in_array($field->type, array('list', 'radio', 'checkboxes'))) {
				$def = 'default="0"';
				if ($field->type != 'radio') {
					$buffer .= "\t" . "\t" . "\t" . "\t" . 'multiple="' . $field->multiple . '"' . "\n" .
						"\t" . "\t" . "\t" . "\t" . '>' . "\n";
				} else {
					$buffer .= '>' . "\n";
				}

				//if ($field->type != 'radio') $buffer .= "\t"."\t"."\t"."\t"."\t".'<option value="0">Select '.$field->name.'</option>'."\n";
				for ($j = 0; $j < count($field->value); $j++) {
					$buffer .= "\t" . "\t" . "\t" . "\t" . "\t" . '<option value="' . $field->value[$j]->value . '"><![CDATA[' . $field->value[$j]->name . ']]></option>' . "\n";
				}
				$buffer .= "\t" . "\t" . "\t" . '</field>' . "\n" . "\n";
			} else {
				$buffer .= '/>' . "\n";
			}
		}
		$buffer .= "\t" . "\t" . '</fieldset>' . "\n" .
			"\t" . '</fields>' . "\n" .
			'</form>';

		if (JFile::write(JPATH_ROOT . '/plugins/system/jacontenttype/models/types/' . $exfieldgroupname, $buffer)) {
			$migrator->addAssociation($groupid, 'extraGroup', $cttname);
		}

		if (count($languageTexts)) {
			$langFile = JPATH_ROOT . '/administrator/language/en-GB/en-GB.plg_system_jacontenttype_ex.ini';
			$txt = implode("\n", $languageTexts);
			if (JFile::exists($langFile)) {
				$txt = file_get_contents($langFile) . "\n" . $txt;
			}
			JFile::write($langFile, $txt);
		}
	}
    
    function createContentExtraField($groupid, $syncParams)
	{
		$user = JFactory::getUser();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields_groups'))->where('id=' . $db->quote($groupid));
		$db->setQuery($query);
		$group = $db->loadObject();
		$countField = 0;
		if (!$group) return;

		$data = array(
			'id' 			=> 0,
			'context' 		=> 'com_content.article',
			'title' 		=> $group->name,
			'state' 		=> 1,
			'created' 		=> $user->id,
			'created_by' 	=> $user->id,
			'modified' 		=> $user->id,
			'language' 		=> "*",
			'note' 			=> "",
			'description' 	=> "",
			'access' 		=> 1,
			'rules' 		=> array(),
			'params' 		=> array("display_readonly" => 1),
			'metakey' 		=> "",
			'tags' 			=> ""
		);
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/tables');
		$config = array();
		$GroupTable = JTable::getInstance('Group', 'FieldsTable', $config);

		// Bind the rules.
		if (isset($data['rules'])) {
			$rules = new JAccessRules($data['rules']);
			$GroupTable->setRules($rules);
		}

		if (!$GroupTable->bind($data)) {
			echo ($GroupTable->getError());
			return false;
		}

		if (!$GroupTable->check()) {
			echo ($GroupTable->getError());
			return false;
		}

		if (!$GroupTable->store()) {
			echo ($GroupTable->getError());
			return false;
		}
		$extra_groupid = $GroupTable->id;
        
        $migrator = new JADataMigrator();
		$migrator->addAssociation($groupid, 'ExtraGroup', $extra_groupid);

		// select extra fields.
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields', 'a'));
		$query->where('a.group =' . $db->quote($groupid));
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		/* $contentFields_ = new stdClass();
		$k2ExtraFields_ = new stdClass(); */
		foreach ($fields as $field) {
			$field->group = $extra_groupid;
			// the last extra field name will be duplicated in other field group if COM_FIELDS_ERROR_UNIQUE_NAME appear
			// print_r($field->name);
			if (isset($syncParams->extraField->{$groupid}->{$field->id}) && $syncParams->extraField->{$groupid}->{$field->id} == 'ignore')
				continue;

			if ($field->type == 'header') {
				continue;
			}

			if (array_key_exists($field->id, (array) $syncParams->extraField->{$groupid})) {
				if ($syncParams->extraField->{$groupid}->{$field->id} != 'auto') {
					$fieldname = trim($syncParams->extraField->{$groupid}->{$field->id});
				} else {
					$fieldname = trim(@$syncParams->exfipName->{$groupid}->{$field->id});
				}
			}

			if (empty($fieldname)) {
				$fieldname = $field->name;
			}

            $field->multiple = 0;
            
			switch ($field->type) {
				case 'textfield':
				case 'labels':
					$field->type = 'text';
					break;
				case 'select':
					$field->type = 'list';
					$field->multiple = 0;
					break;
				case 'multipleSelect':
					$field->type = 'list';
					$field->multiple = 1;
					break;
				case 'radio':
					break;
				case 'image':
					$field->type = 'media';
					break;
				case 'link':
					$field->type = 'url';
					break;
				case 'date':
					$field->type = 'calendar';
					break;
				case 'csv':
					$field->type = 'file';
					break;
			}

            $extraFieldValue = $this->convertk2ExtrFieldValue($field->value, $field->multiple);

			$data = array(
				"id" => 0,
				"context" => "com_content.article",
				"group_id" => $field->group,
				"assigned_cat_ids" => array(),
				"title" => $field->name,
				"name" => "",
				"type" => $field->type,
				"required" => 0,
				"default_value" => "",
				"state" => $field->published,
				"created_user_id" => $user->id,
				"created_time" => "",
				"modified_time" => "",
				"language" => "*",
				"note" => "",
				"label" => $field->name,
				"description" => "",
				"access" => 1,
				"rules" => array('core.delete' => array(), 'core.edit' => array(), 'core.edit.state' => array()),
				"params" => array(
					"class" => "",
					"label_class" => "",
					"show_on" => "",
					"render_class" => "",
					"showlabel" => "1",
					"label_render_class" => "",
					"display" => "2",
					"layout" => "",
					"display_readonly" => "2"
				),
				"fieldparams" => $extraFieldValue,
				"tags" => ""
			);
			$ExtrafieldTable = JTable::getInstance('Field', 'FieldsTable');

			if (!$ExtrafieldTable->bind($data)) {
				echo ($ExtrafieldTable->getError());
				return false;
			}

			if (!$ExtrafieldTable->check()) {
				echo ($ExtrafieldTable->getError());
				return false;
			}

			if (!$ExtrafieldTable->store()) {
				echo ($ExtrafieldTable->getError());
				return false;
			}

			/* $k2ExtraFieldId = (string) $field->id;
			$field_name = strtolower(str_replace(' ', '_', $field->name));
			$k2ExtraFields_->$k2ExtraFieldId = strtolower(str_replace(' ', '_', $field->name));
			$contentFields_->$field_name = $ExtrafieldTable->get('id'); */

			$migrator->addAssociation($field->id, 'ExtraField', $ExtrafieldTable->id);

			$countField++;
		}

		/* $this->k2ExtraFields[] = $k2ExtraFields_;
		$this->contentFields[] = $contentFields_;
		$extra_field_content = JPATH_ROOT . '/plugins/system/jak2tocomcontentmigration/cache/content-to-exfields.json';
		if (!is_file($extra_field_content) && $this->contentFields && $this->k2ExtraFields) {
			$this->write_to_file([$this->k2ExtraFields, $this->contentFields], $extra_field_content);
		} */

		if ($countField) {
			JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXTRA_FIELD_DONE', $group->name, $countField));
		}
	}

    /**
     * 
     * @param string $data
     * 
     * @return array
     */
    function convertk2ExtrFieldValue($data, $multiple)
	{
		$result = array();
		$result['multiple'] = $multiple;
		$result['options'] = array();
		$dataArr = json_decode($data, true);

		if (!empty($dataArr)) {
			$count = 0;
			foreach ($dataArr as $item) {
				unset($item['target']);
				$result['options']['options' . $count] = $item;
				$count++;
			}
		}
		return $result;
	}

    /**
	 * update k2 extra field names to avoid missing extrafield and 
	 * duplicate entry when assigning field to category
	 * 
	 * @return void
	 */
	function checkDuplicateK2ExtraFields(){
		$db = JFactory::getDbo();
		$query = "SELECT `id`,`name`, count(id) AS num 
					FROM `#__k2_extra_fields`
					GROUP BY `name` HAVING `num` > 1";
		$db->setQuery($query);
		$result = $db->loadAssocList();
		
		if (empty($result)) return;
		
		$duplicateNames = array_column($result, 'name');
		$str_duplicate_names = "'" . implode("', '", $duplicateNames) . "'";
		$first_duplicate_id = array_column($result, 'id');

		$query = $db->getQuery(true);
		$query->select('`id`, `name`, CONCAT(`name`, ".", `id`) AS `new_name`')
			->from('`#__k2_extra_fields`')
			->where('`name` IN('.$str_duplicate_names.')')
			->where('`id` NOT IN('.implode(',', $first_duplicate_id) .')')
			->order('`name` ASC');
		$db->setQuery($query);
		$duplicate_k2_extra_fields = $db->loadAssocList();
		
		if (empty($duplicate_k2_extra_fields)) return;

		foreach($duplicate_k2_extra_fields as $v){
			$query->clear();
			$query->update($db->quoteName('#__k2_extra_fields'))
				->set($db->quoteName('name') .'='. $db->quote($v['new_name']))
				->where($db->quoteName('id') .'='. $db->quote($v['id']));
			$db->setQuery($query);
			if ($db->execute()){
				$id = $v['id'];
				echo "updated: $id\n";
			}
		}
	}
}

?>