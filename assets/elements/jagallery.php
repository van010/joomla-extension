<?php
/**
 * $JA#COPYRIGHT$
 */
 
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.formfield');


class JFormFieldJagallery extends JFormField {
    protected $type = 'Jagallery';
    
    protected function getInput() {
		if (!version_compare(JVERSION, '4', 'ge')){
			JHtml::_('behavior.modal');
		}		
		Jhtml::_('stylesheet', JURI::root() . 'modules/' . $this->form->getValue('module') . '/assets/elements/jagallery/style.css');
	
		$jaGalleryId = $this->id ;
		$params = $this->form->getValue('params');
		if(!isset($params)){
			$params = new stdClass();
			$params->folder = '';
		}
		if(!isset($params->folder)){
			$params->folder = '';
		}else{
			if(substr(trim($params->folder), -1) != '/'){
				$params->folder = trim($params->folder) . '/';
			}
		}
		//Check data format && convert it to json data if it is older format		
		$updateFormatData = 0;
		
		if($this->value && ! $this->isJson($this->value)){
			$this->value = $this->convertFormatData($this->value);
			if(isset($this->element["updatedata"]) && $this->element["updatedata"]){
				$updateFormatData = 1;
			}
		}		
		//Create element
		$button = '<input type="button" id="jaGetImages" value="'.JText::_("JA_GET_IMAGES").'" style="display: none;" /><br /><div id="listImages" style="width: 100%; overflow: hidden;"></div>';
		$button .= '<textarea style="width: 75%; display: none;" rows="6" cols="75" name="' . $this->name . '" id="' . $jaGalleryId . '" >'. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') .'</textarea>';
		
		$curr_gallery = addslashes($this->value);
		$curr_folder = $params->folder;
		$folder_require = JText::_("FOLDER_PATH_REQUIRED");
		$folder_empty = JText::_('FOLDER_EMPTY');
		$text_show = JText::_('JSHOW');
		$text_edit = JText::_('EDIT');
		$text_title = JText::_('TITLE');
		$text_link = JText::_('LINK');
		$text_update = JText::_('UPDATE');
		$text_cancel = JText::_('CANCEL');
		$text_desc = JText::_('DESCRIPTION');
		$path_loading_gif = JURI::root().'modules/mod_jaslideshow/assets/elements/jagallery/loading.gif';


		echo "<script>
			const options = {
				curr_gallery: '$curr_gallery',
				curr_folder: '$curr_folder',
				auto_update: $updateFormatData,
				folder_require: '$folder_require',
				folder_empty: '$folder_empty',
				text_show: '$text_show',
				text_edit: '$text_edit',
				text_title: '$text_title',
				text_link: '$text_link',
				text_update: '$text_update',
				text_cancel: '$text_cancel',
				text_desc: '$text_desc',
				path_gif_loading: '$path_loading_gif',
				gallery_id: '$jaGalleryId',
			};
		</script>";
		echo '<script src="'.JURI::root().'modules/mod_jaslideshow/assets/script/jagallery.js"></script>';
		return $button;
    }

	/*
	* Check data format for update data type from old version to json format
	* @string data string 
	* @return boolean
	*/
	function isJson($string) 
	{
		return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
	}	
    
	
	function convertFormatData($string)
	{
		$data = array();
		$description = $this->parseDescNew($string);
		
		if(!empty($description)){
			$i = 0;
			foreach($description as $key=>$v){
				$data[$i]								= new stdClass();
				$data[$i]->image 						= $key;
				$data[$i]->title 						= "";
				$data[$i]->link 						= isset($v["url"])?$v["url"]:'';
				$data[$i]->description 	    			= str_replace(array("\n","\r"),"<br />",$v["caption"]);
				$data[$i]->show							= isset($v['show'])?$v['show']:'';
				$i++;			
			}
		}
		if(!empty($data)){
			return json_encode($data);
		}
		return '';
	}
	
	/**
     *
     * Parse description
     * @param string $description
     * @return array
     */
    function parseDescNew($description)
    {

        $regex = '#\[desc ([^\]]*)\]([^\[]*)\[/desc\]#m';
        $description = str_replace(array("{{", "}}"), array("<", ">"), $description);
        preg_match_all($regex, $description, $matches, PREG_SET_ORDER);

        $descriptionArray = array();
        foreach ($matches as $match) {
            $params = $this->parseParams($match[1]);
            if (is_array($params)) {
                $img = isset($params['img']) ? trim($params['img']) : '';
                if (!$img)
                    continue;
                $url = isset($params['url']) ? trim($params['url']) : '';
                $target = isset($params['target']) ? trim($params['target']) : '';
                $show = isset($params['show']) ? trim($params['show']) : '';
                $descriptionArray[$img] = array('url' => $url, 'caption' => str_replace("\n", "<br />", trim($match[2])), 'target' => $target);
            }
        }

        return $descriptionArray;
    }
	
	/**
     * get parameters from configuration string.
     *
     * @param string $string;
     * @return array.
     */
    function parseParams($string)
    {
        $string = html_entity_decode($string, ENT_QUOTES);
        $regex = "/\s*([^=\s]+)\s*=\s*('([^']*)'|\"([^\"]*)\"|([^\s]*))/";
        $params = null;
        if (preg_match_all($regex, $string, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $key = $matches[1][$i];
                $value = $matches[3][$i] ? $matches[3][$i] : ($matches[4][$i] ? $matches[4][$i] : $matches[5][$i]);
                $params[$key] = $value;
            }
        }
        return $params;
    }
	
}