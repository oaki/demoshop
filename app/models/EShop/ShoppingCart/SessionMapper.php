<?php

namespace ShoppingCart;

class SessionMapper extends \NObject implements IMapper
{

    private $session;



    public function __construct(\NSessionSection $session)
    {
        $this->session = $session;
    }

    public function save(Item $item){
		
        if (isset($this->session[$item->getId()])) {
				$this->session[$item->getId()] = $item;
				return;
		}

		$this->session[$item->getId()] = $item;
    }
	
	
//    public function add(Item $item){
//		
//        if (isset($this->session[$item->getId()])) {
//				
//				$this->session[$item->getId()]->count = $this->session[$item->getId()]->count + $item->count;
//				return;
//		}
//
//		$this->session[$item->getId()] = $item;
//    }

	
	public function delete(Item $item){
		unset($this->session[$item->getId()]);
	}
	
	public function deleteAll(){
		foreach($this->session as $id=>$item){
			unset($this->session[$id]);
		}
		
	}

	public function load( $data){
		
		$item = new Item;  
        
        foreach ($data as $prop => $val) {
			if(isset($item->$prop))
				$item->$prop = $val;
        }

		return $item;
	}
	
	public function fetchAll(){
		$items = array();
        foreach($this->session as $id=>$item){
			
			
			$items[ $id ] = $item;
		}
		
		return $items;
    }
	
	
		
    public function find($id)
    {
        if(isset($this->session[$id]))
			return $this->session[$id];
		return false;
    }
	
    public function findBy(array $values)
    {
       throw new \Exception('not implemented');
    }
	
     function findOneBy(array $values)
    {
       throw new \Exception('not implemented');
    }

	
//	 private function load($data)
//    {
//        $item = new Item;
//        
//        unset($data['id']);
//        foreach ($data as $prop => $val) {
//            $item->$prop = $val;
//        }
//
//        return $item;
//    }


}