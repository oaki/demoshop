<?php

/**
 * Description of OrderPresenter
 *
 * @author oaki
 */
class Front_OrderPresenter extends Front_BasePresenter {

//	public function renderDefault() {
//	    if(!NEnvironment::getUser()->isLoggedIn()){
//		$this->redirect('Cart:default');
//	    }
//	    $this->template->user = NEnvironment::getUser()->getIdentity()->data;
//
//	    $this->saveOrder();
//	}

//	private function saveOrder(){
//
//	    $user = NEnvironment::getUser()->getIdentity()->data;
//
//	    $session = NEnvironment::getSession('cart');
//	    $arr = array(
//		'name'=>$user['name'],
//		'id_auth_user'=>$user['id_auth_user'],
//		'surname'=>$user['surname'],
//		'address'=>$user['address'],
//		'city'=>$user['city'],
//		'zip'=>$user['zip'],
//		'phone'=>$user['phone'],
//		'fax'=>$user['fax'],
//		'ico'=>$user['ico'],
//		'paying_vat'=>$user['paying_vat'],
//		'dic'=>$user['dic'],
//		'company_name'=>$user['company_name'],
//		'iso'=>$user['iso'],
//		'title'=>$user['title'],
//		'delivery_address'=>$session->delivery_address['address'],
//		'delivery_city'=>$session->delivery_address['city'],
//		'delivery_zip'=>$session->delivery_address['zip'],
//		'delivery_phone'=>$session->delivery_address['phone'],
//		'delivery_name'=>$session->delivery_address['name'],
//		'delivery_surname'=>$session->delivery_address['surname'],
//		'delivery_fax'=>$session->delivery_address['fax'],
//		'delivery_company_name'=>$session->delivery_address['company_name'],
//		'delivery_paying_vat'=>$session->delivery_address['paying_vat'],
//	    );
//
//	    dibi::query("INSERT INTO [order]",$arr);
//
//	    $id_order = dibi::insertId();
//
//
//	    //save product
//	    foreach($session->products as $id_product=>$count){
//		$product = ProductModel::get($id_product,$this->id_lang );
//
//		$ap = array(
//		    'name'=>$product['name'],
//		    'id_order'=>$id_order,
//		    'id_product'=>$product['id_product'],
//		    'ean13'=>$product['ean13'],
//		    'price'=>$product['price'],
//		    'count'=>$count,
//		);
//
//		dibi::query("INSERT INTO [order_product]",$ap);
//	    }
//	}
	
        
}