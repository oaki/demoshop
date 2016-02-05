<?php

namespace ShoppingCart;

class DibiMapper extends \NObject implements IMapper
{

    private $conn;



    public function __construct(\DibiConnection $conn)
    {
        $this->conn = $conn;
    }



    public function save(Item $item, $count)
    {
		$count = (int)$count;
		
        if ($item->getId() === NULL) { // insert
            $data = $this->itemToData($item); // vytáhne data z entity a vrátí jako pole
            $id = $this->conn->insert('shopping_cart', $data)->execute();
            $this->setIdentity($item, $id);

        } else { // update
            $data = $this->itemToData($item); // vytáhne data z entity a vrátí jako pole
            // tady se velice hodí logika, která porovná v jakém stavu byla entita při načtení
            // a v jakém je teď, aby se nemuselo posílat všechno, ale to jsou hodně pokročílé funkce
            // a optimalizace se má dělat až když je potřeba, že :)

            $this->conn->update('shopping_cart', $data)
                ->where('id = %i', $item->getId())->execute();
        }
    }



    public function find($id)
    {
        $data = $this->conn->select('*')->from('shopping_cart')->where('id = %i', $id)->fetch();
        return $this->load($data);
    }
	
    public function findBy(array $values)
    {
       throw new \Exception('not implemented');
    }
	
     function findOneBy(array $values)
    {
       throw new \Exception('not implemented');
    }



    public function findAll()
    {
        return $this->conn->select('*')->from('shopping_cart')->fetchAssoc('id');
    }



    private function load($data)
    {
        $item = new ShoppingCartItem;
        $this->setIdentity($item, $data->id);

        unset($data['id']);
        foreach ($data as $prop => $val) {
            $item->$prop = $val;
        }

        return $item;
    }



    private function setIdentity($item, $id)
    {
        $ref = Nette\Reflection\ClassReflection($item);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(TRUE);
        $idProp->setValue($item, $id);

        return $item;
    }

}