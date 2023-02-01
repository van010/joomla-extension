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

require_once dirname(__DIR__) . '/helpers/others_migrator.php';

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
		$this->path = JPATH_ROOT.'/plugins/system/jacontenttype/models/types/';
	}

	public function recontent() {
		// start the flush
		if (ob_get_level() == 0) ob_start();
		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_RECONTENT_START'));
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('context').', '.$this->db->quoteName('key', 'id'))
			->from($this->db->quoteName('#__associations'))
			->where($this->db->quoteName('context').' LIKE '.$this->db->quote('ja_migration%'));
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();
		$other_migrator = new JaOthersMigrator();
		$table = array();
		$extrafields = 0;
		foreach ($items AS $item) {
			// handle jlex
			$other_migrator::recontent_jlex($item);
			/*switch ($item->context){
				case 'ja_migration.extraGroup':
					@unlink($this->path.$item->id.'.xml');
					@JFile::delete($this->path.$item->id.'.xml');
					$extrafields++;
					break;
				case 'ja_migration.ExtraGroup':
					$table['#__fields_groups'][] = $item->id;
					break;
				case 'ja_migration.ExtraField':
					$table['#__fields'][] = $item->id;
					break;
				case 'ja_migration.category':
					$table['#__categories'][] = $item->id;
					break;
				case 'ja_migration.tag':
					$table['#__tags'][] = $item->id;
					break;
				case 'ja_migration.item':
					$table['#__content'][] = $item->id;
					break;
			}*/
			if ($item->context == 'ja_migration.extraGroup') {
				@unlink($this->path.$item->id.'.xml');
				@JFile::delete($this->path.$item->id.'.xml');
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
		}

		foreach ($table AS $k => $v) {
			if ($k == '#__tags') {
				$query = $this->db->getQuery(true);
				$query->delete($this->db->quoteName($k));
				$query->where($this->db->quoteName('id') . ' IN ('.implode(',',$v).')');
			} else {
				$query = 'DELETE FROM '.$this->db->quoteName($k).', '.$this->db->quoteName('#__assets').'
					USING '.$this->db->quoteName('#__assets').' INNER JOIN '.$this->db->quoteName($k).'
					WHERE '.$this->db->quoteName('#__assets').'.`id` = '.$this->db->quoteName($k).'.`asset_id`
					AND '.$this->db->quoteName($k).'.`id` IN ('.implode(',',$v).')';
			}
			try {
				$this->db->setQuery($query);
				$this->db->execute();
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_'.strtoupper(str_replace(array('#__','content'),array('','items'),$k)).'_DONE', count($v)));
			}catch (RuntimeException $e) {
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}
		}
		if($extrafields) JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $extrafields));
		$query = $this->db->getQuery(true);
		$query->delete($this->db->quoteName('#__associations'));
		$query->where($this->db->quoteName('context') . ' LIKE '.$this->db->quote('ja_migration%').'');
		
		try {
			$this->db->setQuery($query);
			$this->db->execute();
		}catch (RuntimeException $e) {
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}

		JADataMigrator::printr(JText::_('JA_K2TOCONTENT_DONE'));
		JADataMigrator::printr(JADataMigrator::refresh());
		// End flush
		ob_end_flush();
	}

	public function errorCheck() {
		$error=array();
		
		// no title check.
		$sql = 'SELECT id FROM #__k2_items WHERE title = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['item_empty'] = $items;
		}
		
		$sql = 'SELECT id FROM #__k2_categories WHERE name = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['category_empty'] = $items;
		}
		
		$sql = 'SELECT id FROM #__k2_tags WHERE name = ""';
		$this->db->setQuery($sql);
		$items = $this->db->loadObjectList();
		if (!empty($items)) {
			$error['tag_empty'] = $items;
		}
		
		$sql = 'SELECT id FROM #__tags ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process=array();
		$process['tags']=false;
		if (!empty($items)) {
			$process['tags'] = $items;
		}
		$sql = 'SELECT id FROM #__categories ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$firstCateId = $this->db->setQuery('SELECT id FROM #__categories ORDER BY id ASC LIMIT 1')->loadResult();
		// fix categories getNode(1,)
		if ((int) $firstCateId != 1){
			$query1 = 'INSERT INTO #__categories (id,asset_id,parent_id,lft,rgt,level,path,extension,title,alias,note,description,published,checked_out,checked_out_time,access,params,metadesc,metakey,metadata,created_user_id,created_time,modified_user_id,modified_time,hits,language,version)' .
								'VALUES (1,"0","0","0",888,"0","","system","ROOT","root","","",1,"0","1000-10-10 00:00:00",1,"{}","","","",434,"2009-10-18 16:07:09","0","1000-10-10 00:00:00","0","*",1)';
			$this->db->setQuery($query1);
			$this->db->execute();
		}
		$process['categories']=false;
		if (!empty($items)) {
			$process['categories'] = $items;
		}
		$sql = 'SELECT id FROM #__content ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['content']=false;
		if (!empty($items)) {
			$process['content'] = $items;
		}
		
		sleep(5);
		
		$sql = 'SELECT id FROM #__tags ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['tags2']=false;
		if (!empty($items)) {
			$process['tags2'] = $items;
		}
		$sql = 'SELECT id FROM #__categories ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['categories2']=false;
		if (!empty($items)) {
			$process['categories2'] = $items;
		}
		$sql = 'SELECT id FROM #__content ORDER BY id DESC LIMIT 1';
		$this->db->setQuery($sql);
		$items = $this->db->loadResult();
		$process['content2']=false;
		if (!empty($items)) {
			$process['content2'] = $items;
		}

		if ($process['tags'] != $process['tags2']
			|| $process['categories'] != $process['categories2']
			|| $process['content'] != $process['content2']
		) {
			$error['process'] = JText::_('JAK2_MIGRATE_PROCESS_IS_RUNNING');
		}
		
		return $error;
	}

	public function migrate() {
		// start the flush
		if (ob_get_level() == 0) ob_start();
		$error = $this->errorCheck();
		if (!empty($error)) {
			foreach ($error AS $k => $er) {
				if (!empty($er)) {
					echo JText::_('JAK2_MIGRATE_ERROR_'.strtoupper($k));
					echo '<br/>';
					foreach ($er AS $ek => $ev) {
						// if (!empty($ev->name)) {
						// 	echo 'ALIAS:';
						// 	echo $ev->name;
						// 	echo '<br/>';
						// }
						echo 'ID:';
						echo $ev->id;
						echo '<br/>';
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

		// Convert Tags
		$this->convertK2Tags();
		if($this->extra_type == 'extra'){
			// Convert Extrafield
				$this->convertExtraField2();
		}else{
			$system_ctt = false;
			if (file_exists(JPATH_ROOT . '/plugins/system/jacontenttype/jacontenttype.php'))
			{
				$plugins = JPluginHelper::getPlugin('system');
				foreach ($plugins AS $pl) {
					if ($pl->name == 'jacontenttype') {
						$system_ctt=true;
					}
				}
				// make sure the system plugin content is install & enabled.
				if ($system_ctt==true) {
					// Convert Extrafield
					$this->convertExtraField();
				}
			}
		}
		// Convert Items
		$this->convertK2Items();

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

	function getTag($tag) {
		$query = $this->db->getQuery(true);
		$query->select('*')->from('#__tags')->where('LOWER(alias) = '.$this->db->quote($this->cleanAlias($tag)));
		$this->db->setQuery($query);
		return $this->db->loadObject();
	}
	
	function cleanAlias($alias) {
		$alias = JApplicationHelper::stringURLSafe($alias);
		return strtolower($alias);
	}


	function generateAlias($alias, $table) {
		$alias = $this->cleanAlias($alias);
		$index = 0;

		$query = $this->db->getQuery(true);
		$query->select('id')->from('#__'.$table);
		do {
			$newalias = !$index ? $alias : $alias .'-'.$index;
			if($table == 'tags') {
				$newalias = str_replace('-', '', $newalias);
			}
			$query->clear('where');
			$query->where('LOWER(alias) = '.$this->db->quote($newalias));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();
			$index++;
		} while ($exists);

		return $newalias;
	}
	
	// check exists item using name
	function checkName($name, $table, $id) {
		$query = $this->db->getQuery(true);
		$query->select('id')->from($this->db->quoteName('#__'.$table))->where('title = "'.$name.'"');
		$this->db->setQuery($query);
		$check_name = $this->db->loadResult();
		$this->db->freeResult();

		if (!$check_name) return $name;
		else return $id.'-'.$name;
	}

	function fetchTags() {
		$migrated = $this->getMigratedItems('tag');
		// select all tag.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('t.*, GROUP_CONCAT(tx.itemID)')
			->from($db->quoteName('#__k2_tags', 't'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'tx').' ON (t.id = tx.tagID)')
			->group('t.id')
			->order('t.id');
		if (count($migrated)) {
			$query->where('t.id NOT IN ('.implode(',', $migrated).')');
		}
		$db->setQuery($query);
		$k2tags = $db->loadObjectList();
		$db->freeResult();
		return $k2tags;
	}

	function fetchItems($batch = 50) {
		$catid = $this->getMigratedItems('category');
		if (!count($catid)) return array();

		$catid = array_unique($catid);

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('i.*, GROUP_CONCAT(tx.tagID) AS tags_id')
			->from($db->quoteName('#__k2_items', 'i'))
			->join('LEFT', $db->quoteName('#__k2_tags_xref', 'tx').' ON (tx.itemID = i.id)')
			->join('LEFT', $db->quoteName('#__associations', 'assoc')." ON (assoc.id = i.id AND assoc.context = 'ja_migration.item')")
			->group('i.id')
			->where('assoc.id IS NULL')
			->where('i.catid IN ('.implode(',', $catid).')');

		$db->setQuery($query, 0, $batch);
		$k2items = $db->loadObjectList();
		$db->freeResult();
		return $k2items;
	}

	function convertK2Categories()
	{
		$user = JFactory::getUser();
		$categories = $this->getTreeCategories();
		$count=0;
		if (count($categories) > 0) {
			$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
			foreach ($categories AS $k => $v) {
				$assoc = $this->checkAssociation($v->id, 'category');
				if (!$assoc) {
					if(isset($syncParams->catID->{$v->id})) {
						if($syncParams->catID->{$v->id} == 'ignore') {
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
					if($parent && $parent->key != 'auto' && $parent->key != 'ignore') {
						$parent_id = $parent->key;
					}

					$cparams = array(
						"category_layout" => '',
						"image" => 'images/joomlart/categories/'.$v->image
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
						'published' 	=> ($v->trash==1 ? -2 : $v->published),
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
						echo($table->getError());
						return false;
					}

					if (!$table->store()) {
						echo($table->getError());
						return false;
					}
					$this->addAssociation($v->id, 'category', $table->id);

					// Rebuild the path for the category:
					if (!$table->rebuildPath($table->id)) {
						echo($table->getError());
						return false;
					}

					$table=NULL; // free table;
					$count++;
					if ($count%50==0) {
						//JADataMigrator::printr('...');
						//sleep(1);
					}
				}
			}


			if($count) {
				//rebuild category tree
				require_once( JPATH_ADMINISTRATOR . '/components/com_categories/tables/category.php' );
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
		$count=0;
		if (count($k2tags)) {
			foreach ($k2tags AS $k => $v) {
				$assoc = $this->checkAssociation($v->id, 'tag');

				if(!$assoc) {
					$tag = $this->getTag($v->name);
					if(!$tag) {
						// array data will be insert.
						$alias = $this->generateAlias($v->name, 'tags');
						$data = array (
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
							echo($table->getError());
							return false;
						}

						if (!$table->store()) {
							echo($table->getError());
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
		if($count) {
			JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_TAGS_DONE', $count));
		}
	}

	public function convertK2Items() {
		$batch = 50;
		// $i = 0;
		do {
			$numitems = (int) $this->_convertK2Items($batch);
			// $i++;
			if($numitems) {
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_ITEMS_DONE', $numitems));
			}
		 } while ($batch <= $numitems);
		// } while ($i <= 4);
//		 $this->deleteAssoc();
	}
	
	public function deleteAssoc(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query = "DELETE FROM `#__associations` WHERE context LIKE 'ja_migration.%'";
		$db->setQuery($query);
		if ($db->execute()){
			echo '<pre>';
			print_r('delete assoc success');
			echo '</pre>';
		}
	}

	public function deleteJlexContent(){
		$query = "DELETE FROM `#__jlexcomment_obj` WHERE com_name LIKE 'com_content.%'";
		$this->db->setQuery($query);
		if($this->db->execute()){
			echo '<pre>';print_r('Delete jlex content comment');echo '</pre>';
		}
	}

	private function _convertK2Items($batch = 50) {
		$k2items = $this->fetchItems($batch); // select items with cat and extra fields condition.
		$numItems = count($k2items);
		if (!$numItems) {
			return 0;
		}
		$qi_q = 'INSERT INTO #__content_meta (id, content_id, meta_key, meta_value , encoded) VALUES ';
		$qi_v = array();
		$count=0;
		$other_migrator = new JaOthersMigrator();
    $jlex_obj_inserted = array();

		foreach ($k2items AS $k => $v) {
			$tags = array();
			$assoc = $this->checkAssociation($v->id, 'item');
			if (!$assoc) {
				// Copy Image from K2.
				if (JFile::exists(JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $v->id) . '.jpg')) {
					JFolder::create(JPATH_ROOT . '/images/joomlart/article/'); // create recusive folder.
					// copy('../media/k2/items/src/' . md5('Image' . $v->id) . '.jpg', '../images/joomlart/article/' . md5('Image' . $v->id) . '.jpg');
					if (copy('../media/k2/items/src/' . md5('Image' . $v->id) . '.jpg', '../images/joomlart/article/' . md5('Image' . $v->id) . '.jpg')){
						echo '<pre style="color: red">';print_r("copy img success - id: $v->id");echo '</pre>';
					}
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
				} else
					$images = '';

				// Convert Tag id from k2 to new id.
				if ($v->tags_id != NULL) {
					$tags_id = explode(',', $v->tags_id);
					foreach ($tags_id AS $tg) {
						$tag = $this->checkAssociation($tg, 'tag');
						if($tag) {
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
				$category = $this->checkAssociation($v->catid, 'category');
				if($category && $category->key != 'auto' && $category->key != 'ignore') {
					$catid = $category->key;
				}
				$alias = $this->generateAlias($v->alias, 'content');

				$data = array (
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
						'robots'	=>(isset($meta[0]) ? substr($meta[0], 7) : ''),
						'author'	=>(isset($meta[1]) ? ltrim($meta[1]) : ''),
						'rights'	=>'',
						'xreference'=>'',
					)
				);
				$table = JTable::getInstance('Content', 'JTable');
				
				if (!$table->bind($data)) {
					echo ($table->getError());
					return false;
				}

				if (!$table->check()) {
					echo($table->getError());
					return false;
				}

				if (!$table->store()) {
					echo($table->getError());
					return false;
				}
        $contentId = $table->get('id');
        $extra__ = '';
        $this->importK2ExtraFieldItemToContent($v, $contentId, $extra__);
//				$other_migrator::main($contentId, $v->id);
//        $new_jlex_obj_id = $other_migrator::importIntoJlexObj($contentId, $v->id);
//        $jlex_obj_inserted[] = $new_jlex_obj_id;
				// insert to merged table
				if ($table->id != 0) {
					$this->addAssociation($v->id, 'item', $table->id);
					if (count($tags)>0) {
						$this->updateTagItem($table, $tags);
					}

					if ($attrib != NULL) {
						$cmeta = json_decode($attrib);
						foreach ($cmeta AS $cmk => $cmv) {
							$val = ((is_object($cmv) || is_array($cmv)) ? json_encode($cmv) : $cmv);
							$encode = ((is_object($cmv) || is_array($cmv)) ? 1 : 0);
							if (preg_match("/ctm_/i", $cmk))
								$qi_v[] = '(NULL, '.$table->id.', "'.str_replace('ctm_','',$cmk).'", "'.addslashes($val).'", '.$encode.')';
						}
					}
					$table=NULL; // free table;
					$count++;
				}
			}
		}
		
		// Insert to content_meta ro sort in system content_type
		if (count($qi_v) > 0) {
			$qi_v = array_unique($qi_v);
			$qi_q .= implode(', ', $qi_v);
			if ($this->checkPluginCTT() == true) {
				try {
					$this->db->setQuery($qi_q);
					$this->db->execute();
					$this->db->freeResult();
				}catch (RuntimeException $e){
					JFactory::getApplication()->enqueueMessage($e, 'error');
				}
			}
		}
		
//		if (count($jlex_obj_inserted) > 0){
//      JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_TO_JLEXOBJ_DONE', count($jlex_obj_inserted)));
//    }else{
//      JADataMigrator::printr(JText::_('JA_K2TOCONTENT_TO_JLEXOBJ_ERROR'));
//    }
		return $numItems;
	}

	function updateTagItem($table, $tags) {
		// update core content id
		$cciQuery = $this->db->getQuery(true);
		$cciQuery->insert('#__ucm_content');
		$cciQuery->columns(array(
			$this->db->quoteName('core_content_id'),
			$this->db->quoteName('core_type_alias'),
			$this->db->quoteName('core_title'),
			$this->db->quoteName('core_alias'),
			$this->db->quoteName('core_body'),
			$this->db->quoteName('core_state'),
			$this->db->quoteName('core_checked_out_time'),
			$this->db->quoteName('core_checked_out_user_id'),
			$this->db->quoteName('core_access'),
			$this->db->quoteName('core_params'),
			$this->db->quoteName('core_featured'),
			$this->db->quoteName('core_metadata'),
			$this->db->quoteName('core_created_user_id'),
			$this->db->quoteName('core_created_by_alias'),
			$this->db->quoteName('core_created_time'),
			$this->db->quoteName('core_modified_user_id'),
			$this->db->quoteName('core_modified_time'),
			$this->db->quoteName('core_language'),
			$this->db->quoteName('core_publish_up'),
			$this->db->quoteName('core_publish_down'),
			$this->db->quoteName('core_content_item_id'),
			$this->db->quoteName('asset_id'),
			$this->db->quoteName('core_images'),
			$this->db->quoteName('core_urls'),
			$this->db->quoteName('core_hits'),
			$this->db->quoteName('core_version'),
			$this->db->quoteName('core_ordering'),
			$this->db->quoteName('core_metakey'),
			$this->db->quoteName('core_metadesc'),
			$this->db->quoteName('core_catid'),
			$this->db->quoteName('core_xreference'),
			$this->db->quoteName('core_type_id'),
		));

		$cciQuery->values(
			'NULL , ' .
			$this->db->quote('com_content.article') . ', ' .
			$this->db->quote($table->title).','.
			$this->db->quote($table->alias).','.
			'"",'.
			$table->state.','.
			'"",'.
			'0,'.
			'1,'.
			$this->db->quote($table->attribs).','.
			'1,'.
			$this->db->quote($table->metadata).','.
			'"'.$table->created_by.'",'.
			'"",'.
			'"'.date('Y-m-d H:i:s').'",'.
			'"'.$table->created_by.'",'.
			'"'.date('Y-m-d H:i:s').'",'.
			'"'.$table->language.'",'.
			'"0000-00-00 00:00:00",'.
			'"0000-00-00 00:00:00",'.
			$table->id.','.
			$table->asset_id.','.
			$this->db->quote($table->images).','.
			'"",'.
			$table->hits.','.
			$table->version.','.
			'0,'.
			$this->db->quote($table->metakey).','.
			$this->db->quote($table->metadesc).','.
			$table->catid.','.
			$this->db->quote($table->xreference).','.
			'1'
		);
		try {
			$this->db->setQuery($cciQuery);
			$this->db->execute();
		}catch (RuntimeException $e){
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}
		$ucmid = $this->db->insertid();

		foreach ($tags AS $tag) {
			if (!empty($tag)) {
				$query = $this->db->getQuery(true);
				$query->select('core_content_id')->from($this->db->quoteName('#__contentitem_tag_map'))
					->where('type_id=1 AND tag_id='.$tag.' AND content_item_id='.$table->id);
				$this->db->setQuery($query);
				$check_tagmap = $this->db->loadResult();
				$this->db->freeResult();
				if (!$check_tagmap) {
					$tagmap = $this->db->getQuery(true);
					$tagmap->insert('#__contentitem_tag_map');
					$tagmap->columns(array(
						$this->db->quoteName('type_alias'),
						$this->db->quoteName('core_content_id'),
						$this->db->quoteName('content_item_id'),
						$this->db->quoteName('tag_id'),
						$this->db->quoteName('tag_date'),
						$this->db->quoteName('type_id'),
					));
					$tagmap->values(
						$this->db->quote('com_content.article').','.
						$ucmid.','.
						$table->id.','.
						$tag.','.
						'"'.date('Y-m-d H:i:s').'",'.
						'1'
					);

					try {
						$this->db->setQuery($tagmap);
						$this->db->execute();
					}catch (RuntimeException $e) {
						JFactory::getApplication()->enqueueMessage($e, 'error');
					}
				}
			}
		}

			
		$ucm_base = $this->db->getQuery(true);
		$ucm_base->insert('#__ucm_base');
		$ucm_base->columns(array(
			$this->db->quoteName('ucm_id'),
			$this->db->quoteName('ucm_item_id'),
			$this->db->quoteName('ucm_type_id'),
			$this->db->quoteName('ucm_language_id')
		));
		$ucm_base->values(
			$ucmid.','.
			$table->id.','.
			'1,'.
			'0'
		);

		try {
			$this->db->setQuery($ucm_base);
			$this->db->execute();
		}catch (RuntimeException $e){
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}
		
		$assets = $this->db->getQuery(true);
		$assets->insert('#__assets');
		$assets->columns(array(
			$this->db->quoteName('id'),
			$this->db->quoteName('parent_id'),
			$this->db->quoteName('lft'),
			$this->db->quoteName('rgt'),
			$this->db->quoteName('level'),
			$this->db->quoteName('name'),
			$this->db->quoteName('title'),
			$this->db->quoteName('rules')
		));
		$assets->values(
			'NULL,'.
			'1,'.
			'0,'.
			'0,'.
			'1,'.
			'"#__ucm_content.'.$ucmid.'",'.
			'"#__ucm_content.'.$ucmid.'",'.
			'"[]"'
		);

		try {
			$this->db->setQuery($assets);
			$this->db->execute();
			$this->db->freeResult();
		}catch (RuntimeException $e){
			JFactory::getApplication()->enqueueMessage($e, 'error');
		}
	}
	
	function convertExtraField()
	{
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);

		// get the field will be reset and make new file xml.
		if (isset($syncParams->extraGroup) && is_array($syncParams->extraGroup) && count($syncParams->extraGroup)) {
			$count = 0;
			foreach ($syncParams->extraGroup AS $g => $gv) {
				//$assoc = $this->checkAssociation($g, 'extraGroup');
				//if(!$assoc) {
					if ($gv != 'ignore') {
						$this->createContentTypeManifest($g, $syncParams);
						$count++;
					}
				//}
			}
			if($count) {
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $count));
			}
		}

	}
	function convertExtraField2()
	{
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
		// get the field will be reset and make new file xml.
		if (isset($syncParams->extraGroup) && count((array)$syncParams->extraGroup)) {
			$count = 0;
			foreach ($syncParams->extraGroup AS $g => $gv) {
				//$assoc = $this->checkAssociation($g, 'extraGroup');
				//if(!$assoc) {
					if ($gv != 'ignore') {
						$this->createContentExtraField($g, $syncParams);
						$count++;
					}
				//}
			}
			if($count) {
				JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXFIELDS_DONE', $count));
			}
		}

	}

	function createContentTypeManifest($groupid, $syncParams) {
		// Processing to make a new xml file. always make to check for new field.
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields_groups'))->where('id='.$db->quote($groupid));
		$db->setQuery($query);
		$group = $db->loadObject();
		if(!$group) return;

		// select extra fields.
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields', 'a'));
		$query->where('a.group ='.$db->quote($groupid));
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		if($syncParams->extraGroup->{$groupid} != 'auto') {
			$filename = $syncParams->extraGroup->{$groupid};
		} else {
			$filename = $syncParams->exgipName->{$groupid};
		}
		$cttname = JADataMigrator::utf8tolatin(trim($filename) == '' ? $group->name : trim($filename));

		$exfieldgroupname = JFile::makeSafe($cttname . '.xml');
		$buffer = '<?xml version="1.0" encoding="utf-8" ?>'."\n".
			'<form>'."\n".
			"\t".'<type>' . $cttname . '</type>' ."\n".
			"\t".'<title><![CDATA[' . $group->name . ']]></title>' ."\n".
			"\t".'<fields name="attribs">' ."\n".
			"\t"."\t".'<fieldset name="content_meta" label="' . (htmlentities($group->name)) . '">'."\n";
		$buffer.= "
			<!--IS REQUIRED FIELD-->
			<field name=\"ctm_content_type\"
						type=\"hidden\"
						default=\"{$cttname}\"
						label=\"PLG_JACONTENT_TYPE_CONTENT_TYPE_LABEL\"
						description=\"PLG_JACONTENT_TYPE_CONTENT_TYPE_DESC\"/>
				<!--IS REQUIRED FIELD--> \n";

		$languageTexts = array();
		for($i = 0; $i<count($fields); $i++){
			$field = $fields[$i];
			if(isset($syncParams->extraField->{$groupid}->{$field->id}) && $syncParams->extraField->{$groupid}->{$field->id} == 'ignore')
				continue;

			if($field->type == 'header') {
				continue;
			}

			if (array_key_exists($field->id, (array) $syncParams->extraField->{$groupid})) {
        if ($syncParams->extraField->{$groupid}->{$field->id} != 'auto') {
          $fieldname = trim($syncParams->extraField->{$groupid}->{$field->id});
        } else {
          $fieldname = trim(@$syncParams->exfipName->{$groupid}->{$field->id});
        }
      }
			if(empty($fieldname)) $fieldname = $field->name;

			$this->addAssociation($field->id, 'extraField', $fieldname);

			$field->value = json_decode($field->value, false);

			switch($field->type) {
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
					$field->multiple = 'true';
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
			$label = 'PLG_JACONTENT_TYPE_'.strtoupper($fieldname).'_LABEL';
			$desc = 'PLG_JACONTENT_TYPE_'.strtoupper($fieldname).'_DESC';
			$languageTexts[] = $label .'="'.$field->name.'"';
			$languageTexts[] = $desc .'="'.$field->name.'"';
			$buffer .= "\t"."\t"."\t".'<field'."\n".
				"\t"."\t"."\t"."\t".'name="ctm_'.str_replace('ctm_', '', $fieldname).'"'."\n".
				"\t"."\t"."\t"."\t".'type="'.$field->type.'"'."\n".
				"\t"."\t"."\t"."\t".'label="'.$label.'"'."\n".
				"\t"."\t"."\t"."\t".'description="'.$desc.'"'."\n";

			for($k = 0; $k < count($field->value); $k++){
				if($field->type == 'text') {
					$buffer .= "\t"."\t"."\t"."\t".'default="'.(string)$field->value[$k]->value.'"'."\n";
				}
				if(isset($field->value[$k]->required)){
					if($field->value[$k]->required = 1){
						$buffer .= "\t"."\t"."\t"."\t".'required="true"'."\n";
						break;
					}
				}
			}
			if(in_array($field->type, array('list','radio','checkboxes'))) {
				$def = 'default="0"';
				if($field->type != 'radio'){
					$buffer .= "\t"."\t"."\t"."\t".'multiple="'.$field->multiple.'"'."\n".
						"\t"."\t"."\t"."\t".'>'."\n";
				} else {
					$buffer .= '>'."\n";
				}

				//if ($field->type != 'radio') $buffer .= "\t"."\t"."\t"."\t"."\t".'<option value="0">Select '.$field->name.'</option>'."\n";
				for($j = 0; $j<count($field->value); $j++){
					$buffer .= "\t"."\t"."\t"."\t"."\t".'<option value="'.$field->value[$j]->value.'"><![CDATA['.$field->value[$j]->name.']]></option>'."\n";
				}
				$buffer .= "\t"."\t"."\t".'</field>'."\n"."\n";
			}else{
				$buffer .= '/>'."\n";
			}
		}
		$buffer .= "\t"."\t".'</fieldset>'."\n".
			"\t".'</fields>'."\n".
			'</form>';

		if(JFile::write($this->path.$exfieldgroupname, $buffer)) {
			$this->addAssociation($groupid, 'extraGroup', $cttname);
		}

		if(count($languageTexts)) {
			$langFile = JPATH_ROOT.'/administrator/language/en-GB/en-GB.plg_system_jacontenttype_ex.ini';
			$txt = implode("\n", $languageTexts);
			if(JFile::exists($langFile)) {
				$txt = file_get_contents($langFile) . "\n" . $txt;
			}
			JFile::write($langFile, $txt);
		}
	}


	function createContentExtraField($groupid, $syncParams){
		$user = JFactory::getUser();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields_groups'))->where('id='.$db->quote($groupid));
		$db->setQuery($query);
		$group = $db->loadObject();
		$countField = 0;
		if(!$group) return;

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
		$checkExtraGroup = $this->checkExtraGroupAssoc($group->id, 'extraGroup');
		if ($checkExtraGroup) return false;
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
			echo($GroupTable->getError());
			return false;
		}

		if (!$GroupTable->store()) {
			echo($GroupTable->getError());
			return false;
		}
		$extra_groupid = $GroupTable->id;
		$this->addAssociation($groupid, 'ExtraGroup', $extra_groupid);

		// select extra fields.
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__k2_extra_fields', 'a'));
		$query->where('a.group ='.$db->quote($groupid));
		$db->setQuery($query);
		$fields = $db->loadObjectList();

    $contentFields_ = new stdClass();
    $k2ExtraFields_ = new stdClass();
		foreach ($fields as $field) {
			$field->group = $extra_groupid;
      // the last extra field name will be duplicated in other field group if COM_FIELDS_ERROR_UNIQUE_NAME appear
      // print_r($field->name);
			if(isset($syncParams->extraField->{$groupid}->{$field->id}) && $syncParams->extraField->{$groupid}->{$field->id} == 'ignore')
				continue;

			if($field->type == 'header') {
				continue;
			}

      if (array_key_exists($field->id, (array) $syncParams->extraField->{$groupid})) {
        if ($syncParams->extraField->{$groupid}->{$field->id} != 'auto') {
          $fieldname = trim($syncParams->extraField->{$groupid}->{$field->id});
        } else {
          $fieldname = trim(@$syncParams->exfipName->{$groupid}->{$field->id});
        }
      }

			if(empty($fieldname)){
        $fieldname = $field->name;
      }

			$extraFieldValue = $this->convertk2ExtrFieldValue($field->value);
			switch($field->type) {
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
					$field->multiple = 'true';
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
			$data = array(
				"id" => 0,
			    "context" => "com_content.article",
			    "group_id" => $field->group,
			    "assigned_cat_ids" => Array(),
			    "title" => $field->name,
			    "name" => "",
			    "type" => $field->type,
			    "required" => 0,
			    "default_value" => "" ,
			    "state" => 1,
			    "created_user_id" => $user->id,
			    "created_time" => "",
			    "modified_time" => "",
			    "language" => "*",
			    "note" => "",
			    "label" => $field->name,
			    "description" => "",
			    "access" => 1,
			    "rules" => array('core.delete' => array(), 'core.edit' => array(), 'core.edit.state' => array()),
			    "params" => Array(
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
			$ExtrafieldTable = JTable::getInstance('Field','FieldsTable');

			if (!$ExtrafieldTable->bind($data)) {
				echo ($ExtrafieldTable->getError());
				return false;
			}

			if (!$ExtrafieldTable->check()) {
				echo($ExtrafieldTable->getError());
				return false;
			}

			if (!$ExtrafieldTable->store()) {
				echo($ExtrafieldTable->getError());
				return false;
			}

      $k2ExtraFieldId = (string) $field->id;
      $field_name = strtolower(str_replace(' ', '_', $field->name));
      $k2ExtraFields_->$k2ExtraFieldId = strtolower(str_replace(' ', '_', $field->name));
      $contentFields_->$field_name = $ExtrafieldTable->get('id');

      // $extraFieldContentId = $ExtrafieldTable->get('id');
			$this->addAssociation($field->id, 'ExtraField', $ExtrafieldTable->id);

			$countField++;

		}
//		echo '<pre>';print_r([$k2ExtraFields_, $contentFields_]);echo '</pre>';
    $this->k2ExtraFields[] = $k2ExtraFields_;
    $this->contentFields[] = $contentFields_;

		if($countField) {
			JADataMigrator::printr(JText::sprintf('JA_K2TOCONTENT_EXTRA_FIELD_DONE', $group->name, $countField));
		}
	}

	function mergeObj($obj){
		if (count((array)$obj) == 1){
			return $obj[0];
		}
		$arrMerged = (array) $obj[0] + (array) $obj[1];
		return (object) $arrMerged;
	}

  function importK2ExtraFieldItemToContent($item, $contentId, $extraFieldContentId){
    if ($this->contentFields && $this->k2ExtraFields) {
			$k2ExtraFieldsClone = $this->mergeObj($this->k2ExtraFields);
			$contentFieldsClone = $this->mergeObj($this->contentFields);
      $exValue = json_decode($item->extra_fields);
      foreach ($exValue as $k => $v) {
        $extraFieldStrId = (string) $v->id;
        if (array_key_exists((int) $extraFieldStrId, (array) $k2ExtraFieldsClone)) {
          $extraFieldName = (string)$k2ExtraFieldsClone->$extraFieldStrId;
          $fieldId = (int)$contentFieldsClone->$extraFieldName;
          if (is_array($v->value)) {
            $v_ = $v->value;
            $value = $v_[1] ?? $v_[0];
          } else {
            $value = !empty($v->value) ? $v->value : '';
          }
          $this->insertToFieldsValue($fieldId, $contentId, $value);
        }
      }
    }

  }


  function insertToFieldsValue($fieldId, $itemId, $value){
    $query = $this->db->getQuery(true);
    $query->insert('#__fields_values');
    $query->columns(array(
      $this->db->quoteName('field_id'),
      $this->db->quoteName('item_id'),
      $this->db->quoteName('value'),
    ));

    $query->values(
      $this->db->quote($fieldId) . ', ' .
      $this->db->quote($itemId).','.
      $this->db->quote($value)
    );
    try {
      $this->db->setQuery($query);
	    $this->db->execute();
//      if ($this->db->execute()){
//				echo '<pre style="color: green">';print_r('insert extrafield value: ' . $this->db->insertid());echo '</pre>';
//      }
    }catch (RuntimeException $e){
      echo '<pre>';
      print_r($e);
      echo '</pre>';
    }
  }

	function convertk2ExtrFieldValue($data){

		$result = array();
		$result['multiple'] = false;
		$result['options'] = array();
		$dataArr = json_decode($data,true);

		if(!empty($dataArr)){
			$count = 0;
			foreach ($dataArr as $item) {
				unset($item['target']);
				$result['options']['options'.$count] = $item;
				$count++;
			}

		}
		return $result;

	}
	function getAttrib ($item) {
		$buff = array();
		$exvalue = json_decode($item->extra_fields);

		if (JFolder::exists(JPATH_ROOT.'/media/k2/galleries/'.$item->id.'/')) {
			$files = JFolder::files(JPATH_ROOT.'/media/k2/galleries/'.$item->id.'/', '\.jpg$', false, true);
			$buff['ctm_jagallery'] = new stdClass();
			$buff['ctm_jagallery']->src = new stdClass();
			$buff['ctm_jagallery']->class = new stdClass();
			$buff['ctm_jagallery']->caption = new stdClass();
			$buff['ctm_jagallery']->link = new stdClass();
			foreach ($files AS $k => $f) {
				$arrname = explode('/', $f);
				$fn = end($arrname);
				JFolder::create(JPATH_ROOT.'/images/joomlart/article/'.$item->id.'/');
				copy('../media/k2/galleries/'.$item->id.'/'.$fn, '../images/joomlart/article/'.$item->id.'/'.$fn);
				$buff['ctm_jagallery']->src->$k = 'images/joomlart/article/'.$item->id.'/'.$fn;
				$buff['ctm_jagallery']->class->$k = '';
				$buff['ctm_jagallery']->caption->$k = '';
				$buff['ctm_jagallery']->link->$k = '';
			}
		}
		
		if (!empty($item->video) && trim($item->video)!='') {
			$buff['ctm_jaembed_text'] = $item->video;
		}

		if (is_array($exvalue) && count($exvalue)>0) {
			$group = $this->getExtraFieldGroup($exvalue[0]->id);

			if($group) {
				$idGroupCurrent = $group->id;
				$ctName='';
				$extraGroup = $this->checkAssociation($idGroupCurrent, 'extraGroup');
				if($extraGroup) {
					$ctName = $extraGroup->key;
				}

				if($ctName) {
					$buff['ctm_content_type'] = $ctName;

					foreach ($exvalue AS $k => $v) {
						$extraField = $this->checkAssociation($v->id, 'extraField');
						if($extraField) {
							$fieldname = $extraField->key;
							$buff['ctm_'.str_replace('ctm_', '', $fieldname)] = $v->value;
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

		if (count($buff) > 0)
			return json_encode($buff);
		else return NULL;
	}

	/**
	 * get extra field group that extra field belong to (from extra field id)
	 * @param $exid
	 * @return mixed
	 */
	function getExtraFieldGroup($exid) {
		$query = $this->db->getQuery(true);
		$query->select('efg.id, efg.name')->from($this->db->quoteName('#__k2_extra_fields', 'ef'))
			->join('INNER', $this->db->quoteName('#__k2_extra_fields_groups', 'efg').' ON (ef.group = efg.id)')
			->where('ef.id=' . $exid);
		$this->db->setQuery($query);
		$group = $this->db->loadObject();

		$this->db->freeResult();
		return $group;
	}

	public static function generateSafeName($str) {
		$str = self::utf8tolatin($str);
		$str = preg_replace('/[^a-z0-9_]+/', '_', $str);
		return strtolower($str);
	}
	
	public static function utf8tolatin($str)
	{
		$utf8=array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă",
			"ằ","ắ","ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề",
			"ế","ệ","ể","ễ",
			"ì","í","ị","ỉ","ĩ",
			"ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ",
			"ờ","ớ","ợ","ở","ỡ",
			"ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ",
			"ỳ","ý","ỵ","ỷ","ỹ",
			"đ",
			"À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă",
			"Ằ","Ắ","Ặ","Ẳ","Ẵ",
			"È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ",
			"Ì","Í","Ị","Ỉ","Ĩ",
			"Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ","Ờ","Ớ","Ợ","Ở","Ỡ",
			"Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ",
			"Ỳ","Ý","Ỵ","Ỷ","Ỹ",
			"Đ"," ");
		 
		$latin=array("a","a","a","a","a","a","a","a","a","a","a",
			"a","a","a","a","a","a",
			"e","e","e","e","e","e","e","e","e","e","e",
			"i","i","i","i","i",
			"o","o","o","o","o","o","o","o","o","o","o","o",
			"o","o","o","o","o",
			"u","u","u","u","u","u","u","u","u","u","u",
			"y","y","y","y","y",
			"d",
			"A","A","A","A","A","A","A","A","A","A","A","A",
			"A","A","A","A","A",
			"E","E","E","E","E","E","E","E","E","E","E",
			"I","I","I","I","I",
			"O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O",
			"U","U","U","U","U","U","U","U","U","U","U",
			"Y","Y","Y","Y","Y",
			"D","_");
		return strtolower(str_replace($utf8,$latin,$str));
	}
	
	static public function printr ($text) {
		echo '<p>';
		echo $text;
		echo '</p>';
		ob_flush();
        flush();
        sleep(1);
	}
	
	static public function refresh(){
        return $html ='<a style="color:red" href="index.php?option=com_content" target="_parent">'.JText::_('JA_K2TOCONTENT_REFRESH').'</a>';
    }

	function loadAssociated() {
		if($this->assoc === null) {
			$db = JFactory::getDbo();
			$query = "SELECT CONCAT(context, '.', id) AS contextid, id, ".$db->quoteName('key')." FROM #__associations WHERE context LIKE 'ja_migration.%'";
			$db->setQuery($query);

			$this->assoc = $db->loadObjectList('contextid');
		}
	}

	function getMigratedItems($type) {
		$this->loadAssociated();
		$ids = array();
		$context = 'ja_migration.'.$type.'.';
		if(count($this->assoc)) {
			foreach($this->assoc as $contextid => $assoc) {
				if(strpos($contextid, $context) === 0) {
					if($type == 'category' && ($assoc->key == 'ignore' || $assoc->key == 'auto')) {
						continue;
					}
					$ids[] = $assoc->id;
				}
			}
		}
		return $ids;
	}
	
	function checkExtraGroupAssoc($id, $type='extraGroup') {
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__associations'))
			->where("id = $id");
		$this->db->setQuery($query);
		$data = $this->db->loadAssocList();
		if (!empty($data)) return true;
		return false;
	}

	function checkAssociation($k2id, $type) {
		$this->loadAssociated();
		$contextid = sprintf('ja_migration.%s.%d', $type, $k2id);
		return isset($this->assoc[$contextid]) ? $this->assoc[$contextid] : false;
	}

	function addAssociation($k2id, $type, $contentid) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$context = 'ja_migration.'.$type;
		$contextid = $context.'.'.$k2id;

		$assoc = $this->checkAssociation($k2id, $type);
		if($assoc) {
			$query->update('#__associations')
				->set($db->quoteName('key').'='.$db->quote($contentid))
				->where($db->quoteName('context').'='.$db->quote($context))
				->where($db->quoteName('id').'='.$db->quote($k2id));
			
			try {
				$db->setQuery($query);
				$db->execute();
			}catch (RuntimeException $e){
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}

			$this->assoc[$contextid]->key = $contentid;
		} else {
			$query->insert('#__associations')
				->columns(array($db->quoteName('id'), $db->quoteName('context'), $db->quoteName('key')))
				->values($db->quote($k2id).','.$db->quote($context).','.$db->quote($contentid));
			try {
				$db->setQuery($query);
				$db->execute();
			}catch (RuntimeException $e){
				JFactory::getApplication()->enqueueMessage($e, 'error');
			}

			$this->assoc[$contextid] = (object) array(
				'contextid' => $contextid,
				'id' => $k2id,
				'key' => $contentid
			);
		}
	}
	
	function checkPluginCTT () {
		if (file_exists(JPATH_ROOT . '/plugins/system/jacontenttype/jacontenttype.php'))
		{
		    $plugins = JPluginHelper::getPlugin('system');
		    foreach ($plugins AS $pl) {
		    	if ($pl->name == 'jacontenttype') {
		    		return true;
		    	}
		    }
		}
		return false;
	}
	
}
