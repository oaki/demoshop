<?php

class Promo extends FilesNode{

	public function render(){

		$template = new NFileTemplate();
		$template->registerFilter ( new NLatteFilter() );
		$template->setFile(dirname(__FILE__).'/promo.phtml');
		$template->id = 'Multiupload_'.$this->type_module;
//		$template->css = file_get_contents(dirname(__FILE__).'/fileuploader.css');

//		$template->js = file_get_contents(dirname(__FILE__).'/fileuploader.js');

		if( _NETTE_MODE ){
		    $template->action = NEnvironment::getApplication()->getPresenter()->link('Homepage:upload');
		}else{
		    $template->action = '/admin.php';
		}


		$template->parsed_url = $this->parsed_url;
		$template->list = self::getAllFiles($this->type_module, $this->id_module, $this->type);

		foreach($template->list as $k=>$l){
			$i = dibi::fetch("SELECT title, link, alt, link_name FROM [promo_text] WHERE id_file = %i",$l['id_file']);
			$template->list[$k]['title'] = $i['title'];
			$template->list[$k]['link'] = $i['link'];
			$template->list[$k]['alt'] = $i['alt'];
			$template->list[$k]['link_name'] = $i['link_name'];
		}
		return $template;
	}

	function  action() {
		parent::action();
		if(isset($_GET['ajax_save_promo']) AND $_GET['ajax_save_promo'] == 1){
			
			$arr = Tools::getValuesForTable('promo_text', $_POST);
			
			//zisti ci existuje
			$id_file = dibi::fetchSingle("SELECT id_file FROM [promo_text] WHERE id_file = %i",$_POST['id_file']);
//			
//			$arr = array(
//				'title'=>$_POST['title'],
//				'alt'=>$_POST['alt'],
//				'link'=>$_POST['link'],
//				'link_name'=>$_POST['link_name'],
//			);

			if($id_file){
				dibi::query("UPDATE promo_text SET ", $arr,"WHERE id_file = %i",$id_file);
			}else{
				$arr['id_file'] = $_POST['id_file'];
				dibi::query("INSERT INTO promo_text ", $arr);
			}
			exit;
		}
	}
	
}
