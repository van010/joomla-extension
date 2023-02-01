<?php

defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

require_once dirname(__DIR__) . '/helpers/migrator.php';

class JaOthersMigrator{
  
  public function __construct(){}
  
  public static function main($itemId, $k2Id){
	  self::importIntoJlexObj($itemId, $k2Id);
  }

	// delete jlex assoc data
	public static function recontent_jlex($assocData){
    // query turns id = key
    $id = $assocData->id;
    $context = $assocData->context;
    $tables = array();
    switch ($context) {
      case 'ja_migration.jlex_comment':
        $tables['#__jlexcomment'][] = $id;
        break;
      case 'ja_migration.jlex_obj':
        $tables['#__jlexcomment_obj'][] = $id;
        break;
      case 'ja_migration.jlex_media':
        $tables['#__jlexcomment_media'][] = $id;
        break;
	    case 'ja_migration.jlex_vote':
				$tables['#__jlexcomment_vote'][] = $id;
				break;
    }
    if (empty($tables)) return;
    foreach ($tables as $tbl => $_id) {
			$db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->delete($db->quoteName($tbl))
        ->where($db->quoteName('id') . ' IN (' . implode(',', $_id) . ')');
      try {
        $db->setQuery($query);
        if ($db->execute()) {
          echo '<pre style="color: green">';
          print_r("Removed content data in tbl: $tbl");
          echo '</pre>';
        }
      } catch (RuntimeException $e) {
        echo '<pre>' . var_dump($e->getMessage()) . '</pre>';
        die();
      }
    }
  }
	
	public static function importIntoJlexObj($itemId, $k2Id){
		$jlexObj = self::getK2JlexObjData($k2Id);
		if (empty($jlexObj)) return;
		// 1st: insert into _jlexcomment_obj
		$db = JFactory::getDBO();
		$query = $db->getQuery(True);
		$query->insert($db->quoteName('#__jlexcomment_obj'))
			->columns(array(
				$db->quoteName('id'),
				$db->quoteName('title'),
				$db->quoteName('com_name'),
				$db->quoteName('com_key'),
				$db->quoteName('com_id'),
				$db->quoteName('created_by'),
				$db->quoteName('created_time'),
				$db->quoteName('cm_count'),
				$db->quoteName('cm_count_active'),
				$db->quoteName('cm_i_count'),
				$db->quoteName('cm_i_count_active'),
				$db->quoteName('url'),
				$db->quoteName('published'),
				$db->quoteName('params'),
				$db->quoteName('latest_update'),
			))
			->values(
				'NULL , ' .
				$db->quote($jlexObj->title) . ', ' .
				$db->quote('com_content') . ' , ' .
				'"' .$itemId . '", ' .
				'"' .$itemId. '", ' .
				$db->quote($jlexObj->created_by) . ' , ' .
				'"' .date($jlexObj->created_time) . '", ' .
				'"' .$jlexObj->cm_count . '", ' .
				'"' .$jlexObj->cm_count_active . '", ' .
				'"' .$jlexObj->cm_i_count . '", ' .
				'"' .$jlexObj->cm_i_count_active . '", ' .
				'"' .$jlexObj->url . '", ' .
				'"' .$jlexObj->published . '", ' .
				$db->quote($jlexObj->params??'') . ' , ' .
				$db->quote($jlexObj->latest_update)
			);
		try{
			$db->setQuery($query);
			if ($db->execute()){
				$jlexContentId = $db->insertid();
				$migrator = new JADataMigrator();
				$migrator->addAssociation($jlexObj->id, 'jlex_obj', $jlexContentId);
				// 2nd: insert into _jlexcomment
				self::importIntoJlexComment($k2Id, $jlexContentId);
//				$db->freeResult();
//				echo '<pre style="color: green">';print_r("jlexObj: $itemId: $jlexObj->title");echo '</pre>';
				return $jlexContentId;
			}
		}catch (RuntimeException $e){
			echo '<pre>'. var_dump($e->getMessage()) .'</pre>';die();
		}
	}

	public static function importIntoJlexComment($k2Id, $jlexObjId){
		$jlexComments = self::getK2JlexComment($k2Id);
		if (empty($jlexComments)) return;
    $count = 0;
		foreach ($jlexComments as $key => $comment){
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__jlexcomment'))
				->columns(array(
					$db->quoteName('id'),
					$db->quoteName('obj_id'),
					$db->quoteName('comment'),
					$db->quoteName('guest_name'),
					$db->quoteName('guest_email'),
					$db->quoteName('parent_id'),
					$db->quoteName('root_parent_id'),
					$db->quoteName('child_count'),
					$db->quoteName('child_count_active'),
					$db->quoteName('up_point'),
					$db->quoteName('down_point'),
					$db->quoteName('report_count'),
					$db->quoteName('created_by'),
					$db->quoteName('created_time'),
					$db->quoteName('modified_by'),
					$db->quoteName('modified_time'),
					$db->quoteName('published'),
					$db->quoteName('featured'),
					$db->quoteName('sent'),
					$db->quoteName('language'),
					$db->quoteName('ip_address'),
					$db->quoteName('sticker_id'),
					$db->quoteName('params'),
					$db->quoteName('reaction_count'),
					$db->quoteName('reaction_data'),
					$db->quoteName('style_id'),
					$db->quoteName('giphy_id')
				))
				->values(
					'NULL ,' .
					'"' . $jlexObjId . '", '.
					$db->quote($comment->comment) . ',' .
					$db->quote($comment->guest_name??'') . ',' .
					$db->quote($comment->guest_email??'') . ',' .
					'"' .$comment->parent_id . '",' .
					'"' .$comment->root_parent_id . '",' .
					'"' .$comment->child_count . '",' .
					'"' .$comment->child_count_active . '",' .
					'"' .$comment->up_point . '",' .
					'"' .$comment->down_point . '",' .
					'"' .$comment->report_count . '",' .
					'"' .$comment->created_by . '",' .
					'"'.date($comment->created_time). '",' .
					'"' .$comment->modified_by . '",' .
					'"'.date($comment->modified_time). '",' .
					'"' .$comment->published . '",' .
					'"' .$comment->featured . '",' .
					'"' .$comment->sent . '",' .
					$db->quote($comment->language) . ',' .
					$db->quote($comment->ip_address) . ',' .
					'"' .$comment->sticker_id . '",' .
					$db->quote($comment->params??'') . ',' .
					'"' .$comment->reaction_count . '",' .
					$db->quote($comment->reaction_data) . ',' .
					'"' .$comment->style_id . '",' .
					$db->quote($comment->giphy_id)
				);
			try{
				$db->setQuery($query);
				if ($db->execute()){
          $count++;
					$jlexCommentContentId = $db->insertid();
					$migrator = new JADataMigrator();
					$migrator->addAssociation($comment->id, 'jlex_comment', $jlexCommentContentId);
					// 3rd: insert into _jlexcomment_media
          self::importIntoJlexMedia($comment->id, $jlexCommentContentId);
          // 4th: insert into _jlexcomment_vote
          self::importIntoJlexVote($comment->id, $jlexCommentContentId);
//           $db->freeResult();
//					echo '<pre style="color: green">';print_r("jlexComment: obj_id: $jlexObjId");echo '</pre>';
				}
			}catch (RuntimeException $e){
				echo '<pre>'. var_dump($e->getMessage()) .'</pre>';die();
			}
		}
    if ($count > 0){
      $migrator::printr(JText::sprintf('JA_K2TOCONTENT_TO_JLEXCOMMENT_DONE', $count));
    }else{
      $migrator::printr(JText::_('JA_K2TOCONTENT_TO_JLEXCOMMENT_ERROR'));
    }
	}

	public static function importIntoJlexMedia($commentId, $jlexCommentContentId){
		$medias = self::getK2JlexCommentMedia($commentId);
		if (empty($medias)) return;
		$count = 0;
		foreach ($medias as $key => $media){
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__jlexcomment_media'))
				->columns(array(
					$db->quoteName('id'),
					$db->quoteName('comment_id'),
					$db->quoteName('name'),
					$db->quoteName('description'),
					$db->quoteName('created'),
					$db->quoteName('created_by'),
					$db->quoteName('path'),
					$db->quoteName('fileSize'),
					$db->quoteName('fileName'),
					$db->quoteName('fileType'),
				))
				->values(
					'NULL ,' .
					'"' . $jlexCommentContentId . '", ' .
					$db->quote($media->name) . ', ' .
					$db->quote($media->description) . ', ' .
					'"' . date($media->created) . '",' .
					$db->quote($media->created_by) .','.
					$db->quote($media->path) . ',' .
					$db->quote($media->fileSize) . ','.
					$db->quote($media->fileName) . ','.
					$db->quote($media->fileType)
				);
			try{
				$db->setQuery($query);
				if ($db->execute()){
					$count++;
//					echo '<pre style="color: green">';print_r("jlexMedia: new_id: " . $db->insertid());echo '</pre>';
					$migrator = new JADataMigrator();
					$migrator->addAssociation($media->id, 'jlex_media', $db->insertid());
//					 $db->freeResult();
				}
			}catch (RuntimeException $e){
				echo '<pre>'. var_dump($e->getMessage()) .'</pre>';die();
			}
		}
		if ($count > 0){
			$migrator::printr(JText::sprintf('JA_K2TOCONTENT_TO_JLEXMEDIA_DONE', $count));
		}else{
			$migrator::printr(JText::_('JA_K2TOCONTENT_TO_JLEXMEDIA_ERROR'));
		}
	}
	
	public static function importIntoJlexVote($commentId, $jlexCommentContentId){
		$votes = self::getK2JlexCommentVote($commentId);
		if (empty($votes)) return;
		$count = 0;
		foreach ($votes as $key => $vote){
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__jlexcomment_vote'))
				->columns(array(
					$db->quoteName('id'),
					$db->quoteName('comment_id'),
					$db->quoteName('point'),
					$db->quoteName('created_by'),
					$db->quoteName('created_time'),
					$db->quoteName('ip_address'),
					$db->quoteName('change_times'),
				))
				->values(
					'NULL ,' .
					$db->quote($jlexCommentContentId) . ', ' .
					$db->quote($vote->point) . ', ' .
					$db->quote($vote->created_by) . ', ' .
					$db->quote(date($vote->created_time)) . ', ' .
					$db->quote($vote->ip_address) . ', ' .
					$db->quote($vote->change_times)
				);
			try{
				$db->setQuery($query);
				if ($db->execute()){
					$count++;
//					echo '<pre style="color: lightseagreen">';print_r("jlexVote: " . $db->insertid());echo '</pre>';
					$migrator = new JADataMigrator();
					$migrator->addAssociation($vote->id, 'jlex_vote', $db->insertid());
				}
			}catch (RuntimeException $e){
				echo '<pre>'. var_dump($e->getMessage()) .'</pre>';die();
			}
		}
		if ($count > 0){
			$migrator::printr(JText::sprintf('JA_K2TOCONTENT_TO_JLEXVOTE_DONE', $count));
		}else{
			$migrator::printr(JText::_('JA_K2TOCONTENT_TO_JLEXVOTE_ERROR'));
		}
	}

	public static function getK2JlexObjData($k2Id){
		$db = JFactory::getDBO();
		$query = $db->getQuery(True);
		$query->select('*')
			->from($db->quoteName('#__jlexcomment_obj'))
			->where("com_id = $k2Id");
		$db->setQuery($query);
		$result = $db->loadObject();
//		 $db->freeResult();
		return $result;
	}

	public static function getK2JlexComment($k2Id){
		$db = JFactory::getDBO();
		$query = $db->getQuery(True);
		$query->select('cm.*')
			->from($db->quoteName('#__jlexcomment_obj', 'obj'))
			->join('INNER', $db->quoteName('#__jlexcomment', 'cm') . ' ON ' . ('obj.id=cm.obj_id'))
			->where("obj.com_id=" . (int) $k2Id);
		$db->setQuery($query);
		$result = $db->loadObjectList();
//		 $db->freeResult();
		return $result;
	}

	public static function getK2JlexCommentMedia($commentId){
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__jlexcomment_media'))
			->where('comment_id=' . $commentId);
		$db->setQuery($query);
		$result = $db->loadObjectList();
//		 $db->freeResult();
		return $result;
	}
	
	public static function getK2JlexCommentVote($commentId){
		$db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select('*')
      ->from($db->quoteName('#__jlexcomment_vote', 'cv'))
      ->where('cv.comment_id=' .$commentId);
    $db->setQuery($query);
    $result = $db->loadObjectList();
//     $db->freeResult();
    return $result;
  }
}

?>