<?php
class Front_BlogPresenter extends Front_ListPresenter{

	function beforeRender(){
		parent::beforeRender();
		$this['header']['css']->addFile('blog.css');
	}

	function renderDefault(){
		$this->template->blog_box = dibi::fetchAll("SELECT * FROM menu_item WHERE parent = %i",dibi::fetch("SELECT * FROM menu_item WHERE url_identifier = 'blog'")->id_menu_item);
		foreach ($this->template->blog_box as $box) {
			$box['data'] = dibi::fetchAll("
				SELECT * FROM node 
				LEFT JOIN article USING (id_node)
				WHERE 
				id_menu_item = %i",$box->id_menu_item,"
				GROUP BY id_node
				ORDER BY id_node DESC
				LIMIT 0, 5
			");
			foreach ($box['data'] as $article) {
				$article['url'] = $this->getPresenter()->link('Blog:current', array('categories'=> $box->url_identifier, 'url_identifier'=>$article->url_identifier));
				if ($image = FilesNode::getOneFirstFile('article', $article->id_node))
					$article['image_url'] = Files::gURL($image->src, $image->ext, 220, 160, 6);
			}
		}
		
	}

	function renderList($categories){
		$this->template->blog_box = dibi::fetchAll("SELECT * FROM `menu_item` 
LEFT JOIN node USING (id_menu_item)
LEFT JOIN article USING (id_node)
WHERE 
menu_item.url_identifier = %s",$categories,"
ORDER BY id_node DESC
");
		foreach ($this->template->blog_box as $article) {
			$article['url'] = $this->getPresenter()->link('Blog:current', array('categories'=> $categories, 'url_identifier'=>$article->url_identifier));
			if ($image = FilesNode::getOneFirstFile('article', $article->id_node))
				$article['image_url'] = Files::gURL($image->src, $image->ext, 220, 160, 6);
		}

		$session = NEnvironment::getSession("Front_List");
		$session['back_url'] = $_SERVER['REQUEST_URI'];

	}


}
