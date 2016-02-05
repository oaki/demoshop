<?php

namespace ShoppingCart;

class Item
{
    public $id; // nastavovat v mapperu reflexí, aby "nešlo" změnit

    public $name; // jméno položky

    public $price; // kolik stojí
    
	public $total_sum; // kolik stojí dokopy
	
	public $count; // kolko ich je

    // .. další vlastnosti

    public function getId()
    {
        return $this->id;
    }
}