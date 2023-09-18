<?php

use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

class convertK2Attch{

	/**
	 * delete attachment information associates with joomla article
	 * when recontent
	 * 
	 * @return void
	 */
	public function recontentAttach(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('`#__ja_attach_ref`');
		$db->setQuery($query);
		$jAttach = $db->loadColumn();
		
		if (empty($jAttach)) return;

		$query->clear();
		$query->delete('`#__ja_attach_ref`')
			->where('`id` IN(' .implode(',', $jAttach).')');
		$db->setQuery($query);
		if ($db->execute()){
			JADataMigrator::printr('K2 migrated attachments remove: ' . count($jAttach));
		}
	}

	/**
	 * create tbl `#__ja_attach_ref` to store k2 attachments infor 
	 * and joomla article id
	 * 
	 * @return void
	 */
    public function createJaAttchTbl(){
        $tblName = 'ja_attach_ref';
        $db = JFactory::getDbo();
		$config = JFactory::getConfig();
		$db_prefix = $config->get('dbprefix');
		$table = $db_prefix . $tblName;

        if (in_array($table, $db->getTableList())){
			return;
		}

        $colums = [
            '`id` INT(11) NOT NULL AUTO_INCREMENT',
            '`j_id` INT(11) NOT NULL',
            '`k2_id` INT(11) NOT NULL',
            '`attch_id` INT(11) NOT NULL',
            '`filename` VARCHAR(255)',
            '`title` VARCHAR(255)',
            '`titleAttribute` TEXT',
            '`hit` INT(11)',
            '`migrated_at` DATETIME NOT NULL',
            'PRIMARY KEY (id)'
        ];

        $create_tbl_sql = "CREATE TABLE `$table` (" . implode(',', $colums) . ")";
        $db->setQuery($create_tbl_sql);
        if ($db->execute()){
            echo '<pre style="color: #0077a9">';printf('Create table: %s success!', $table);echo '</pre>';
        }
    }

	/**
	 * main migrate k2 attachments into joomla
	 * 
	 * @return void
	 */
    public function migrateAttachment(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('ass.`key` as j_id, att.*')
			->from('`#__associations` AS ass')
			->join('INNER', '`#__k2_attachments` as att ON att.itemID = ass.id')
			->where('`context` = ' . $db->quote('ja_migration.item'));
		$db->setQuery($query);
		$jArticle_assoc_attach = $db->loadAssocList();
		
		if (empty($jArticle_assoc_attach)) return;

		// check assoc in table `#__ja_attach_ref` before inserting
		$query->clear();
		$query->select('j_id')
			->from('`#__ja_attach_ref`');
		$db->setQuery($query);
		$j_id_assoc_attach = $db->loadColumn();
		
		if (!empty($j_id_assoc_attach)){
			foreach($jArticle_assoc_attach as $k => $att){
				if (in_array($att['j_id'], $j_id_assoc_attach)){
					unset($jArticle_assoc_attach[$k]);
				}
			}
		}

		if (empty($jArticle_assoc_attach)) return;

		// prepare attachments data
		$attachData = [];
		foreach($jArticle_assoc_attach as $k => $att){
			$attachData[] = 'NULL, ' . $db->quote($att['j_id']) . ',' . 
				$db->quote($att['itemID']) . ',' . $db->quote($att['id']) . ',' .
				$db->quote($att['filename']) . ',' . $db->quote($att['title']) . ',' .
				$db->quote($att['titleAttribute']) . ',' . $db->quote($att['hits']) . ',' .
				$db->quote(date('Y-m-d H:i:s'));
		}
		
		if (empty($attachData)){
			echo 'No attachment to import.';
			return;
		}

		// insert into `#__ja_attach_ref`
		$columns = '`id`, `j_id`, `k2_id`, `attch_id`, `filename`, `title`, `titleAttribute`, `hit`, `migrated_at`';
		$query->clear();
		$query->insert('`#__ja_attach_ref`')
			->columns(explode(', ', $columns))
			->values($attachData);
		try{
			$db->setQuery($query);
			if ($db->execute()){
				JADataMigrator::printr('K2 attachments: ' . count($attachData));
				/* $first_id_inserted = $db->insertid();
				for($i=0; $i<count($attachData); $i++){
				} */
			}
			
		}catch (RuntimeException $e){
			echo '<pre>'. print_r($e, true) .'</pre>';die();
		}
	}

	/**
	 * render a 'download pdf' button to a single article page
	 * 
	 * @param int $articleId
	 * @param string $articleTitle
	 * 
	 * @return array|mixed
	 */
    public static function fetchJoomlaAttachment($articleId, $articleTitle){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('`#__associations`')
			->where('`key` = ' . $db->quote($articleId))
			->where('`context` = ' . $db->quote('ja_migration.item'));
		$db->setQuery($query);
		$k2Id = $db->loadResult();
		
		if (empty($k2Id)) return ['code' => 404, 'message' => "No k2 item associated with a joomla article id: $articleId"];

		$query->clear();
		$query->select('att.*')
			->from('`#__k2_attachments` as att')
			->where('att.itemID = ' . $db->quote($k2Id));
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (empty($rows)) return ['code' => 404, 'message' => 'no attachment found'];

		$html = '';
		foreach ($rows as $row)
		{
			$hash = version_compare(JVERSION, '3.0', 'ge') ? JApplication::getHash($row->id) : JUtility::getHash($row->id);
			// $row->link = JRoute::_('index.php?option=com_k2&view=item&task=download&id='.$row->id.'_'.$hash);
			// parse link
			$urlParams = [
				'option' => 'com_ajax',
				'plugin' => 'jak2tocomcontentmigration',
				'jatask' => 'downloadAttachment',
				'format' => 'json',
				'id' => $row->id.'_'.$hash,
			];

			$urlDownload = JUri::root(true) . '/index.php?' . http_build_query($urlParams);
			$row->link = new Uri(JRoute::_($urlDownload));
			$html .= '<div class="dropdown button-pdf joomla-article-attachment">
						<a class="btn btn-primary pdf-button ja-download-btn" href="'.$row->link.'" target="_self">'.JText::_('MVIEW_PDF').'</a>
						</div>';
		}
		return ['data' => $html, 'message' => 'success', 'code' => 200];
	}

	/**
	 * download pdf file, base download was processed by ja, source still in /media/k2/attachments/
	 * need to migrate source into /media/jadownload/attachments/ in next phase
	 * 
	 * @return void|array
	 */
	public static function downloadAttachment(){
		$input = JFactory::getApplication()->input;
		$id = $input->get('id', '', 'RAW');
		
		if (empty($id)) return;

		$check = explode('_', $id)[1];
		$attachId = explode('_', $id)[0];
		$hash = version_compare(JVERSION, '3.0', 'ge') ? JApplication::getHash($attachId) : JUtility::getHash($attachId);
		if ($check != $hash){
			echo 'K2 attachment not found.';
			return;
		}

		$attachInfo = self::loadAttachments($attachId);
		if (empty($attachInfo)){
			echo 'K2 attachment not found!';
			return;
		}

		$path_to_attach = JPATH_ROOT . '/media/k2/attachments/';
		$file = $path_to_attach . $attachInfo->filename;
		if (!is_file($file)){
			echo 'File not found: ' . $attachInfo->filename;
			return;
		}
		
		$len = filesize($file);
		$filename = basename($file);
		$type = filetype($file);
		
		ob_end_clean();
		header('Content-Type: ' . $type);
		header('Content-Disposition: attachment; filename='.$filename.';');
		header('Content-Transfer-Encoding: binary');
		header('Pragma: public');
		header('Expires: 0');
		header('Content-Length: ' . $len . ';');
		set_time_limit(0);
		readfile($file);

		return ['message' => 'Download success.', 'code' => 200];
	}

	/**
	 * get attachment information associate with current joomla article
	 * 
	 * @param int $id
	 * 
	 * @return object first object of attachment information
	 */
	public static function loadAttachments($id){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('`#__ja_attach_ref`')
			->where('`attch_id` = ' . $db->quote($id));
		$db->setQuery($query);
		return $db->loadObjectList()[0];
	}
}

?>