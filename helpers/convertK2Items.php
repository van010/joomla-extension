<?php

class convertK2Items{

	public function mainBatch(){
		$migrator = new JADataMigrator();
		$catid = $migrator->getMigratedItems('category');
		if (!count($catid)) return array();

		$catid = array_unique($catid);

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('i.*, GROUP_CONCAT(tx.tagID) AS `tags_id`')
			->from($db->quoteName('#__k2_items', 'i'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'tx') . ' ON (tx.itemID = i.id)')
			->join('LEFT', $db->quoteName('#__associations', 'assoc') . " ON (assoc.id = i.id AND assoc.context = 'ja_migration.item')")
			->group('i.id')
			->where('assoc.id IS NULL')
			->where('i.catid IN (' . implode(',', $catid) . ')');

		$start = 0;
		$batchSize = 550;
		while(true){
			$query->clear('limit')->clear('offset');
			$query->setLimit($batchSize, $start);
			$db->setQuery($query);
			$k2Items = $db->loadObjectList();
			
			if (empty($k2Items)){
				break;
			}

			$numItems = (int) $this->_convertK2Items($k2Items);
			if ($numItems){
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_ITEMS_DONE', $numItems));
			}
			sleep(1);
		}
	}

    /**
     * 
     * @return boolean|int The number of items or False if there no items
     */
    private function _convertK2Items($data)
	{
        $migrator = new JADataMigrator();
		$k2items = $data;
		$numItems = count($k2items);
		if (!$numItems) {
			return 0;
		}
		$qi_q = 'INSERT INTO #__content_meta (id, content_id, meta_key, meta_value , encoded) VALUES ';
		$qi_v = array();
		$count = 0;

		foreach ($k2items as $k => $v) {
			$tags = array();
			$assoc = $migrator->checkAssociation($v->id, 'item');
			if (!empty($assoc)) continue;
			// Copy Image from K2.
			if (JFile::exists(JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $v->id) . '.jpg')) {
				JFolder::create(JPATH_ROOT . '/images/joomlart/article/'); // create recusive folder.
				copy('../media/k2/items/src/' . md5('Image' . $v->id) . '.jpg', '../images/joomlart/article/' . md5('Image' . $v->id) . '.jpg');
				$images = array(
					'image_intro' => 'images/joomlart/article/' . md5('Image' . $v->id) . '.jpg',
					'float_intro' => '',
					'image_intro_alt' => $v->image_caption,
					'image_intro_caption' => $v->image_credits,
					'image_fulltext' => 'images/joomlart/article/' . md5('Image' . $v->id) . '.jpg',
					'float_fulltext' => '',
					'image_fulltext_alt' => $v->image_caption,
					'image_fulltext_caption' => $v->image_credits
				);
				$images = json_encode($images);
			} else{
				$images = '';
			}
			// Convert Tag id from k2 to new id.
			if ($v->tags_id != NULL) {
				$tags_id = explode(',', $v->tags_id);
				foreach ($tags_id as $tg) {
					$tag = $migrator->checkAssociation($tg, 'tag');
					if ($tag) {
						$tags[] = $tag->key;
					}
				}
			}

			// Convert Extra field to attr.
			$attrib = $this->getAttrib($v);

			//metadata
			$metadata = $v->metadata;
			$meta = explode('author=', $metadata);

			$catid = 1;
			$category = $migrator->checkAssociation($v->catid, 'category');
			if ($category && $category->key != 'auto' && $category->key != 'ignore') {
				$catid = $category->key;
			}
			$alias = $migrator->generateAlias($v->alias, 'content');

			$data = array(
				'id' 			=> 0,
				'title' 		=> $v->title, // remove unwanted character.
				'alias' 		=> $alias,
				'articletext' 	=> $v->introtext . (trim($v->introtext) != '' ? '<hr id="system-readmore">' : '') . $v->fulltext,
				'state' 		=> ($v->trash == 1 ? -2 : $v->published),
				'catid' 		=> $catid,
				'tags' 			=> $tags,
				'created' 		=> $v->created,
				'created_by' 	=> $v->created_by,
				'created_by_alias' => $v->created_by_alias,
				'modified' 		=> $v->modified,
				'modified_by' 	=> $v->modified_by,
				'publish_up' 	=> $v->publish_up,
				'publish_down' 	=> $v->publish_down,
				'version' 		=> 0,
				'metakey' 		=> $v->metakey,
				'metadesc' 		=> $v->metadesc,
				'access' 		=> $v->access,
				'hits' 			=> $v->hits,
				'language' 		=> $v->language,
				'featured' 		=> $v->featured,
				'rules' 		=> array('core.delete' => array(), 'core.edit' => array(), 'core.edit.state' => array()),
				'attribs' 		=> $attrib,
				'xreference' 	=> NULL,
				'images' 		=> $images,
				'urls' 			=> array(),
				'metadata' 		=> array(
					'robots'	=> (isset($meta[0]) ? substr($meta[0], 7) : ''),
					'author'	=> (isset($meta[1]) ? ltrim($meta[1]) : ''),
					'rights'	=> '',
					'xreference' => '',
				)
			);
			$table = JTable::getInstance('Content', 'JTable');

			if (!$table->bind($data)) {
				return false;
			}

			if (!$table->check()) {
				return false;
			}

			if (!$table->store()) {
				return false;
			}
			$contentId = $table->get('id');
			$extra__ = '';
			$this->importK2ExtraFieldItemToContent($v, $contentId, $extra__);
			// insert to merged table
			if ($table->id != 0) {
				$migrator->addAssociation($v->id, 'item', $table->id);
				if (count($tags) > 0) {
					$this->updateTagItem($table, $tags);
				}

				if ($attrib != NULL) {
					$cmeta = json_decode($attrib);
					foreach ($cmeta as $cmk => $cmv) {
						$val = ((is_object($cmv) || is_array($cmv)) ? json_encode($cmv) : $cmv);
						$encode = ((is_object($cmv) || is_array($cmv)) ? 1 : 0);
						if (preg_match("/ctm_/i", $cmk))
							$qi_v[] = '(NULL, ' . $table->id . ', "' . str_replace('ctm_', '', $cmk) . '", "' . addslashes($val) . '", ' . $encode . ')';
					}
				}
				$table = NULL; // free table;
				$count++;
			}
		}

		// Insert to content_meta ro sort in system content_type
		if (count($qi_v) > 0) {
			$qi_v = array_unique($qi_v);
			$qi_q .= implode(', ', $qi_v);
			if ($migrator->checkPluginCTT() == true) {
                $db = JFactory::getDbo();
				try {
					$db->setQuery($qi_q);
					$db->execute();
				} catch (RuntimeException $e) {
					JFactory::getApplication()->enqueueMessage($e, 'error');
				}
			}
		}

		return $numItems;
	}

    /**
     * add joomla fields and value fields into `#__fields_values`
     * 
     * @param object $item
     * @param int $contentId
     * 
     * @return void
     */
    protected function importK2ExtraFieldItemToContent($item, $contentId)
	{
		$exValue = $item->extra_fields;
		
		if (empty($exValue)) return;
		
		$values = array();
		$exValue = json_decode($exValue);
		$k2_field_assoc_jfield = $this->mapK2FieldToJoomlaContent();
        $db = JFactory::getDbo();

		foreach ($exValue as $v) {
			if (!empty($k2_field_assoc_jfield[$v->id])) {
				$fieldId = $k2_field_assoc_jfield[$v->id];
				if (is_array($v->value)) {
					$v_ = $v->value;
					$value = $v_[1] ?? $v_[0];
				} else {
					$value = !empty($v->value) ? $v->value : '';
				}
				$values[] = "$fieldId, $contentId, " . $db->quote($value);
			}
		}

		if (empty($values)) return;

        $migrator = new JADataMigrator();
		$query = $db->getQuery(true);
		$query->insert('`#__fields_values`')
			->columns(array(
				$db->quoteName('field_id'),
				$db->quoteName('item_id'),
				$db->quoteName('value')
			));
		foreach ($values as $value) {
			$query->values($value);
		}
		$db->setQuery($query);
		if ($db->execute()) {
			foreach ($values as $value) {
				$migrator->addAssociation($item->id, 'field_values', trim(explode(',', $value)[1]));
			}
		}
	}

    /**
	 * get k2 extra fields associated with joomla fields
	 * 
	 * @return array
	 */
	protected function mapK2FieldToJoomlaContent()
    {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`id`, `key`')
			->from('`#__associations`')
			->where('`context` LIKE '. $db->quote('%migration.ExtraField%'));
		$db->setQuery($query);
		return $db->loadAssocList('id', 'key');
	}

    protected function getAttrib($item)
	{
		$buff = array();
		$exvalue = json_decode($item->extra_fields);

		if (JFolder::exists(JPATH_ROOT . '/media/k2/galleries/' . $item->id . '/')) {
			$files = JFolder::files(JPATH_ROOT . '/media/k2/galleries/' . $item->id . '/', '\.jpg$', false, true);
			$buff['ctm_jagallery'] = new stdClass();
			$buff['ctm_jagallery']->src = new stdClass();
			$buff['ctm_jagallery']->class = new stdClass();
			$buff['ctm_jagallery']->caption = new stdClass();
			$buff['ctm_jagallery']->link = new stdClass();
			foreach ($files as $k => $f) {
				$arrname = explode('/', $f);
				$fn = end($arrname);
				JFolder::create(JPATH_ROOT . '/images/joomlart/article/' . $item->id . '/');
				copy('../media/k2/galleries/' . $item->id . '/' . $fn, '../images/joomlart/article/' . $item->id . '/' . $fn);
				$buff['ctm_jagallery']->src->$k = 'images/joomlart/article/' . $item->id . '/' . $fn;
				$buff['ctm_jagallery']->class->$k = '';
				$buff['ctm_jagallery']->caption->$k = '';
				$buff['ctm_jagallery']->link->$k = '';
			}
		}

		if (!empty($item->video) && trim($item->video) != '') {
			$buff['ctm_jaembed_text'] = $item->video;
		}

		if (is_array($exvalue) && count($exvalue) > 0) {
			if (!empty($exvalue[0]->id)){
                $migrator = new JADataMigrator();
				$group = $this->getExtraFieldGroup($exvalue[0]->id);

				if ($group) {
					$idGroupCurrent = $group->id;
					$ctName = '';
					$extraGroup = $migrator->checkAssociation($idGroupCurrent, 'extraGroup');
					if ($extraGroup) {
						$ctName = $extraGroup->key;
					}

					if ($ctName) {
						$buff['ctm_content_type'] = $ctName;

						foreach ($exvalue as $k => $v) {
							$extraField = $migrator->checkAssociation($v->id, 'extraField');
							if ($extraField) {
								$fieldname = $extraField->key;
								$buff['ctm_' . str_replace('ctm_', '', $fieldname)] = $v->value;
							}
						}
					}
				}
			}
		}

		// item view option attribute
		$params = json_decode($item->params);
		$buff['show_title'] = empty($params->itemTitle) ? '' : $params->itemTitle;
		$buff['link_titles'] = '';
		$buff['show_tags'] = empty($params->itemTags) ? '' : '';
		$buff['show_intro'] = empty($params->itemIntroText) ? '' : '';
		$buff['info_block_position'] = '';
		$buff['show_category'] = empty($params->itemCategory) ? '' : '';
		$buff['link_category'] = '';
		$buff['show_parent_category'] = '';
		$buff['link_parent_category'] = '';
		$buff['show_author'] = empty($params->itemAuthor) ? '' : '';
		$buff['link_author'] = empty($params->itemAuthorURL) ? '' : '';
		$buff['show_create_date'] = '';
		$buff['show_modify_date'] = '';
		$buff['show_publish_date'] = '';
		$buff['show_item_navigation'] = '';
		$buff['show_icons'] = '';
		$buff['show_print_icon'] = '';
		$buff['show_email_icon'] = '';
		$buff['show_vote'] = empty($params->itemRating) ? '' : '';
		$buff['show_hits'] = empty($params->itemHits) ? '' : '';
		$buff['show_noauth'] = '';
		$buff['urls_position'] = '';
		$buff['alternative_readmore'] = '';
		$buff['show_publishing_options'] = '';
		$buff['show_article_options'] = '';
		$buff['show_urls_images_backend'] = '';
		$buff['show_urls_images_frontend'] = '';

		if (count($buff) > 0){
			return json_encode($buff);
        }
        return NULL;
	}

    protected function updateTagItem($table, $tags)
    {
		// update core content id
        $db = JFactory::getDbo();
		$cciQuery = $db->getQuery(true);
		$cciQuery->insert('`#__ucm_content`');
		$cciQuery->columns(array(
			$db->quoteName('core_content_id'),
			$db->quoteName('core_type_alias'),
			$db->quoteName('core_title'),
			$db->quoteName('core_alias'),
			$db->quoteName('core_body'),
			$db->quoteName('core_state'),
			$db->quoteName('core_checked_out_time'),
			$db->quoteName('core_checked_out_user_id'),
			$db->quoteName('core_access'),
			$db->quoteName('core_params'),
			$db->quoteName('core_featured'),
			$db->quoteName('core_metadata'),
			$db->quoteName('core_created_user_id'),
			$db->quoteName('core_created_by_alias'),
			$db->quoteName('core_created_time'),
			$db->quoteName('core_modified_user_id'),
			$db->quoteName('core_modified_time'),
			$db->quoteName('core_language'),
			$db->quoteName('core_publish_up'),
			$db->quoteName('core_publish_down'),
			$db->quoteName('core_content_item_id'),
			$db->quoteName('asset_id'),
			$db->quoteName('core_images'),
			$db->quoteName('core_urls'),
			$db->quoteName('core_hits'),
			$db->quoteName('core_version'),
			$db->quoteName('core_ordering'),
			$db->quoteName('core_metakey'),
			$db->quoteName('core_metadesc'),
			$db->quoteName('core_catid'),
			$db->quoteName('core_xreference'),
			$db->quoteName('core_type_id'),
		));

		$cciQuery->values(
			'NULL , ' .
				$db->quote('com_content.article') . ', ' .
				$db->quote($table->title) . ',' .
				$db->quote($table->alias) . ',' .
				'"",' .
				$table->state . ',' .
				'"",' .
				'0,' .
				'1,' .
				$db->quote($table->attribs) . ',' .
				'1,' .
				$db->quote($table->metadata) . ',' .
				'"' . $table->created_by . '",' .
				'"",' .
				'"' . date('Y-m-d H:i:s') . '",' .
				'"' . $table->created_by . '",' .
				'"' . date('Y-m-d H:i:s') . '",' .
				'"' . $table->language . '",' .
				'"0000-00-00 00:00:00",' .
				'"0000-00-00 00:00:00",' .
				$table->id . ',' .
				$table->asset_id . ',' .
				$db->quote($table->images) . ',' .
				'"",' .
				$table->hits . ',' .
				$table->version . ',' .
				'0,' .
				$db->quote($table->metakey) . ',' .
				$db->quote($table->metadesc) . ',' .
				$table->catid . ',' .
				$db->quote($table->xreference) . ',' .
				'1'
		);
		try {
			$db->setQuery($cciQuery);
			$db->execute();
		} catch (RuntimeException $e) {
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}
		$ucmid = $db->insertid();

		foreach ($tags as $tag) {
			if (!empty($tag)) {
				$query = $db->getQuery(true);
				$query->select('core_content_id')->from($db->quoteName('#__contentitem_tag_map'))
					->where('type_id=1 AND tag_id=' . $tag . ' AND content_item_id=' . $table->id);
				$db->setQuery($query);
				$check_tagmap = $db->loadResult();
				$db->freeResult();
				if (!$check_tagmap) {
					$tagmap = $db->getQuery(true);
					$tagmap->insert('#__contentitem_tag_map');
					$tagmap->columns(array(
						$db->quoteName('type_alias'),
						$db->quoteName('core_content_id'),
						$db->quoteName('content_item_id'),
						$db->quoteName('tag_id'),
						$db->quoteName('tag_date'),
						$db->quoteName('type_id'),
					));
					$tagmap->values(
						$db->quote('com_content.article') . ',' .
							$ucmid . ',' .
							$table->id . ',' .
							$tag . ',' .
							'"' . date('Y-m-d H:i:s') . '",' .
							'1'
					);

					try {
						$db->setQuery($tagmap);
						$db->execute();
					} catch (RuntimeException $e) {
						JFactory::getApplication()->enqueueMessage($e, 'error');
					}
				}
			}
		}


		$ucm_base = $db->getQuery(true);
		$ucm_base->insert('#__ucm_base');
		$ucm_base->columns(array(
			$db->quoteName('ucm_id'),
			$db->quoteName('ucm_item_id'),
			$db->quoteName('ucm_type_id'),
			$db->quoteName('ucm_language_id')
		));
		$ucm_base->values(
			$ucmid . ',' .
				$table->id . ',' .
				'1,' .
				'0'
		);

		try {
			$db->setQuery($ucm_base);
			$db->execute();
		} catch (RuntimeException $e) {
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}

		$assets = $db->getQuery(true);
		$assets->insert('#__assets');
		$assets->columns(array(
			$db->quoteName('id'),
			$db->quoteName('parent_id'),
			$db->quoteName('lft'),
			$db->quoteName('rgt'),
			$db->quoteName('level'),
			$db->quoteName('name'),
			$db->quoteName('title'),
			$db->quoteName('rules')
		));
		$assets->values(
			'NULL,' .
				'1,' .
				'0,' .
				'0,' .
				'1,' .
				'"#__ucm_content.' . $ucmid . '",' .
				'"#__ucm_content.' . $ucmid . '",' .
				'"[]"'
		);

		try {
			$db->setQuery($assets);
			$db->execute();
			$db->freeResult();
		} catch (RuntimeException $e) {
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}
	}

    /**
	 * get extra field group that extra field belong to (from extra field id)
     * 
	 * @param $exid
     * 
	 * @return mixed
	 */
	protected function getExtraFieldGroup($exid)
	{
        $db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('efg.id, efg.name')->from($db->quoteName('#__k2_extra_fields', 'ef'))
			->join('INNER', $db->quoteName('#__k2_extra_fields_groups', 'efg') . ' ON (ef.group = efg.id)')
			->where('ef.id=' . $exid);
		$db->setQuery($query);
		$group = $db->loadObject();
		return $group;
	}

    /**
     * 
     * 
     * @param int $batch
     * 
     * @return Object
     */
    protected function fetchItems($batch = 50)
    {
        $migrator = new JADataMigrator();
		$catid = $migrator->getMigratedItems('category');
		if (!count($catid)) return array();

		$catid = array_unique($catid);

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('i.*, GROUP_CONCAT(tx.tagID) AS `tags_id`')
			->from($db->quoteName('#__k2_items', 'i'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'tx') . ' ON (tx.itemID = i.id)')
			->join('LEFT', $db->quoteName('#__associations', 'assoc') . " ON (assoc.id = i.id AND assoc.context = 'ja_migration.item')")
			->group('i.id')
			->where('assoc.id IS NULL')
			->where('i.catid IN (' . implode(',', $catid) . ')');

		$db->setQuery($query, 0, $batch);
		$k2items = $db->loadObjectList();
		return $k2items;
	}
}

?>