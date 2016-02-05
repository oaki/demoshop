<?php

/**
 * Description of OrderPresenter
 *
 * @author oaki
 */
class Admin_OrderPresenter extends Admin_BasePresenter {

	function  beforeRender() {
	    parent::beforeRender();

	    $this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');


	}

	/*
	 * delete order
	 */
	function handleDeleteOrder($id){
		
		OrderModel::deleteOrder($id);
		
		$this->flashMessage('Objednávka bola zmazaná');
		$this->redirect('this');
	}


	function renderView($id){		
		//DiscountHashModel::hasDiscount(1);
		$this->template->o = OrderModel::get($id);
		
		
		$this->template->admin_mod = true;
	}

	public function renderDefault() {
		$datasource = OrderModel::getDatasource();
	}

	function actionGenerateExcel( $id_order ){

		$order = OrderModel::get( $id_order );
//print_r($order);exit;
		$order['country_name'] = dibi::fetchSingle("SELECT country_name FROM [gui_user_country] WHERE iso = %s",$order['iso']);
		$date = new DateTime( $order['add_date'] );

		$order['create_date'] = $date->format('d/m/Y');
		
		require_once LIBS_DIR.'/PHPExcel/PHPExcel.php';

		//cesta k sablone
		$dir = WWW_DIR."/uploaded/order_excel_template/invoice_template.xlsx";

		$objPHPExcel = PHPExcel_IOFactory::load($dir);

		// Set active sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Assign data
		$invoice_number = str_pad($order['id_order'], 5, "0", STR_PAD_LEFT);
//		var_dump($invoice_number);exit;
		$objPHPExcel->getActiveSheet()->setCellValue('INVOICE_NUMBER', $invoice_number );
		$objPHPExcel->getActiveSheet()->setCellValue('VARIABLE_SYMBOL', $invoice_number );
		$objPHPExcel->getActiveSheet()->setCellValue('B_NAME', $order['name'].' '.$order['surname'].' '.$order['company_name']);
		$objPHPExcel->getActiveSheet()->setCellValue('B_STREET', $order['address']);
		$objPHPExcel->getActiveSheet()->setCellValue('B_CITY', $order['city']);
		$objPHPExcel->getActiveSheet()->setCellValue('B_COUNTRY', $order['country_name']);
		
		if($order['ico']!=''){
			$objPHPExcel->getActiveSheet()->setCellValue('B_ICO', $order['ico']);
			$objPHPExcel->getActiveSheet()->setCellValue('B_TITLE_ICO', 'IČO:');
		}

		if($order['dic']!=''){
			$objPHPExcel->getActiveSheet()->setCellValue('B_IC_DPH', $order['dic']);
			$objPHPExcel->getActiveSheet()->setCellValue('B_TITLE_IC_DPH', 'IČO:');
		}

		$objPHPExcel->getActiveSheet()->setCellValue('B_IC_DPH', $order['dic']);
		$objPHPExcel->getActiveSheet()->setCellValue('CREATE_DATE', $order['create_date']);
		$objPHPExcel->getActiveSheet()->setCellValue('PAYMENTS_METHOD', 'PP');
		$objPHPExcel->getActiveSheet()->setCellValue('DPH_TITLE', 'DPH '.NEnvironment::getVariable('vat')."%");
		$objPHPExcel->getActiveSheet()->setCellValue('DPH', NEnvironment::getVariable('vat'));

		// Add data
		$collum = 18;

		$vat_coef = 1 + NEnvironment::getVariable('vat') / 100;
		foreach($order['products'] as $p){
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $collum, $p['ean13']);
			$objPHPExcel->getActiveSheet()->setCellValue('C' . $collum, $p['name']);
			$objPHPExcel->getActiveSheet()->setCellValue('D' . $collum, $p['count']);//mnozstvo
			$objPHPExcel->getActiveSheet()->setCellValue('E' . $collum, "ks");//jed
			$objPHPExcel->getActiveSheet()->setCellValue('F' . $collum, $p['price'] / $vat_coef );//bez dph
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $collum, $p['count']*$p['price'] / $vat_coef );
			++$collum;
		}


		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');


		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="faktura_'.$invoice_number.'.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter->save('php://output');

		exit;
	}

	function  createComponent($name) {
		 switch ($name){

		 case 'statusForm':
			 $f = new NAppForm($this, $name);
			 $renderer = $f->getRenderer();
			 $renderer->wrappers['pair']['container'] = '';
			 $renderer->wrappers['controls']['container'] = '';
			 $renderer->wrappers['control']['container'] = '';
			 $renderer->wrappers['label']['container'] = '';

			 $f->addSelect('order_status', 'Stav: ', OrderModel::getStatus());
			 $f->addSubmit('btn_submit', 'Uložiť');

			 $o = OrderModel::get($this->getParam('id'));

			 $f->setDefaults($o);
			 if($f->isSubmitted() AND $f->isValid()){
				$values = $f->getValues();

				OrderModel::updateStatus($this->getParam('id'), $values['order_status']);

				$o = OrderModel::get($this->getParam('id'));
				
				$template = $this->template;

				$template->setFile( APP_DIR.'/FrontModule/templates/Order/OrderChangeStatusEmail.phtml' );
//print_r($o);
				$template->o = $o;
				$template->status = OrderModel::getStatus( $o['order_status'] );
//				echo $template->status;exit;
				$mail = new MyMail();
				$mail->addTo( $o['email'] );

				$mail->addBcc( NEnvironment::getVariable('client_email') );

				$mail->setSubject( _('Objednávka č. ').$o['id_order']. ' zmena stavu.' );
				$mail->setTemplate($template);
//echo $template;exit;
				$mail->send();

				$this->flashMessage('Bol odoslaný email o zmene statusu.' );
				$this->redirect('this');
			 }

			 return $f;
			 break;

		 case 'orderTabella':

			$grid = new Tabella( OrderModel::getDatasource() ,
				array(
					'sorting'=>'desc',
					'order'=>'id_order',
				)
			);


			$grid->addColumn( "Číslo obj.", "id_order", array( "width" => 50 ) );
			$grid->addColumn( "Meno", "name", array( "width" => 100 ) );
			$grid->addColumn( "Priezvisko", "surname", array( "width" => 100 ) );
			$grid->addColumn( "Mesto", "city", array( "width" => 100 ) );
			$grid->addColumn( "Dátum vytvorenia", "add_date", array( "width" => 100 ) );
			$grid->addColumn( "Celková cena", "total_price", array( "width" => 100 ) );

			$grid->addColumn( "Stav", "order_status",
					array(
						"width" => 50,
						'type'=>  Tabella::SELECT,
						"editable" => true,
						"filter" => OrderModel::getStatus(),
						'renderer' => function($row){
							$el = NHtml::el( "td" )->setHtml(  OrderModel::getStatus($row['order_status']) );
							return $el;
						}
					) 
			);

			$grid->addColumn( "Spôsob platby", "payment_method",
					array(
						"width" => 90,
						'type'=>  Tabella::SELECT,
						"editable" => false,
						"filter" => OrderModel::getPaymentMethod(),
						'renderer' => function($row){
							$el = NHtml::el( "td" )->setHtml(  OrderModel::getPaymentMethod($row['payment_method']) );
							return $el;
						}
					) 
			);


		$grid->addColumn("", "",
			array(
				"width" => 30,
				'filter'=>NULL,
				"options" => '',

				"renderer" => function( $row ) {
					$el = NHtml::el( "td" );

					/*
					 * link na zmazanie produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'deleteOrder!' , array('id'=>$row->id_order))
					)->addClass( 'deleteIcon' )
					);



					/*
					 * link na editaciu produktu
					 */

					$el->add(
					NHtml::el( 'a' )->href(
						NEnvironment::getApplication()->getPresenter()->link( 'view' , array('id'=>$row->id_order))
					)->addClass( 'editIcon' )
					);

	 /*
						 * ikona aktivan polozka, neaktivan polozka
						 */
						$span = NHtml::el('span');

						
						$el->add($span);

					return $el;
				}
				)
			);



			$this->addComponent( $grid, $name );
			 break;

		 default:
			return parent::createComponent($name);
			 break;
		 }
	}//end createComponent
        
}
