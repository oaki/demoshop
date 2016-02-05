<?php

/**
 * Description of CartPresenter
 *
 * @author oaki
 */
class Front_CartPresenter extends Front_BasePresenter {

    public $payment_method;

    function  __construct(IDIContainer $context) {
		parent::__construct($context);
		
		$this->payment_method = OrderModel::getPaymentMethod();
    }

	
	function renderDefault(){
		$session = NEnvironment::getSession('cart');
		$this->template->products = $session->products;		
	}

    public function actionSummary(){
		if(!NEnvironment::getUser()->isLoggedIn()){
			$this->redirect('Cart:default');
		}

		$session = NEnvironment::getSession('cart');

		if($session->delivery_address == NULL){
			$user = NEnvironment::getUser()->getIdentity()->data;
			$session->delivery_address = $user;
		}

		$this->template->s = $session;
    }
    
	
	/*
	 * Informacie o uzivatelovi + sposob platby
	 */
    public function actionStep2() {
		$session = NEnvironment::getSession('cart');

		if(empty($session->products)){
			$this->redirect('default');
		}

	}
	
	function saveUserInfo(NForm $form){
		$values = $form->getValues();

		$order_info = NEnvironment::getSession('user_order_info');
		$order_info['values'] = $values;		

		$this->redirect('step3');
	}

	
	/*
	 * Doprava a platba
	 */
	public function actionStep3() {
		
		$session = NEnvironment::getSession('cart');

		if(empty($session->products)){
			$this->redirect('default');
		}

		$this->template->delivery = $this->getService('Delivery')->fetchAll();
		$this->template->payment = $this->getService('Payment')->fetchAll(); 
		
		$order_info = NEnvironment::getSession('user_order_info');
		if( isset($order_info['values'] ) AND !empty($order_info['values'] )){
			$this['paymentAndDelivetyForm']->setDefaults( $order_info['values']);
		}
	}
	
	function handleSavePaymentAndDelivery(NForm $form){
		$values = $form->getValues();
		
		$order_info = NEnvironment::getSession('user_order_info');
		@$order_info['values']['id_delivery'] = $values['id_delivery'];
		@$order_info['values']['id_payment'] = $values['id_payment'];
		$this->redirect('step4');
	}
	
	function createComponentPaymentAndDelivetyForm($name) {
		$f = new MyForm;
		
		$f->addRadioList('id_delivery', 'Doprava', $this->getService('Delivery')->fetchPairs('name'))
				->addRule(NForm::FILLED,'Musíte vybrať dopravu');
		
		$f->addRadioList('id_payment', 'Platby', $this->getService('Payment')->fetchPairs('name'))
				->addRule(NForm::FILLED,'Musíte vybrať spôsob platby.');
		
		$f->addSubmit('btn','Pokračovať');
		$f->onSuccess[] = array($this,'handleSavePaymentAndDelivery');
		
		$f->setDefaults( array(
			'id_delivery'=>$this->getService('Delivery')->getDefault(),
			'id_payment'=>$this->getService('Payment')->getDefault(),
			)
		);
		
		return $f;
	}
	
	
	/*
	 * Sumar
	 */
	
	public function actionStep4() {
		
//		$template = $this->template;
//
//		$template->setFile( APP_DIR.'/FrontModule/templates/Order/OrderEmail.phtml' );
//
//		$template->discount = false;
//
//		$template->o = OrderModel::get(18);
//		dump($template->o);
//		$template->render();
//		exit;
		
		
		$session = NEnvironment::getSession('cart');

		if(empty($session->products)){
			$this->redirect('default');
		}

		$order_info = NEnvironment::getSession('user_order_info');
		
		$this->template->v = $order_info['values'];

		if( empty($order_info['values'])){
			$this->redirect('step2');
		}
		
		if( !isset($order_info['values']['id_delivery']) OR !isset($order_info['values']['id_payment'])){
			$this->redirect('step3');
		}
	}

	
	
	
	
    function  createComponent($name) {
	switch($name){
		 case 'fullOrderForm':
			 $form = $this->createComponent('userBaseForm');
			 unset( $form['login'], $form['password'], $form['passwordCheck']);

			 
//			 $form['login']->getElementPrototype()->setText('sss');
			 //platobne podmienky
			 $form->addGroup( '' );
//			 $form->addRadioList('payment_method', _('Spôsob platby'), $this->payment_method)
//					 ->addRule(NForm::FILLED, 'Musíte zvoliť spôsob platby');
//
////			 $form->addGroup('');
////			 $form->addText('discount_hash', _('Zľavový kupón (3%)'));
//
			 $form->addGroup('');
			 $form->addText('login', _('Email'))
					 ->addRule(NForm::FILLED,'Email musí byť vyplnený')
						 ->addRule(NForm::EMAIL, 'Email nie je v správnom tvare');
//
//			 //obchodne + button
//			 $form->addGroup();
//
			 $form->addTextarea('text', 'Poznámka k objednávke');
////			 $form->addCheckbox('need_available_to_48_hour', ' V prípade ak ste si objednali z ponuky "Tovar dostupný do 48 hod." prosím potvrdte, či chcete tovar dodať do 48 hod.');
////			  
//			 $conf = $this->context->parameters;
//			 
//			 $form->addCheckbox('terms_and_conditions',
//					 NHtml::el('span')
//						->setHtml(_('Obchodné podmienky '))
//							->add(
//								NHtml::el('a')
//									->href( $this->link(':Front:Article:default',array('id'=>$conf['CONDITIONS_CMS_ID'], 'id_menu_item'=>$conf['CONDITIONS_CMS_PAGE_ID']) ))
//											->setHtml("("._('viac').")")
//							)
//				)->addRule(NForm::FILLED, 'Je potrebné súhlasiť s obchodnými podmienkami.');



			$form->addSubmit('btn_user_form', _('Pokračovať') )
				 ->getControlPrototype()->class='classic-btn border-radius-2';

			
			 //ak je prihlaseny vyplnia sa polia
			 if( $this->user->isLoggedIn()){
				 $form->setDefaults( $this->user->getIdentity()->data);
			 }

			 //ak uz zadal informacie
			 $order_info = NEnvironment::getSession('user_order_info');
			 if( isset($order_info['values'] ) AND !empty($order_info['values'] )){
				 $form->setDefaults( $order_info['values']);
			 }

			 $form->onSuccess[] = array($this, 'saveUserInfo');
			 return $form;
			 break;


			default:
			return parent::createComponent($name);
			break;
		}

    }

	
	
	

	
	/*
	 * Vytvorenie objednavky
	 */
	function handleCreateOrder(){
		
		$order_info = NEnvironment::getSession('user_order_info');

		$values = $order_info['values'];
//		dump($values);exit;
		//presmerovanie ak nieco nieje v poriadku
		$session = NEnvironment::getSession('cart');

		if(empty($session->products)){ $this->redirect('default'); }

		if( empty($order_info['values'])){ $this->redirect('step2');}
		
		//znama polick login na email
		$values['email'] = $values['login'];

		$values['id_lang'] = $this->id_lang;
		
		//odstranenie loginu
		unset($values['login']);
		//odstranenie terms_and_conditions
		unset($values['terms_and_conditions']);
		
		
		//odstranenie discount_hash
//		$discount_hash = $values['discount_hash'];
		
		if ($values['type']==NULL)
			$values['type']=0;
		
		$values['user_discount'] = 0;
		// ak je prihlaseny setni id_user
		if( $this->user->isLoggedIn()){
			$values['id_user'] = $this->user->getIdentity()->data['user_id'];
			$values['user_discount'] = $this->user->getIdentity()->data['discount'];
		}
		
		
		
		$cart_info = OrderModel::getCartInfo(
				$session->products, 
				$order_info['values']['id_delivery'], 
				$order_info['values']['id_payment'], 
				$this->context, 
				false);
		
		$values['total_price'] = $cart_info['total_sum'];
		$values['total_price_with_tax'] = $cart_info['total_sum_with_tax'];
		
		$values['delivery_title'] = $cart_info['delivery_title'];
		$values['delivery_price'] = $cart_info['delivery_price']['price'];
		$values['delivery_tax'] = $cart_info['delivery_price']['tax'];
		
		$values['payment_title'] = $cart_info['payment_title'];
		$values['payment_price'] = $cart_info['payment_price']['price'];
		$values['payment_tax'] = $cart_info['payment_price']['tax'];

		
		//odstranenie id_delivery
		unset($values['id_delivery']);
		
		//odstranenie id_payment
		unset($values['id_payment']);
		
		$values['rate'] = Lang::get($this->id_lang)->rate;

		try{
			$id_order = OrderModel::createOrder($values, $session->products, $this->id_lang, $this->user );

			$conf = $this->context->parameters;
			
			$template = $this->template;

	        $template->setFile( APP_DIR.'/FrontModule/templates/Order/OrderEmail.phtml' );

			$template->discount = false;
			
			$template->o = OrderModel::get($id_order);
			
			$mail = new MyMail();
	        $mail->addTo( $values['email'] );

	        $mail->addBcc( $conf['client_email'] );

	        $mail->setSubject( _('Objednávka č. ').$id_order );
	        $mail->setTemplate($template);

			$mail->send();
			
			
			if($conf['HEUREKA']['ENABLE'] == 1){
				
				try  {

					$overeno = new HeurekaOvereno( $conf['HEURELA']['API_KEY'], HeurekaOvereno::LANGUAGE_SK );
					$overeno->setEmail( $values['email'] );

					foreach($session->products as $id_product_param=>$p){
						$product = ProductModel::getProductIdentifyByParam($id_product_param, $this->id_lang, $this->user );
						$overeno->addProduct($product->name);
					}			

					$overeno->send();

				} catch (Exception $e) { }
				
			}
			
			//vymazanie z kosika

			$session = NEnvironment::getSession('cart');
			unset($session->products);

			//vymazanie info o uzivatelovi
			unset($order_info['values']);
			
			$this->redirect('Cart:success');

		}catch(OrderException $e){
			$form->addError($e);
		}
	}

}
