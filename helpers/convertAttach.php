<?php 

defined('_JEXEC') or die;

class convertK2Attch{
    
    public function __construct() {
        // todo
    }

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

    public function getK2Assoc(){}

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
			$row->link = JRoute::_('index.php?option=com_k2&view=item&task=download&id='.$row->id.'_'.$hash);
			$html .= '<div class="dropdown button-pdf joomla-article-attachment">
						<a class="btn  btn-primary pdf-button" href="'.$row->link.'" target="_self">'.JText::_('MVIEW_PDF').'</a>
						</div>';
		}
		return ['data' => $html, 'message' => 'success', 'code' => 200];
	}

}

?>