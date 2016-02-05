<?php

class CompareProductModel extends CacheModel
{
        private $session;

		public static function init(){
			return new self( NEnvironment::getSession( get_class() ) );
		}

        public function __construct(NSessionSection $session){			
            $this->session = $session;
			if(!isset($this->session->products)){
				$this->session->products = array();
			}			
        }

        public function getSession(){
                return $this->session;
        }

		function insert($id){
			$this->session->products[$id] = $id;			
		}
		
		function delete($id){
			unset($this->session->products[$id]);
		}
       
		function fetchAll(){
			return $this->session->products;
		}
	

}