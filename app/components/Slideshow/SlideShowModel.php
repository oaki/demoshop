<?php

class SlideShowModel extends CacheModel // object je Nette\Object
{
    private $connection;
		
	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache( get_class() ) );
	}

	public function __construct(DibiConnection $connection, NCache $cache ){
		$this->connection = $connection;
		$this->cache = $cache;
	}

	public function getConnection(){
		return $this->connection;
	}
	
	function get($id_node){
		$a = dibi::fetch("SELECT * FROM [home] WHERE id_node=%i",$id_node);
		$a['files'] = $this->getFiles($id_node);
		
		return $a;
	}
       
	public function getFiles($id_node){
		$files = FilesNode::getAllFiles( 'Home', $id_node);
		
		foreach($files as $k=>$l){
			$i = dibi::fetch("SELECT title, link, alt, link_name FROM [promo_text] WHERE id_file = %i",$l['id_file']);
			$files[$k]['title'] = $i['title'];
			$files[$k]['link'] = $i['link'];
			$files[$k]['alt'] = $i['alt'];
			$files[$k]['link_name'] = $i['link_name'];
		}		
		
		return $files;
    }

}