<?

class PageModel extends CacheModel
{
        private $connection;



		public static function init(){
			return new self( dibi::getConnection(), NEnvironment::getCache( get_class() ) );
		}

        public function __construct(DibiConnection $connection, NCache $cache ){
			
            $this->connection = $connection;
			$this->cache = $cache;
//			NDebug::dump($this->cache);	
        }

        public function getConnection(){
                return $this->connection;
        }

       
	
        public function find(array $criteria, $limit = 0){
                $key = serialize($criteria).'limit='.$limit;
                if ( $list = $this->loadCache($key) ) {
                        return $list;
                }

                $query = $this->connection->select('*')->from('menu_item')->where('%and', $criteria);
				
                if ($limit) {                        
                        if ($limit === 1) {
                                return $this->saveCache($key, $query->fetch());
                        }
                }

				return $this->saveCache($key, $query->fetchAll());
                
        }

        public function findOne(array $criteria)
        {
                return $this->find($criteria, 1);
        }


//        public function getMenu()
//        {
//                if (!isset($this->cache['menu'])) {
//                        $this->cache['menu'] = $this->connection
//                                ->select('*')
//                                ->from('stranky')
//                                ->orderBy(array('order' => dibi::ASC))
//                                ->fetchPairs();
//                }
//
//                return $this->cache['menu'];
//        }
		
		
	public function getAssoc( $id_lang )
	{
		$key = 'getAssoc('.$id_lang.')';
		$tree = $this->loadCache( $key );
		
		if( $tree ){
			return $tree;
		}else{
			$tree = $this->connection
				->select('*')
				->from('menu_item')
				->orderBy(array('sequence' => dibi::ASC))
				->fetchAssoc('parent,id_menu_item');
		}
		return $this->saveCache($key, $tree);		
	}
		
	public function getParent($id_menu_item){
		$key = 'getParent('.$id_menu_item.')';
		
		if( $parent = $this->loadCache( $key ) ){
			return $parent;
		}else{
			$all_item = $this->connection->select('*')->from('menu_item')->fetchAssoc('id_menu_item');
		
			$parent = array();
			$actual_id_menu_item = $id_menu_item;
			if(!isset($all_item[ $actual_id_menu_item ]))
				return $parent;

			for($i=0; $i < 10; ++$i ){
				$parent[] = $all_item[ $actual_id_menu_item ];
				if( $all_item[ $actual_id_menu_item ]['parent'] == 0 )
					break;

				$actual_id_menu_item = $all_item[ $actual_id_menu_item ]['parent'];
			}


			$parent = array_reverse($parent);
		}
		
		return $this->saveCache($key, $parent);
		
	}	
		
		
	public function slugToId($slug){		
		$slug = rtrim($slug, '/');
		$key = 'slugToId('.$slug.')';
		
//		echo $slug;exit;
		$id = $this->loadCache( $key );
		
		if( $id ){
			return $id;
		}else{
			$id = dibi::fetchSingle("SELECT id_menu_item FROM [menu_item] WHERE url_identifier LIKE %s",$slug);
			if(!$id) $id = NULL;			
		}
		
		return $this->saveCache($key, $id);
	}
	
	public function idToSlug($id){	
		
		$key = 'idToSlug('.$id.')';
		
		$slug = $this->loadCache( $key );
//		echo $slug;exit;
		if( $slug ){
			return $slug;
		}else{
			$name = dibi::fetchSingle("SELECT url_identifier FROM [menu_item] WHERE id_menu_item = %i",$id);
			if(!$name) $name = NULL;
		}
		
		return $this->saveCache($key, $name);
		
	}
	
	
	

}