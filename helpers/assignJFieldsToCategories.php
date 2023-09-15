<?php 

class assignJoomlaFieldsToCategories{

    public function main(){
        $this->assignJFieldsNoLinkToCategory();
        $this->assignJFieldToCategories();
    }

    /**
	 * assigning some joomla fields that belonged to 
	 * groups did not associated with any categories
	 * 
	 * @return void
	 */
	function assignJFieldsNoLinkToCategory(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// check if some extra field group have no link to any catefories
		$subQuery = $db->getQuery(true)
			->select('DISTINCT `extraFieldsGroup`')
			->from('#__k2_categories');
			// ->where($db->quoteName('published') .'=1');
		$query = $db->getQuery(true)
			->select('`id`')
			->from('`#__k2_extra_fields_groups`')
			->where('`id` NOT IN (' . $subQuery . ')');
		$db->setQuery($query);
		$k2_groups_no_assoc = $db->loadColumn();
		
		if (empty($k2_groups_no_assoc)) return;

		// get all k2 extra fields belong to the field group above
		$query->clear();
		$query->select("k2f.`id` AS `k2_field_id`, ass.`key` AS `joomla_field_id`")
			->from($db->quoteName('#__k2_extra_fields', 'k2f'))
			->join('INNER', $db->quoteName('#__associations', 'ass') . ' ON k2f.`id` = ass.`id`')
			->where($db->quoteName('k2f.group') .' IN('. implode(',', $k2_groups_no_assoc).')')
			->where($db->quoteName('ass.context') . ' LIKE "%migration.ExtraField%"');
		$db->setQuery($query);
		$k2_ex_fields = $db->loadAssocList();

        $migrator = new JADataMigrator();
		// check joomla fields associate with joomla category id or not
		foreach($k2_ex_fields as $k => $fieldId){
			if ($migrator->checkAssociation($fieldId['k2_field_id'], 'field_category')){
				unset($k2_ex_fields[$k]);
			}
		}
		
		if (empty($k2_ex_fields)) return;

		// assign joomla fields to uncategorises category `#__fields_categories`
		$query->clear();
		$query->select('`id`')
			->from('`#__categories`')
			->where("`path` = 'uncategorised'")
			->where("`extension` = 'com_content'");
		$db->setQuery($query);
		$jUnCat = $db->loadResult();
		
		if (empty($jUnCat)) return;
		
		$query->insert('`#__fields_categories`')
			->columns($db->quoteName([
				'field_id', 'category_id'
			]));
		foreach($k2_ex_fields as $fieldId){
			$query->values($db->quote($fieldId['joomla_field_id']) .', '. $db->quote($jUnCat));
		}
		$db->setQuery($query);
		if ($db->execute()){
            JADataMigrator::printr(sprintf("%d joomla fields assigned to an Uncategorised", count($k2_ex_fields)));
			foreach($k2_ex_fields as $fieldId){
				$migrator->addAssociation($fieldId['k2_field_id'], 'field_category', $fieldId['joomla_field_id']);
			}
		}
	}

    /**
	 * 
	 * 
	 * @return void
	 */
	function assignJFieldToCategories(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$subQuery = $db->getQuery(true)
			->select('DISTINCT `extraFieldsGroup`')
			->from('`#__k2_categories`');
		$query = $db->getQuery(true)
			->select('`id`')
			->from('`#__k2_extra_fields_groups`')
			->where('`id` IN (' . $subQuery . ')');
		$db->setQuery($query);
		$k2Groups = $db->loadColumn();
		
		if (empty($k2Groups)) return;
		
		$query->clear();
		$query->select('k2ef.`id` as `k2_group_id`, k2cat.`id` as `k2_cat_id`, ass.`key` as `joomla_cat_id`')
			->from($db->quoteName('#__k2_extra_fields_groups', 'k2ef'))
			->join('INNER', $db->quoteName('#__k2_categories', 'k2cat') . ' ON k2cat.`extraFieldsGroup` = k2ef.`id`')
			->join('INNER', $db->quoteName('#__associations', 'ass') . ' ON ass.`id` = k2cat.`id`')
			->where($db->quoteName('k2cat.published') .'=1')
			->where($db->quoteName('k2ef.id') .' IN(' . implode(',', $k2Groups) .')')
			->where($db->quoteName('ass.context') .'='. $db->quote('ja_migration.category'))
			->order('k2ef.id ASC');
		$db->setQuery($query);
		$joomla_catids_assoc_k2_field_groups = $db->loadAssocList('joomla_cat_id', 'k2_group_id');
		
		$query->clear();
		$query->select('k2fg.`id` as `k2_group_id`, k2f.`id` as `k2_field_id`, ass.`key` as `joomla_field_id`')
			->from($db->quoteName('#__k2_extra_fields', 'k2f'))
			->join('INNER', $db->quoteName('#__k2_extra_fields_groups', 'k2fg') . " ON k2fg.`id` = k2f.`group`")
			->join('INNER', $db->quoteName('#__associations', 'ass') . " ON ass.`id` = k2f.`id`")
			->where($db->quoteName('k2fg.id') .' IN('. implode(',', $k2Groups) .')')
			->where($db->quoteName('ass.context') .'='. $db->quote('ja_migration.ExtraField'))
			->order('k2fg.id ASC');
		$db->setQuery($query);
		$k2_groups_fields = $db->loadAssocList();

        $migrator = new JADataMigrator();
		// check joomla fields associate with joomla category id or not
		foreach($k2_groups_fields as $k => $fieldId){
			if ($migrator->checkAssociation($fieldId['k2_field_id'], 'field_category')){
				unset($k2_groups_fields[$k]);
			}
		}

		if (empty($k2_groups_fields)) return;

		// prepare data: joomla fields - category ids
		$k2_groups_assoc_joomla_fields = [];
		foreach ($k2_groups_fields as $item) {
			$k2GroupId = $item['k2_group_id'];
			$jFieldId = $item['joomla_field_id'];
			if (!isset($k2_groups_assoc_joomla_fields[$k2GroupId])) {
				$k2_groups_assoc_joomla_fields[$k2GroupId] = [];
			}
			$k2_groups_assoc_joomla_fields[$k2GroupId][] = $jFieldId;
		}

		$insertValues = [];
		foreach($joomla_catids_assoc_k2_field_groups as $joomlaCatId => $k2GroupId){
			$jFieldIds = $k2_groups_assoc_joomla_fields[$k2GroupId];
			if (empty($jFieldIds)) continue;
			foreach($jFieldIds as $k => $jfield){
				$insertValues[] = $db->quote($jFieldIds[$k]) .','. $db->quote($joomlaCatId);
			}
		}

		$query->clear();
		$query->insert('`#__fields_categories`')
			->columns($db->quoteName(['field_id', 'category_id']))
			->values($insertValues);
		$db->setQuery($query);
		if ($db->execute()){
            JADataMigrator::printr(sprintf("%d joomla fields assigned to categories.", count($insertValues)));
			foreach($k2_groups_fields as $field){
				$migrator->addAssociation($field['k2_field_id'], 'field_category', $field['joomla_field_id']);
			}
		}
	}
}

?>