<?php

/**
 * ------------------------------------------------------------------------
 * JA K2 To Com Content Migration Plugin for J25 & J34
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

require_once __DIR__ . '/convertK2Items.php';
require_once __DIR__ . '/convertK2Extrafields.php';
require_once __DIR__ . '/assignJFieldsToCategories.php';
require_once __DIR__ . '/convertAttach.php';

ini_set('memory_limit', '2024M');

class JADataMigrator
{
	private $db;
	private $catID;
	private $merged;
	private $path;
	private $postype	= array('ignore', 'auto');
	private $assoc = null;
	public $contentFields;
	public $k2ExtraFields;
	public $extra_type;


	function __construct()
	{
		$this->db = JFactory::getDBO();
		$this->path = JPATH_ROOT . '/plugins/system/jacontenttype/models/types/';
		$convertAttch = new convertK2Attch();
		$convertAttch->createJaAttchTbl();
	}

	public function recontent()
	{
		// start the flush
		if (ob_get_level() == 0) ob_start();
		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_RECONTENT_START'));
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('context') . ', ' . $this->db->quoteName('key', 'id'))
			->from($this->db->quoteName('#__associations'))
			->where($this->db->quoteName('context') . ' LIKE ' . $this->db->quote('ja_migration%'));
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		$table = array();
		$extrafields = 0;
		foreach ($items as $item) {

			if ($item->context == 'ja_migration.extraGroup') {
				@unlink($this->path . $item->id . '.xml');
				@JFile::delete($this->path . $item->id . '.xml');
				$extrafields++;
			}
			if ($item->context == 'ja_migration.ExtraGroup') {
				$table['#__fields_groups'][] = $item->id;
			}
			if ($item->context == 'ja_migration.ExtraField') {
				$table['#__fields'][] = $item->id;
			}
			if ($item->context == 'ja_migration.category')
				$table['#__categories'][] = $item->id;
			if ($item->context == 'ja_migration.tag')
				$table['#__tags'][] = $item->id;
			if ($item->context == 'ja_migration.item')
				$table['#__content'][] = $item->id;
			if ($item->context == 'ja_migration.field_category') {
				$table['#__fields_categories'][] = $item->id;
			}
			if ($item->context == 'ja_migration.field_values') {
				$table['#__fields_values'][] = $item->id;
			}
		}

		foreach ($table as $k => $v) {
			if ($k == '#__tags') {
				$query = $this->db->getQuery(true);
				$query->delete($this->db->quoteName($k));
				$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $v) . ')');
			} else {
				if ($k == '#__fields_categories' || $k == '#__fields_values') {
					$query1 = $this->db->getQuery(true);
					$id = $k == '#__fields_categories' ? 'field_id' : 'item_id';
					$query1->delete($this->db->quoteName($k))
						->where($this->db->quoteName($k) . ".$id IN (" . implode(',', array_filter($v)) . ')');
					$this->db->setQuery($query1);
					$this->db->execute();
				} else {
					$query = 'DELETE FROM ' . $this->db->quoteName($k) . ', ' . $this->db->quoteName('#__assets') . '
					USING ' . $this->db->quoteName('#__assets') . ' INNER JOIN ' . $this->db->quoteName($k) . '
					WHERE ' . $this->db->quoteName('#__assets') . '.`id` = ' . $this->db->quoteName($k) . '.`asset_id`
					AND ' . $this->db->quoteName($k) . '.`id` IN (' . implode(',', $v) . ')';
				}
			}
			try {
				$this->db->setQuery($query);
				$this->db->execute();
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_' . strtoupper(str_replace(array('#__', 'content'), array('', 'items'), $k)) . '_DONE', count($v)));
			} catch (RuntimeException $e) {
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}
		}

		$convertAttch = new convertK2Attch();
		$convertAttch->recontentAttach();

		if ($extrafields) JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $extrafields));
		$query = $this->db->getQuery(true);
		$query->delete($this->db->quoteName('#__associations'));
		$query->where($this->db->quoteName('context') . ' LIKE ' . $this->db->quote('ja_migration%') . '');

		try {
			$this->db->setQuery($query);
			$this->db->execute();
		} catch (RuntimeException $e) {
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}

		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_DONE'));
		JADataMigrator::printr(JADataMigrator::refresh());
		// End flush
		ob_end_flush();
	}

	public function recontent_k2_items(){
		if (ob_get_level() == 0) ob_start();
		JADataMigrator::printr('recontent K2 items only');
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('`#__associations`')
			->where('context = "ja_migration.item"');
		$db->setQuery($query);
		$items = $db->loadObjectList();
	}

	public function errorCheck()
	{
		$error = array();

		// no title check.
		$sql = 'SELECT id FROM #__k2_items WHERE title = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['item_empty'] = $items;
			$error['table_name'] = '#__k2_items';
		}

		$sql = 'SELECT id FROM #__k2_categories WHERE name = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['category_empty'] = $items;
			$error['table_name'] = '#__k2_categories';
		}

		$sql = 'SELECT id FROM #__k2_tags WHERE name = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['tag_empty'] = $items;
			$error['table_name'] = '#__k2_tags';
		}

		$sql = 'SELECT id FROM #__tags ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process = array();
		$process['tags'] = false;
		if (!empty($items)) {
			$process['tags'] = $items;
		}
		$sql = 'SELECT id FROM #__categories ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$firstCateId = $this->db->setQuery('SELECT id FROM #__categories ORDER BY id ASC LIMIT 1')->loadResult();
		// fix categories getNode(1,)
		if ((int) $firstCateId != 1) {
			$query1 = 'INSERT INTO #__categories (id,asset_id,parent_id,lft,rgt,level,path,extension,title,alias,note,description,published,checked_out,checked_out_time,access,params,metadesc,metakey,metadata,created_user_id,created_time,modified_user_id,modified_time,hits,language,version)' .
				'VALUES (1,"0","0","0",888,"0","","system","ROOT","root","","",1,"0","1000-10-10 00:00:00",1,"{}","","","",434,"2009-10-18 16:07:09","0","1000-10-10 00:00:00","0","*",1)';
			$this->db->setQuery($query1);
			$this->db->execute();
		}
		$process['categories'] = false;
		if (!empty($items)) {
			$process['categories'] = $items;
		}
		$sql = 'SELECT id FROM #__content ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['content'] = false;
		if (!empty($items)) {
			$process['content'] = $items;
		}

		sleep(5);

		$sql = 'SELECT id FROM #__tags ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['tags2'] = false;
		if (!empty($items)) {
			$process['tags2'] = $items;
		}
		$sql = 'SELECT id FROM #__categories ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['categories2'] = false;
		if (!empty($items)) {
			$process['categories2'] = $items;
		}
		$sql = 'SELECT id FROM #__content ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['content2'] = false;
		if (!empty($items)) {
			$process['content2'] = $items;
		}

		if (
			$process['tags'] != $process['tags2']
			|| $process['categories'] != $process['categories2']
			|| $process['content'] != $process['content2']
		) {
			$error['process'] = JText::_('JAK2_MIGRATE_PROCESS_IS_RUNNING');
		}

		return $error;
	}

	public function ajaxMigrate(){
		JADataMigrator::printr(JText::_('In developing...'));
		return;
	}

	public function migrate()
	{
		// start the flush
		if (ob_get_level() == 0) ob_start();
		$error = $this->errorCheck();
		if (!empty($error)) {
			foreach ($error as $k => $er) {
				if (!empty($er)) {
					echo $k !== 'table_name' ? JText::_('JAK2_MIGRATE_ERROR_' . strtoupper($k)):'';
					echo '<br/>';
					foreach ($er as $ek => $ev) {
						echo 'ID:';
						echo $ev->id;
						echo '<br/>';
						echo empty($error['table_name']) ?: JText::_('JAK2_MIGRATE_ERROR_TABLE_NAME') . ': ' . $error['table_name'];
					}
					echo '<br/>';
				}
			}
			// End flush
			ob_end_flush();
			return;
		}

		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_START'));
		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_STOP_WARNING'));
		// Convert Category
		$this->convertK2Categories();
		
		$convertK2Extrafields = new convertK2ExtraFieldValue();

		$convertK2Extrafields->checkDuplicateK2ExtraFields();

		// Convert Tags
		$this->convertK2Tags();

		if ($this->extra_type == 'extra') {
			// Convert Extrafield
			$convertK2Extrafields->convertExtraField2();
		} else {
			$system_ctt = false;
			if (file_exists(JPATH_ROOT . '/plugins/system/jacontenttype/jacontenttype.php')) {
				$plugins = JPluginHelper::getPlugin('system');
				foreach ($plugins as $pl) {
					if ($pl->name == 'jacontenttype') {
						$system_ctt = true;
					}
				}
				// make sure the system plugin content is install & enabled.
				if ($system_ctt == true) {
					// Convert Extrafield
					$convertK2Extrafields->convertExtraField();
				}
			}
		}

		// assign joomla fields to joomla categories
		$joomlaFieldsToCategories = new assignJoomlaFieldsToCategories();
		$joomlaFieldsToCategories->main();

		// Convert Items
		$convertK2Items = new convertK2Items();
		$convertK2Items->mainBatch();

		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_DONE'));
		JADataMigrator::printr(JADataMigrator::refresh());
		// End flush
		ob_end_flush();
	}

	/**
	 *
	 * Show element data on K2
	 * @param int $id
	 * @param array $list
	 * @return array list categories element
	 */
	function getTreeCategories()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*, name AS title, parent AS parent_id')->from($this->db->quoteName('#__k2_categories'));
		$db->setQuery($query);
		$mitems = $db->loadObjectList();

		$list = array();
		if (count($mitems)) {
			$children = array();
			foreach ($mitems as $v) {
				$v->title = $v->name;
				$v->parent_id = $v->parent;
				$pt = $v->parent_id;
				$child = isset($children[$pt]) ? $children[$pt] : array();
				array_push($child, $v);
				$children[$pt] = $child;
			}

			$list = @JHtml::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);
		}
		return $list;
	}

	function getTag($tag)
	{
		$query = $this->db->getQuery(true);
		$query->select('*')->from('#__tags')->where('LOWER(alias) = ' . $this->db->quote($this->cleanAlias($tag)));
		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	function cleanAlias($alias)
	{
		$alias = JApplicationHelper::stringURLSafe($alias);
		return strtolower($alias);
	}


	function generateAlias($alias, $table)
	{
		$alias = $this->cleanAlias($alias);
		$index = 0;

		$query = $this->db->getQuery(true);
		$query->select('id')->from('#__' . $table);
		do {
			$newalias = !$index ? $alias : $alias . '-' . $index;
			if ($table == 'tags') {
				$newalias = str_replace('-', '', $newalias);
			}
			$query->clear('where');
			$query->where('LOWER(alias) = ' . $this->db->quote($newalias));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();
			$index++;
		} while ($exists);

		return $newalias;
	}

	// check exists item using name
	function checkName($name, $table, $id)
	{
		$query = $this->db->getQuery(true);
		$query->select('id')->from($this->db->quoteName('#__' . $table))->where('title = "' . $name . '"');
		$this->db->setQuery($query);
		$check_name = $this->db->loadResult();
		$this->db->freeResult();

		if (!$check_name) return $name;
		else return $id . '-' . $name;
	}

	function fetchTags()
	{
		$migrated = $this->getMigratedItems('tag');
		// select all tag.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('t.*, GROUP_CONCAT(tx.itemID)')
			->from($db->quoteName('#__k2_tags', 't'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'tx') . ' ON (t.id = tx.tagID)')
			->group('t.id')
			->order('t.id');
		if (count($migrated)) {
			$query->where('t.id NOT IN (' . implode(',', $migrated) . ')');
		}
		$db->setQuery($query);
		$k2tags = $db->loadObjectList();
		$db->freeResult();
		return $k2tags;
	}

	function convertK2Categories()
	{
		$user = JFactory::getUser();
		$categories = $this->getTreeCategories();
		$count = 0;
		if (count($categories) > 0) {
			$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
			foreach ($categories as $k => $v) {
				$assoc = $this->checkAssociation($v->id, 'category');
				if (!$assoc) {
					if (isset($syncParams->catID->{$v->id})) {
						if ($syncParams->catID->{$v->id} == 'ignore') {
							continue;
						}
					}

					// check for exists.
					// Copy Image from K2.
					if (JFile::exists(JPATH_ROOT . '/media/k2/categories/' . $v->image)) {
						JFolder::create(JPATH_ROOT . '/images/joomlart/categories/'); // create recusive folder.
						copy('../media/k2/categories/' . $v->image, '../images/joomlart/categories/' . $v->image);
					}

					// Metadata
					$params = json_decode($v->params);

					$parent_id = 1;
					$parent = $this->checkAssociation($v->parent_id, 'category');
					if ($parent && $parent->key != 'auto' && $parent->key != 'ignore') {
						$parent_id = $parent->key;
					}

					$cparams = array(
						"category_layout" => '',
						"image" => 'images/joomlart/categories/' . $v->image
					);
					$metadata = array(
						"author" => (isset($params->catMetaAuthor) ? $params->catMetaAuthor : ''),
						"robots" => (isset($params->catMetaRobots) ? $params->catMetaRobots : '')
					);

					// array data will be insert.
					$alias = $this->generateAlias($v->alias, 'categories');
					$data = array(
						'id' 			=> 0,
						'hits' 			=> 0,
						'parent_id' 	=> $parent_id,
						'extension' 	=> 'com_content',
						'title' 		=> $v->title,
						'alias' 		=> $alias,
						'description' 	=> ($v->description),
						'path' 			=> $alias,
						'published' 	=> ($v->trash == 1 ? -2 : $v->published),
						'access' 		=> ($v->access),
						'params' 		=> json_encode($cparams),
						'language' 		=> ($v->language),
						'metadata' 		=> '',
						'rules' 		=> array(),
						'metadesc' 		=> (isset($params->catMetaDesc) ? $params->catMetaDesc : ''),
						'metakey' 		=> (isset($params->catMetaKey) ? $params->catMetaKey : ''),
						'created_user_id' => $user->id,
						'created_time' 	=> '',
						'modified_user_id' => '',
						'metadata' 		=> json_encode($metadata),
						'tags' 			=> '',
						'version_note' 	=> '',
						'note' 			=> '',
						'level' 		=> (isset($v->level) ? $v->level : 1),
						'lft' 			=> NULL,
						'rgt' 			=> NULL,
					);
					$table = JTable::getInstance('Category', 'JTable');

					// Set the new parent id if parent id not matched OR while New/Save as Copy .
					if ($table->parent_id != $data['parent_id'] || $data['id'] == 0) {
						$table->setLocation($data['parent_id'], 'last-child');
					}

					// Bind the rules.
					if (isset($data['rules'])) {
						$rules = new JAccessRules($data['rules']);
						$table->setRules($rules);
					}

					if (!$table->bind($data)) {
						echo ($table->getError());
						return false;
					}

					if (!$table->check()) {
						echo ($table->getError());
						return false;
					}

					if (!$table->store()) {
						echo ($table->getError());
						return false;
					}
					$this->addAssociation($v->id, 'category', $table->id);

					// Rebuild the path for the category:
					if (!$table->rebuildPath($table->id)) {
						echo ($table->getError());
						return false;
					}

					$table = NULL; // free table;
					$count++;
				}
			}

			if ($count) {
				//rebuild category tree
				require_once(JPATH_ADMINISTRATOR . '/components/com_categories/tables/category.php');
				$config = array();
				$modelCat = JTable::getInstance('Category', 'CategoriesTable', $config);
				$modelCat->rebuild();
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_CATEGORIES_DONE', $count));
			}
		}
	}

	function convertK2Tags()
	{
		$k2tags = $this->fetchTags();
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
		$count = 0;
		if (count($k2tags)) {
			foreach ($k2tags as $k => $v) {
				$assoc = $this->checkAssociation($v->id, 'tag');

				if (!$assoc) {
					$tag = $this->getTag($v->name);
					if (!$tag) {
						// array data will be insert.
						$alias = $this->generateAlias($v->name, 'tags');
						$data = array(
							'id' 			=> 0,
							'hits' 			=> 0,
							'parent_id' 	=> 1,
							'level' 		=> 1,
							'title' 		=> $v->name,
							'alias' 		=> $alias,
							'path' 			=> $alias,
							'note' 			=> NULL,
							'description' 	=> NULL,
							'published' 	=> $v->published,
							'access' 		=> NULL,
							'metadesc' 		=> NULL,
							'metakey' 		=> NULL,
							'alias' 		=> NULL,
							'created_user_id' => NULL,
							'created_by_alias' => NULL,
							'created_time' => NULL,
							'modified_user_id' => NULL,
							'modified_time' => NULL,
							'language' 		=> '*',
							'version_note' 	=> NULL,
							'params' 		=> array(),
							'metadata' 		=> array('author' => NULL, 'robots' => NULL),
							'tags' 			=> NULL,
						);

						$table = JTable::getInstance('Tag', 'TagsTable');

						if (!$table->bind($data)) {
							echo ($table->getError());
							return false;
						}

						if (!$table->check()) {
							echo ($table->getError());
							return false;
						}

						if (!$table->store()) {
							echo ($table->getError());
							return false;
						}
						$this->addAssociation($v->id, 'tag', $table->id);

						// Rebuild the path for the tag:
						if (!$table->rebuildPath($table->id)) {
							echo ($table->getError());
							return false;
						}

						$count++;
						if ($count % 50 == 0) {
							//JADataMigrator::printr('...');
							sleep(1);
							// will be remove or change.
						}
					} else {
						$this->addAssociation($v->id, 'tag', $tag->id);
					}
				}
			}
		}
		if ($count) {
			JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_TAGS_DONE', $count));
		}
	}

	public function deleteAssoc()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query = "DELETE FROM `#__associations` WHERE context LIKE 'ja_migration.%'";
		$db->setQuery($query);
		if ($db->execute()) {
			echo '<pre>';
			print_r('delete assoc success');
			echo '</pre>';
		}
	}

	function write_to_file($data, $path_to_file, $override = true)
	{
		if (!$override) {
			file_put_contents($path_to_file, json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
		} else {
			file_put_contents($path_to_file, json_encode($data) . PHP_EOL);
		}
	}

	public static function generateSafeName($str)
	{
		$str = self::utf8tolatin($str);
		$str = preg_replace('/[^a-z0-9_]+/', '_', $str);
		return strtolower($str);
	}

	public static function utf8tolatin($str)
	{
		$utf8 = array(
			"à", "á", "ạ", "ả", "ã", "â", "ầ", "ấ", "ậ", "ẩ", "ẫ", "ă",
			"ằ", "ắ", "ặ", "ẳ", "ẵ", "è", "é", "ẹ", "ẻ", "ẽ", "ê", "ề",
			"ế", "ệ", "ể", "ễ",
			"ì", "í", "ị", "ỉ", "ĩ",
			"ò", "ó", "ọ", "ỏ", "õ", "ô", "ồ", "ố", "ộ", "ổ", "ỗ", "ơ",
			"ờ", "ớ", "ợ", "ở", "ỡ",
			"ù", "ú", "ụ", "ủ", "ũ", "ư", "ừ", "ứ", "ự", "ử", "ữ",
			"ỳ", "ý", "ỵ", "ỷ", "ỹ",
			"đ",
			"À", "Á", "Ạ", "Ả", "Ã", "Â", "Ầ", "Ấ", "Ậ", "Ẩ", "Ẫ", "Ă",
			"Ằ", "Ắ", "Ặ", "Ẳ", "Ẵ",
			"È", "É", "Ẹ", "Ẻ", "Ẽ", "Ê", "Ề", "Ế", "Ệ", "Ể", "Ễ",
			"Ì", "Í", "Ị", "Ỉ", "Ĩ",
			"Ò", "Ó", "Ọ", "Ỏ", "Õ", "Ô", "Ồ", "Ố", "Ộ", "Ổ", "Ỗ", "Ơ", "Ờ", "Ớ", "Ợ", "Ở", "Ỡ",
			"Ù", "Ú", "Ụ", "Ủ", "Ũ", "Ư", "Ừ", "Ứ", "Ự", "Ử", "Ữ",
			"Ỳ", "Ý", "Ỵ", "Ỷ", "Ỹ",
			"Đ", " "
		);

		$latin = array(
			"a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a",
			"a", "a", "a", "a", "a", "a",
			"e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e",
			"i", "i", "i", "i", "i",
			"o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o",
			"o", "o", "o", "o", "o",
			"u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u",
			"y", "y", "y", "y", "y",
			"d",
			"A", "A", "A", "A", "A", "A", "A", "A", "A", "A", "A", "A",
			"A", "A", "A", "A", "A",
			"E", "E", "E", "E", "E", "E", "E", "E", "E", "E", "E",
			"I", "I", "I", "I", "I",
			"O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O",
			"U", "U", "U", "U", "U", "U", "U", "U", "U", "U", "U",
			"Y", "Y", "Y", "Y", "Y",
			"D", "_"
		);
		return strtolower(str_replace($utf8, $latin, $str));
	}

	static public function printr($text)
	{
		echo '<p>';
		echo $text;
		echo '</p>';
		ob_flush();
		flush();
		sleep(1);
	}

	static public function refresh()
	{
		return $html = '<a style="color:red" href="index.php?option=com_content" target="_parent">' . JText::_('JA_K2TOCONTENT_REFRESH') . '</a>';
	}

	function loadAssociated()
	{
		if ($this->assoc === null) {
			$db = JFactory::getDbo();
			$query = "SELECT CONCAT(context, '.', id) AS contextid, id, " . $db->quoteName('key') . " FROM #__associations WHERE context LIKE 'ja_migration.%'";
			$db->setQuery($query);

			$this->assoc = $db->loadObjectList('contextid');
		}
	}

	function getMigratedItems($type)
	{
		$this->loadAssociated();
		$ids = array();
		$context = 'ja_migration.' . $type . '.';
		if (count($this->assoc)) {
			foreach ($this->assoc as $contextid => $assoc) {
				if (strpos($contextid, $context) === 0) {
					if ($type == 'category' && ($assoc->key == 'ignore' || $assoc->key == 'auto')) {
						continue;
					}
					$ids[] = $assoc->id;
				}
			}
		}
		return $ids;
	}

	function checkExtraGroupAssoc($id)
	{
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__associations'))
			->where("id = " . $this->db->quote($id))
			->where($this->db->quoteName('context') . ' LIKE ' . $this->db->quote('%extraGroup%'));
		$this->db->setQuery($query);
		$data = $this->db->loadAssocList();
		if (!empty($data)) return true;
		return false;
	}

	/**
	 * @param int $k2id
	 * @param string $type
	 * 
	 * @return boolean
	 */
	function checkAssociation($k2id, $type)
	{
		$this->loadAssociated();
		$contextid = sprintf('ja_migration.%s.%d', $type, $k2id);
		return isset($this->assoc[$contextid]) ? $this->assoc[$contextid] : false;
	}

	function addAssociation($k2id, $type, $contentid)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$context = 'ja_migration.' . $type;
		$contextid = $context . '.' . $k2id;

		$assoc = $this->checkAssociation($k2id, $type);
		if ($assoc) {
			$query->update('#__associations')
				->set($db->quoteName('key') . '=' . $db->quote($contentid))
				->where($db->quoteName('context') . '=' . $db->quote($context))
				->where($db->quoteName('id') . '=' . $db->quote($k2id));

			try {
				$db->setQuery($query);
				$db->execute();
			} catch (RuntimeException $e) {
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}

			$this->assoc[$contextid]->key = $contentid;
		} else {
			$query->insert('#__associations')
				->columns(array($db->quoteName('id'), $db->quoteName('context'), $db->quoteName('key')))
				->values($db->quote($k2id) . ',' . $db->quote($context) . ',' . $db->quote($contentid));
			try {
				$db->setQuery($query);
				$db->execute();
			} catch (RuntimeException $e) {
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}

			$this->assoc[$contextid] = (object) array(
				'contextid' => $contextid,
				'id' => $k2id,
				'key' => $contentid
			);
		}
	}

	function checkPluginCTT()
	{
		if (file_exists(JPATH_ROOT . '/plugins/system/jacontenttype/jacontenttype.php')) {
			$plugins = JPluginHelper::getPlugin('system');
			foreach ($plugins as $pl) {
				if ($pl->name == 'jacontenttype') {
					return true;
				}
			}
		}
		return false;
	}
}
