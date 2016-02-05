<?php

/**
 * Description of Admin_SettingPresenter
 *
 * @author oaki
 */
class Admin_Setting_HomepagePresenter extends Admin_Setting_BasePresenter {

	function handleSaveSetting(NForm $form){
		$values = $form->getValues();
		
		
		//ak je to "obchodne podmienky" zisti id_page a zapis do konfigu
		$values['CONDITIONS_CMS_PAGE_ID'] = dibi::fetchSingle("SELECT id_menu_item FROM [menu_item] JOIN [node] 
				USING(id_menu_item) WHERE node.id_node = %i", $values['CONDITIONS_CMS_ID']);
		
		
		$setting = $this->getService('Setting');
		foreach($values as $name=>$value){
			
			$setting->insert_update($name, array('name'=>$name,'value'=>$value));
			
		}	
		
		$this->flashMessage('Nastavenia boli upravené');
		$this->redirect('this');
	}
	
	public function actionDefault() {
		
	}

	public function renderDefault() {
		
	}
	
	
	
	protected function createComponentSettingForm($name){
		
		
		$article = $this->getService('Article');
		
		$f = new MyForm;
		$f->addGroup('Nastavenie eshopu');
		$f->addSelect('SHOP_ENABLE', 'Zapnúť obchod', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$f->addSelect('SHOW_PRICE_WITH_TAX', 'Cena sa bude zadávať i zobrazovať s DPH?', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$f->addSelect('SHOW_TAX_FOR_PRODUCT', 'Zobraziť priradenie dane pri produkte', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
//		$f->addSelect('SHOW_PRICE_WITH_DPH', 'Cena sa zobraz s DPH', array(0=>'nie', 1=>'áno'))
//			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$f->addSelect('CONDITIONS_CMS_ID', 'Obchodné podmienky, použitie CMS stránky', 
				$article->getFluent()->fetchPairs('id_node', 'title')
		);
		
		$f->addTextArea('footer_for_emails', 'Pätička pre emaili')
			->getControlPrototype()->style = "width:700px;";
		
		$f->addGroup('Heureka');
		
		$f->addSelect('HEUREKA__ENABLE', 'Spustiť heureku', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$f->addText('HEUREKA__API_KEY', 'API KEY');
		
		$f->addGroup('Facebook');
		
		$f->addSelect('FACEBOOK__AUTH_ENABLE', 'Povoliť Facebook prihlasovanie', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		


//twitter.consumer_key = JD4NBeGvlkEomwmaYYlIQ
//twitter.consumer_secret = WgRwndqR3oA6hShLk43mnQVTpcJvvY9Lmsfe45WNXw
//
//google.client_id = 924141147825.apps.googleusercontent.com
//google.client_secret = G1SSmJ0efgD69eKP43D04FEd
//google.redirect_url = "http://www.propagacnepredmety.sk/google-account/log-in/"
		$f->addText('FACEBOOK__appId', 'appId');
		$f->addText('FACEBOOK__secret', 'secret');
		
		
		$f->addGroup('Google Account');
		
		$f->addSelect('GOOGLE__AUTH_ENABLE', 'Povoliť Google prihlasovanie', array(0=>'nie', 1=>'áno'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');

		$f->addText('GOOGLE__client_id', 'client_id');
		$f->addText('GOOGLE__client_secret', 'client_secret');
		$f->addText('GOOGLE__application_name', 'application_name');
		$f->addText('GOOGLE__redirect_url', 'redirect_url');
		
		$f->addGroup('Google Analytics');
		
		$f->addText('GOOGLE_ANALYTICS__ID', 'ID');
			
		
		$f->addGroup('Doprava');
		
//		$f->addSelect('DELIVERY_IS_WITH_TAX', 'Doprava je už s danou?', array(0=>'nie', 1=>'áno'))
//			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$vat = VatModel::init();
		
		$f->addSelect('DELIVERY_TAX', 'Dan na dopravu?', $vat->getFluent()->fetchPairs('value', 'name'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		
		$f->addGroup('Platby');
		$vat = VatModel::init();
		$f->addSelect('PAYMENT_TAX', 'Dan na platbu?', $vat->getFluent()->fetchPairs('value', 'name'))
			->addRule( NForm::FILLED, 'Musí byť vyplené');
		
		$f->addGroup();
		
		$f->addSubmit('btn','Uložiť');
		
		$f->onSuccess[] = array($this, 'handleSaveSetting');
		
		
		$f->setDefaults( $this->getService('Setting')->fetchPairs() );
		return $f;
		
		
	}

}