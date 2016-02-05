<?php

namespace ShoppingCart;

class Repository extends \NObject
{
    private $mapper;

    public function __construct(IMapper $mapper)
    {
        $this->mapper = $mapper;
    }
    
	static public function init()
    {
        return new self(new SessionMapper(\NEnvironment::getSession('cart')));
    }

	public function getMapper(){
		return $this->mapper;
	}

    public function add(Item $item)
    {
		//ak uz existuje, pripocita pocet
		$old_item = $this->mapper->find($item->getId());
		
		if($old_item){
			$count = $old_item->count + $count;
			
		}
		
        $this->mapper->save($item, $count);
		
        return $item;
    }
	
    public function save(Item $item)
    {
        $this->mapper->save($item, $count);
        return $item;
    }
	
	public function fetchAll(){
		return $this->mapper->fetchAll();
	}
	
	public function deleteAll(){
		return $this->mapper->deleteAll();
	}


	public function find($id)
    {
        return $this->mapper->find($id);
    }


    // metody jako findByName patří spíše sem
    public function findByName($name)
    {
        return $this->mapper->findBy(array(
            'name' => $name
        ));
    }
	
	public function loadProductToItem($product, Item $item ){
		$item->name = $product['name'];
		$item->price = $product['price_array']['price'];
		$item->count = 1;
		$item->total_sum = $product['price_array']['price'];
		return $item;
	}

    // další metody
}