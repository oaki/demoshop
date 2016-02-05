<?php

class Admin_ParsexmlPresenter extends Admin_BasePresenter {

	//const xml_link = 'http://www.sparks.sk/product/xml/SparksProducts.zip';

	public $all_product_param_db,$all_product_db,$all_categories_db, $category_1,$category_2,$category_3 ;

	function  startup() {
		parent::startup();

		//vypnutie debugeru + profileru
		$config =  NEnvironment::getConfig()->database;
		$config->profiler = false;
		$config->lazy = false;
		dibi::connect($config);
		//NDebug::enable( TRUE, LOG_DIR);
	}
	function beforeRender(){

		//self::parseSparksCSV();
	//	ProductModel::repairSequenceProductParam();exit;

		$cache = NEnvironment::getCache('parse_xml');

		$this->all_product_param_db = dibi::query("SELECT code,id_product FROM [product_param]")->fetchPairs('code','id_product');
		$this->all_product_db = dibi::query("SELECT group_code,id_product FROM [product]")->fetchPairs('group_code','id_product');
		
		$this->all_categories_db = dibi::fetchAll("SELECT id_category,name,id_parent FROM [category] JOIN [category_lang] USING(id_category) WHERE id_lang = 1");
//		print_r($this->all_categories_db);
		//zisti levely

		$category_1 = array();
		$category_2 = array();
		$category_3 = array();

		foreach($this->all_categories_db as $k=>$c){
			//prvy
			if($c['id_parent'] == NULL){
				$this->all_categories_db[$k]['level'] = 0;
				$category_1[$c['id_category']] = $c['name'];
			}
		}

		foreach($this->all_categories_db as $k=>$c){
			//prvy
			if($c['id_parent'] != NULL){
				foreach($this->all_categories_db as $k1=>$c1){
					if($c1['id_category'] == $c['id_parent']){
						if($this->all_categories_db[$k1]['level']==0){
							$this->all_categories_db[$k]['level'] = 1;
							$category_2[$c['id_category']] = $c['name'];
						}
					}
				}
			}
		}

		foreach($this->all_categories_db as $k=>$c){
			if(!isset($c['level'])){
				$this->all_categories_db[$k]['level'] = 2;
				$category_3[$c['id_category']] = $c['name'];
			}
		}

		$this->category_1 = $category_1;
		$this->category_2 = $category_2;
		$this->category_3 = $category_3;

		// stiahni a rozzipuj subor
//		$link

		$zip_file_name = WWW_DIR."/uploaded/xml/xml.zip";

		

		if(isset($cache['xml_file_name'])){
			$xml_file = $cache['xml_file_name'];
		}else{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,
					'http://www.sparks.sk/product/xml/SparksProducts.zip');
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$zip = curl_exec($ch);
			curl_close($ch);


			$fp = fopen( $zip_file_name, "w");
			fwrite($fp,$zip);
			fclose($fp);

			$archive = new PclZip($zip_file_name);
			$list = $archive->extract(WWW_DIR.'/uploaded/xml/');

			if($list[0]['status']!='ok')
			 throw new Exception ('Nastala chyba pri rozbalovani suboru: '.$list[0]['stored_filename']);

			 $cache->save('xml_file_name', $list[0]['stored_filename'], array(
				'expire' => time() + 60 * 1,
			));

			$xml_file = $list[0]['stored_filename'];
		}

		$myXMLString = file_get_contents(WWW_DIR.'/uploaded/xml/'.$xml_file);

		$myXMLString = iconv('UTF-8','windows-1250',  $myXMLString);

		$doc = new DOMDocument('1.0', 'windows-1250');
		$doc->loadXML($myXMLString);


		$x = $doc->documentElement;

		$c = 0;
		foreach ($x->childNodes AS $item)
		  {

			$product = array();
			foreach ($item->childNodes AS $i){
				$node_value = preg_replace('#!\[CDATA\[#', '', $i->nodeValue);
				$node_value = preg_replace('#\]\]#', '', $node_value);

//				$product[$i->nodeName] = iconv("UTF-8",'ISO-8859-2', $node_value);;
				$product[$i->nodeName] = $node_value;


			}
			//if($c>4000)print_r($product);
			$this->add($product);

			if(++$c >5000)throw new Exception ('prekroceni limit na import poloziek');
		  }


		  //oprav category rewrite link
		  CategoryModel::repairCategoryRewriteLink();

	}


	function add($values){
		//ak produkt nie je, pridaj

		if( $values['Name_sk']=='')
			return false;

		//ak je pridany aby ho system nemusel prepisovat
		$is_added = false;
		
		if(!isset( $this->all_product_param_db[$values['Code']]) AND $values['Name_sk']!=''){
/*		[Code] => 21420011
    [Name_sk] => Ladies polo shirt, S, white/ blue (01)
    [Name_cz] => Dámska poloko?e?a, S, white/ blue (01)
    [Name_en] => Ladies polo shirt, S, white/ blue (01)
    [Name_de] =>
    [Name_hu] =>
    [Package] => 1
    [Price_CZK] => 124.000000
    [Price_EUR] => 4.770000
    [Price_SK] => 143.700000
    [Image] => p990.02.jpg
    [Mark] => Best in Town
    [Stock] => 2
    [Category1_sk] => Textil
    [Category1_cz] => Textil
    [Category1_en] => Textile
    [Category1_de] =>
    [Category1_hu] =>
    [Category2_sk] => Výpredaj
    [Category2_cz] => Výprodej
    [Category2_en] => Discount
    [Category2_de] =>
    [Category2_hu] =>
    [Category3_sk] =>
    [Category3_cz] =>
    [Category3_en] =>
    [Category3_de] =>
    [Category3_hu] =>
*/
			//zisti ci je v product_temp_parse aby sme zistili group
			$product_temp_parse = dibi::fetch("SELECT * FROM product_temp_parse WHERE code = %s",$values['Code']);

			if(!$product_temp_parse)
				throw new Exception ('Polozka nie je v product_temp_parse. Nemame ako zistit skupinu resp. group_code');

			//zisti ci group_code je v nasej db ak nie je pridaj
			if(!isset( $this->all_product_db[$product_temp_parse['group_code']])){
				$arr = array(
					'mark'=>$values['Mark'],
					'group_code'=>$product_temp_parse['group_code'],
				);

				$id_product = ProductModel::add($arr);

				if(!$id_product)
					throw new Exception ('Nastala chyba. Nexistuje id_product');
				
				$arr_lang = array(
					'id_product'=>$id_product,
					'id_lang'=>1,
					'name'=>$product_temp_parse['groupnameSK'],
					'link_rewrite'=>  NStrings::webalize($product_temp_parse['groupnameSK'])
				);

				ProductModel::addProductLang($arr_lang);

				$this->all_product_db[$product_temp_parse['group_code']] = $id_product;
				$this->all_product_param_db[$values['Code']] = $id_product;
			}else{
				$id_product = $this->all_product_db[$product_temp_parse['group_code']];
				$this->all_product_param_db[$values['Code']] = $id_product;
			}


			//pridanie parametru produktu
			$arr = array(
				'id_product'=>$id_product,
				'code'=>trim($values['Code']),
				'capacity'=>$values['Package'],
				'color'=>trim($product_temp_parse['color']),
				'size'=> trim( ($product_temp_parse['dimension']!='')?$product_temp_parse['dimension']:$product_temp_parse['size']),
//				'dimension'=>($product_temp_parse['dimension']!='')?$product_temp_parse['dimension']:$product_temp_parse['size'],
				'material'=>trim($product_temp_parse['material']),
				'price'=>$values['Price_EUR'],
				'stock'=>$values['Stock'],
				'image'=>$values['Image'],
			);

			ProductModel::addProductParamValue($arr);

			$is_added = true;
		}


		$id_product =  $this->all_product_param_db[$values['Code']];

		/*
		 * Uprava produktu
		 */
		if( !$is_added  AND $values['Name_sk']!=''){
			//upravi len co je v xml - nie co je parsovane z CSV

			$arr = array(
				'price'=>$values['Price_EUR'],
				'stock'=>$values['Stock'],				
			);

			$id_product_param = $this->all_product_param_db[$values['Code']];

			ProductModel::setProductParamValue($arr, $id_product_param, 1);
		}


		$id_categories = array();
//echo $values['Category1_sk'];

		if($id_category = array_search($values['Category1_sk'],$this->category_1)){
			$id_categories[] = $id_category;
		};
		
		if($id_category = array_search($values['Category2_sk'],$this->category_2)){
			$id_categories[] = $id_category;
		};
		
		if($id_category = array_search($values['Category3_sk'],$this->category_3)){
			$id_categories[] = $id_category;
		};

			ProductModel::removeAllCategory($id_product);
//			print_r($id_categories);
			ProductModel::addProductToCategory($id_categories, $id_product);
	

		$pom = 0;
		
		//ak nie je kategoria pridaj
		if( !in_array($values['Category1_sk'], $this->category_1) AND $values['Category1_sk'] != '' ){
			$id_category = CategoryModel::add(array('id_parent'=>NULL,'active'=>1));

			$arr = array('id_category'=>$id_category, 'id_lang'=>1,'name'=>$values['Category1_sk']);
			
			CategoryModel::addCategoryLang($arr);
			$this->category_1[] = $values['Category1_sk'];


			++$pom;
		}

		if( !in_array($values['Category2_sk'], $this->category_2) AND $values['Category2_sk']!=''){

			$cat1 = dibi::fetchSingle("SELECT category.id_category FROM [category] JOIN [category_lang] USING(id_category) WHERE name = %s", $values['Category1_sk']," AND id_lang = 1 AND id_parent IS NULL");
			if(!$cat1)
				throw new Exception ('Rodic pre kategoriu neexistuje : '.$values['Category1_sk']);

			 $id_category = CategoryModel::add(array('id_parent'=>$cat1,'active'=>1));

			$arr = array('id_category'=>$id_category, 'id_lang'=>1,'name'=>$values['Category2_sk']);
//			print_r(array('id_parent'=>$cat1,'active'=>1,'name'=>$values['Category2_sk'] ));
			CategoryModel::addCategoryLang($arr);

			$this->category_2[] = $values['Category2_sk'];
			++$pom;
		}
//
		if( !in_array($values['Category3_sk'], $this->category_3) AND $values['Category3_sk']!=''){


			$cat2 = dibi::fetchSingle("SELECT category.id_category FROM [category] JOIN [category_lang]  USING(id_category) WHERE name = %s", $values['Category2_sk']," AND id_lang = 1 AND id_parent IS NOT NULL");
			if(!$cat2)
				throw new Exception ('Rodic pre kategoriu neexistuje : '.$values['Category2_sk']);

			if($values['Category3_sk'] == 'Pullovers'){
				var_dump($cat2);
			}

			$id_category = CategoryModel::add(array('id_parent'=>$cat2,'active'=>1));

			if($values['Category3_sk'] == 'Pullovers'){
				var_dump($id_category);
			}

			$arr = array('id_category'=>$id_category, 'id_lang'=>1,'name'=>$values['Category3_sk']);

			if($values['Category3_sk'] == 'Pullovers'){
				var_dump($arr);
			}

			CategoryModel::addCategoryLang($arr);

			$this->category_3[] = $values['Category3_sk'];
			++$pom;
		}

		if($pom>0){
//			echo "cat1: ". $values['Category1_sk']." - cat2: ". $values['Category2_sk']." - cat3:". $values['Category3_sk']."
//			";
		}


	}


	//parse XML dodane od sparsku
	static function parseSparksCSV(){

		$inputFileName = WWW_DIR."/uploaded/xml/s.csv";
$csv = file_get_contents($inputFileName);
$data = explode("\n", $csv);

$pom = 0;

foreach($data as $row){
	$pom++;
	$c = explode(",", $row);
	if($pom==1)continue;
//Array
//(
//    [0] => Kod_IT
//    [1] => Znacka
//    [2] => Kategória 1
//    [3] => Kategória 2
//    [4] => Kategória 3
//    [5] => Skupinový názov [SK]
//    [6] => Skupinový názov [CZ]
//    [7] => Skupinový názov [EN]
//    [8] => Farba [SK]
//    [9] => Farba [CZ]
//    [10] => Farba [EN]
//    [11] => Textil Y/N
//    [12] => Skupina
//    [13] => Veľkosť
//    [14] => Rozmer
//    [15] => Materiál
//)
	$arr = array(
		'code'=>$c[0],
		'mark'=>$c[1],

		'cat1'=>$c[2],
		'cat2'=>$c[3],
		'cat3'=>$c[4],
		'groupnameSK'=>$c[5],
		
		'color'=>$c[8],
		'textil'=>$c[11],
		'group_code'=>$c[12],
		'size'=>$c[13],
		'dimension'=>$c[14],
		'material'=>$c[15]
	);

	try{
		dibi::query("INSERT INTO [product_temp_parse]",$arr);
	}catch(Exception $e){
		echo $e->getMessage()."<br> \n";
	}
	
	if($pom > 30000){
		echo 'zastavene';
		exit;
	}
}
		exit;

	}

}