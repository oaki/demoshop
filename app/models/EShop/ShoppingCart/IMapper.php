<?php

namespace ShoppingCart;

interface IMapper
{
    /** prida entitu*/
    function add(Item $entity);
	
    /** uložit entitu*/
    function save(Item $entity);

	/** vymaze entitu */
    function delete(Item $entity);

    /** najít entitu s ID */
    function find($id);

    /** předáš tomu pole hodnot, podle kterých má hledat. Vrátí entity co odpovídají */
    function findBy(array $values);

    /** předáš tomu pole hodnot, podle kterých má hledat. Vrátí jednu entitu */
    function findOneBy(array $values);

    /** vrátí všechno */
//    function findAll();

    // popř. si můžeš napsat další funkce, které budou umět nějaké velice specifické funkce
    // ale to spíše až v tom konkrétnějším mapperu
}
