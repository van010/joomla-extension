<?php
/**
 * ------------------------------------------------------------------------
 * JA Slideshow Module for J25 & J31
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die( 'Restricted access' );

class images{   

	public $app = null;

	public function __construct(){
		$this->app = JFactory::getApplication()->input;
	}


    /**
	* Load images from folder and match them
	*
	*/
    public function loadImages(&$params) { 
		$folder = $this->app->get('path', '', 'RAW');
		$images = $this->getListImages($folder, $params);
		
		return $images;
    }
	
	/**
     *
     * Get all image from image source and render them
     * @param object $params
     * @return array image list
     */
    function getListImages($folder='', $params)
    {	
		if(!$folder){
			return null;
		}   
		$params = (array)$params;
		$orderby = $this->app->get('orderby', 0);
		$sort = $this->app->get('sortby', 0);
        $images = $this->readDirectory($folder, $orderby, $sort);
        $data = array();
		$data['success'] = false;	
		$data['images'] = array();
		
		if(empty($images)){
			return $data;
		}
		$i = 0 ;	
	
        foreach ($images as $k => $img) { 
        	$data['images'][$i] = new stdClass();     
			$data['images'][$i]->image = $img;						
			$data['images'][$i]->imageSrc = JURI::root() . $folder . $img;			
			$data['images'][$i]->title = '';			
            $data['images'][$i]->link = '';
			$data['images'][$i]->description = '';
			$data['images'][$i]->show = true;	
			
			$i++ ;
        }
		$data['success'] = true;
        return $data;
    }	
	
	/**
     *
     * Get all image from resource
     * @param string $folder folder path
     * @param string $orderby
     * @param string $sort
     * @return array images
     */
    function readDirectory($folder, $orderby, $sort)
    {
        $imagePath = JPATH_SITE . "/" . $folder;
        $imgFiles = JFolder::files($imagePath);
		
        $folderPath = $folder;
		$imageFiles = array();
        $images = array();
        $i = 0;
		if (empty($imgFiles)){
			return $images;
		}
        foreach ($imgFiles as $file) {		
            if (preg_match("/\.(bmp|gif|jpg|png|jpeg)$/i", $file) && is_file($imagePath.'/'.$file)) {
                $imageFiles[$i][0] = $file; 
				$imageFiles[$i][1] = filemtime($imagePath.'/'.$file);				
                $i++;
            }
        }  
		$images = $this->sortImage($imageFiles, $orderby, $sort);
        return $images;
    }
	
	/**
	 *
	 * Sort images
	 * @param array $image
	 * @param string $orderby
	 * @param string $sort
	 * @return array image that is sorted
	 */
	function sortImage($image, $orderby, $sort)
	{
		$sortObj = array();
		$imageName = array();
		
		if ($orderby == 1) {
			for ($i = 0; $i < count($image); $i++) {
				$sortObj[$i] = $image[$i][1];
				$imageName[$i] = $image[$i][0];
			}
		} else {
			for ($i = 0; $i < count($image); $i++) {
				$sortObj[$i] = $image[$i][0];
			}
			$imageName = $sortObj;
		}
		if ($sort == 1)
			array_multisort($sortObj, SORT_ASC, $imageName);
		elseif ($sort == 2)
			array_multisort($sortObj, SORT_DESC, $imageName);
		else
			shuffle($imageName);
		return $imageName;
	}
	
	/**
	* Check data for edit 
	*
	*/
    public function validData() {
		$img = new stdClass;
		$data = trim($this->app->get('data', ''));		
		$imgName = trim($this->app->get('imgname', ''));
		if(!empty($data)){
			$check = 0; // data for image: 1 existed, 0 empty
			$data = json_decode($data);			
			foreach($data as $key=>$v){
				if($v->image == $imgName){					
					$img->image 					 = 	$imgName;
					$img->title 					 = 	isset($v->title)?$v->title:'';
					$img->link 						 = 	isset($v->link)?$v->link:'';
					$img->description 				 = 	isset($v->description)?$v->description:'';
					$img->show						 =	isset($v->show)?$v->show:true;	
					$check = 1;
					break;		
				}
			}
			if(!$check){
				$img->image = '';
				$img->title = '';
				$img->link = '';
				$img->description = '';				
				$img->show 	= false;	
			}
		}else{		
			$img->image = '';
			$img->title = '';
			$img->link = '';
			$img->description = '';			
			$img->show = false;		
		}
		
		return $img;
    }
	
	/**
	* Update data of images param
	*
	*/
    public function updateData() { 		
		$data = trim($this->app->get('data', ''));
		$title = $this->app->get('title', '');
		$link = $this->app->get('link', '');
		$description = $this->app->get('description', '', 'POST', 'STRING', JREQUEST_ALLOWRAW);		
		$imgName = trim($this->app->get('imgname', ''));
		$show = trim($this->app->get('show',true));
		if($imgName==''){
			if(!$data==''){
				$data = array();
			}else{
				$data = json_decode($data);
			}
			return $data;
		}
		//update data param			
		if(!empty($data) && !$data ==''){
			$action = 0; // 1 is update, 0 is add new			
			$data = json_decode($data);			
			foreach($data as $key=>$v){
				if($v->image == $imgName){					
					$data[$key]->image = $imgName;
					$data[$key]->title = $title;
					$data[$key]->link = $link;
					$data[$key]->description = $description;					
					$data[$key]->show = $show;			
					$action = 1;
					break;		
				}
			}
			if(!$action){
				$count = count($data);
				$data[$count]->image = $imgName;
				$data[$count]->title = $title;
				$data[$count]->link = $link;
				$data[$count]->description = $description;				
				$data[$count]->show = $show;	
			}
		}else{
			$data = array();
			$data[0] = new stdClass();
			$data[0]->image = $imgName;
			$data[0]->title = $title;
			$data[0]->link = $link;
			$data[0]->description = $description;			
			$data[0]->show = $show;
		}
		return $data;
    }
    
}