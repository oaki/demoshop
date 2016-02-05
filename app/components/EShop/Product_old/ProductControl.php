<?php

/**
 * Description of ProductControl
 *
 * @author oaki
 */
class EshopProductControl_old extends BaseControl {


	const MODULE_NAME = 'product';

	/** @persistent */
	public $id_product;

	/** @persistent */
	public $show;

	/** @persistent */
	public $sale;

	/** @persistent */
	public $material;

	/** @persistent */
	public $size;

	/** @persistent */
	public $color;

	/** @persistent int*/
	public $count = 1;

	public function render($id_product = NULL, $show = true, $sale = false){


		$params = NEnvironment::getApplication()->getPresenter()->getParam();
		//NDebug::bardump($params);
		if( isset($params['material']) )
			$this->material = $params['material'];

		if( isset($params['size']) )
			$this->size = $params['size'];

		if( isset($params['color']) )
			$this->color = $params['color'];

		if( isset($params['id_product']))
			$this->id_product = $params['id_product'];

		if( isset($params['count']))
			$this->count = (int)$params['count'];

		if($id_product != NULL)
			$this->id_product = $id_product;

		
		$id_lang = $this->getPresenter()->id_lang;

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/Product.phtml');

		$template->l = ProductModel::get($this->id_product, $id_lang);

		$lowest_price_product_param = dibi::fetchSingle("SELECT id_product_param FROM [product_param] WHERE id_product = %i",$this->id_product,"ORDER BY price LIMIT 1");
		$template->l['price_array'] = ProductModel::getPrice($lowest_price_product_param);
		
		if($show){

			$template->images = ProductModel::getImages($this->id_product);			

			$template->files = ProductModel::getFiles($this->id_product);
			
			$template->product_alternative = ProductModel::getProductAlternative($this->id_product);

			// prepocitavanie
			$this->template->id_product = $this->id_product;

			
			$product_params = ProductModel::getProductParamFluent($this->id_product)->orderBy('price')->fetchAll();
	//		print_r($this->template->product_params);exit;
			//zisti ktore sltpce su vyplnene
			$this->template->param = array();

			//zisti pre moznosti pre material ak neni zisti pre velkost ak neni zisti pre farbu
			foreach($product_params as $k=>$p){
				if($p['material']!='')$this->template->param['material'][$p['material']] = $p['material'];
				if($p['size']!='')$this->template->param['size'][$p['size']] = $p['size'];
				if($p['color']!='')$this->template->param['color'][$p['color']] = $p['color'];
			}

			if( isset( $this->template->param['material'] )){
				reset($this->template->param['material']);

				if($this->material == NULL OR !isset( $this->template->param['material'][$this->material] ) )
					$this->material = key($this->template->param['material']);
			}

			
			if( isset($this->template->param['size'] )){
				
				reset($this->template->param['size']);

				$this->template->param['size'] = dibi::query("SELECT size FROM [product_param] WHERE %if",$this->material!=NULL,"material = %s",$this->material,"AND %end id_product = %i",$this->id_product,"AND size != ''")->fetchPairs('size','size');

				if($this->size == NULL OR !isset( $this->template->param['size'][$this->size] ) )
					$this->size = key($this->template->param['size']);

				usort($this->template->param['size'], 'EshopProductControl::cmp');
//				print_r($this->template->param['size']);
			}


			if( isset($this->template->param['color'] )){
				
				reset($this->template->param['color']);

				$this->template->param['color'] = dibi::query("SELECT color FROM [product_param] WHERE %if",$this->material!=NULL,"material = %s",$this->material,"AND %end %if",$this->size!=NULL,"size = %s",$this->size,"AND %end id_product = %i",$this->id_product,"AND color!=''")->fetchPairs('color','color');

				if($this->color == NULL OR !isset( $this->template->param['color'][$this->color] ) )
					$this->color = key($this->template->param['color']);
			}

//			zisti id_product_param
			$product = dibi::fetch("
				SELECT
					*, COUNT(*) as c
				FROM
					[product_param]
				WHERE 1=1 %if", $this->material!=NULL,"AND material = %s",$this->material,"%end
					%if",$this->size!=NULL,"AND size = %s",$this->size,"%end
					%if",$this->color!=NULL,"AND color = %s",$this->color,"%end
					AND id_product = %i",$this->id_product,"LIMIT 1");

			$this->template->id_product_param = $product['id_product_param'];

			$this->template->price = ProductModel::getPrice( $product['id_product_param'] );

			$this->template->sql = dibi::$sql;

			$this->template->pictograms = PictogramModel::get($this->id_product);


			//FEATURES - zobrazenie pre sekciu MATRACE link na potahove latky
			$categories = ProductModel::getProductCategories($this->id_product);
			$pom = array();
			foreach($categories as $c){ $pom[] = $c['id_category'];};
			if(in_array('2', $pom) OR in_array('3', $pom)){
				$this->template->show_link_potahove_latky = 1;				
			}
			
			//FEATURES - zobrazenie pre sekciu POSTELE
			
			$pom = array();
			foreach($categories as $c){ $pom[] = $c['id_category'];};
			if(in_array('45', $pom)){
				$this->template->show_different_name_for_postele = 1;				
			}
			
			
			
		}else{				
			$template->setFile(dirname(__FILE__) . '/ProductAnnotation.phtml');
			$template->image = self::getImage( $this->id_product );

//			$categories = $this->getPresenter()->getParam('categories');
//			
//			if($categories=='' OR $categories=='novinky'){
//				
//				$pom = ProductModel::getProductCategories($id_product);
//				$pom = current($pom);
//				$categories = CategoryModel::getUrl($pom['id_category']);
//			}
			
			$pom = ProductModel::getProductCategories($id_product);
//			print_r($pom);
			$pom = end($pom);
			$categories = CategoryModel::getUrl($pom['id_category']);
				
			$template->l['url'] = $this->getPresenter()->link('Eshop:current', array('categories'=> $categories, 'url_identifier'=>$template->l['link_rewrite']));
		}
//		var_dump();

		if ($sale) {
			$params = dibi::fetch("SELECT *  FROM [product_param] WHERE id_product = %i",$this->id_product);
			$template->l['size'] = $params->size;
			$template->l['material'] = $params->material;
			$template->l['color'] = $params->color;
			$template->l['stock'] = $params->stock;
		}
		$template->render();
	}


	static function cmp($a, $b) {
		$pom_a = explode("x",$a);
		$pom_b = explode("x",$b);
//print_r($pom_a);
//print_r($pom_b);
		$a1 = (string)(int)@$pom_a[0];
		$a2 = (string)(int)@$pom_a[1];

		$b1 = (string)(int)@$pom_b[0];
		$b2 = (string)(int)@$pom_b[1];
		
		$a = "$a1" . "$a2";
		$b = "$b1" . "$b2";

		$pom_a_int = (int)$a;
		
		$pom_b_int = (int)$b;

//		echo $pom_a_int.' - '.$pom_b_int.' '.'<br >';
		
		if ($pom_a_int == $pom_b_int) {
			return 0;
		}
		return ($pom_a_int < $pom_b_int) ? -1 : 1;
	}

	public static function getImage($id_node){

		$image = FilesNode::getOneFirstFile(self::MODULE_NAME, $id_node);

		$image['thumb'] = Files::gURL(@$image['src'], @$image['ext'], 220, 160, 6);
		$image['big'] = Files::gURL(@$image['src'], @$image['ext'], 800, 600);
		return $image;
	}

	/*
	 * handles
	 */
	 function handleUpdatePrice(){

		 $this->invalidateControl();

		 
	 }


	 function handleSendReference(NAppForm $form){
			$values = $form->getValues();
			
			$product = ProductModel::get($values['id_product'], 1);
			
			if(!$product){
				$this->getPresenter()->flashMessage('Pre daný produkt nemôžete odoslať referenciu.');
				$this->getPresenter()->redirect('this');
			}
			
			$template = $this->template;
			
	        $template->setFile( dirname(__FILE__).'/ReferenceEmail.phtml' );

			$template->product_name = $product['name'];
			$template->v = $values;
			$mail = new MyMail();
			$mail->addTo( NEnvironment::getVariable('client_email') );

			$mail->setSubject( _('Pridanie referencie ') );
			$mail->setTemplate($template);

			$mail->send();

		 $this->getPresenter()->flashMessage('Referencia bola úspešne odoslaná.');
		 $this->getPresenter()->redirect('this');
		 
		 
	 }
	 
	 
	protected function createComponent($name) {
	   switch ($name){
	       case 'EshopProduct':
				$p = new EshopProductControl ();
				$p->invalidateControl();
				return $p;
		   break;
		   
		   case 'referenceForm':
			   $params = NEnvironment::getApplication()->getPresenter()->getParam();
			   
			   $form = new MyForm;
			   $form->addText('name', 'Meno:');
			   $form->addProtection();
			   $form->addText('email', 'Email:');
			   $form->addTextarea('text', 'Text:');
			   
			   if(class_exists('reCAPTCHA')){
					$form->addRecaptcha('recaptcha_input','Overenie:')
						->addRule('reCAPTCHA::validate','Prosím prepíšte správne text z obrázku.');
				}
				
			   $form->addSubmit('submit', 'Odoslať');
			   $form->onSuccess[] = array($this,'handleSendReference');
			   $form->addHidden('id_product');
			   
			   $form->setDefaults(array('id_product'=>  ProductModel::getIdProductByUrl($params['url_identifier'], 1)));
			   return $form;

			   break;

		   default:
			   return parent::createComponent($name);
			   break;
	   }

	}

	
}
