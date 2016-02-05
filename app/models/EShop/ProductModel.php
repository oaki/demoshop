<?php

class ProductModel extends NObject
{

    const MODULE_NAME = 'product';

    static function add($values)
    {
        $values['product_sequence'] = dibi::fetchSingle("SELECT MAX(product_sequence) + 1 FROM [product]");
        dibi::query("INSERT INTO [product]", $values);

        return dibi::insertId();
    }

    static function insertNew()
    {
        $id_vat                    = NEnvironment::getService('Vat')->getDefault();
        $id_product_template_group = NEnvironment::getService('ProductTemplateGroupModel')->getIdDefaultTemplate();
        $id_product                = self::add(array('active' => 0, 'adddate' => new DibiDateTime, 'added' => 0, 'id_vat' => $id_vat, 'id_product_template_group' => $id_product_template_group));

        $langs = Setting::getLangs();

        foreach ($langs as $l) {
            $lang_val = array('id_product' => $id_product, 'id_lang' => $l['id_lang']);
            ProductModel::addProductLang($lang_val);
        }

        return $id_product;
    }

    //vymaze vsetky vytvorene produkty, ktore neboli ulozene!!! spusta sa po ukladani produktu
    private static function __cronDelete()
    {
        //spusta sa nahodne, 1 ku 10
        if (rand(0, 10) == 0) {
            $ids = self::getFluent($id_lang = 1, $joinToCategory = false, false)->removeClause('select')
                ->select('id_product')->where('added = 0 AND NOW() > (adddate + INTERVAL 1 DAY)')->fetchAll();

            if ($ids) {
                foreach ($ids as $id) {
                    self::delete($id);
                }
            }
        }
    }

    static function delete($id_product)
    {
        dibi::query("DELETE FROM [product] WHERE id_product = %i", $id_product);
    }

    static function getUrl($id_product, $context)
    {

        $key = 'getUrl(' . $id_product . ',' . $context->application->presenter->id_lang . ')';
        $r   = self::loadCache($key);
        if ($r)
            return $r;
        $tmp  = self::getProductWithParams($id_product, $context->application->presenter->id_lang, $context->user);
        $link = $context->application->presenter->link(':Front:Product:default', array('id_category' => $tmp['main_category'], 'id' => $id_product));

        return self::saveCache($key, $link);
    }

    static function save($values, $id_product, $id_lang)
    {
        if (!isset($values['added']))
            $values['added'] = 1;

        dibi::query("
			UPDATE [product]
			LEFT JOIN [product_lang] USING(id_product)
			SET", $values, "
			WHERE
			id_lang = %i", $id_lang, " AND id_product = %i", $id_product);

        self::__cronDelete();
    }

    static function get($id_product, $id_lang)
    {
        $key = 'get(' . $id_product . ',' . $id_lang . ')';
        $r   = self::loadCache($key);
        if ($r)
            return $r;

        $r = dibi::fetch("
			SELECT *
			FROM
			[product]
			LEFT JOIN [product_lang] USING(id_product)
			WHERE id_product = %i", $id_product, "AND id_lang = %i", $id_lang);

        $r['product_template_group'] = NEnvironment::getService('ProductTemplateGroupModel')->fetchAssocAllParam($r['id_product_template_group'], $without_checked = true);

        return self::saveCache($key, $r);
    }

    static function getProductWithParams($id_product, $id_lang, $user)
    {

        $product = self::get($id_product, $id_lang);

        if (!$product)
            throw new NBadRequestException('Product neexistuje');

        $product = (array)$product;

        $key   = 'getProductWithParams(' . $id_product . ',' . $id_lang . ')';
        $value = self::loadCache($key);

        if (!$value) {

            $value          = array();
            $value['files'] = self::getFiles($id_product);

            if (isset($value['files'][0])) {
                $value['first_file'] = &$value['files'][0];
            } else {
                $value['first_file'] = array('src' => 'no-image', 'ext' => 'jpg');
            };

            $value['categories'] = self::getProductCategories($id_product);

            //zisti main category!!!
            $max_count   = 0;
            $main_parent = null;
            foreach ($value['categories'] as $k => $category) {
                $tmp_parents = CategoryModel::getParents($category['id_category'], $id_lang);

                if ($max_count < count($tmp_parents)) {
                    $main_parent = $tmp_parents[key($tmp_parents)];
                    $max_count   = count($tmp_parents);
                }
            }

            $value['main_category'] = $main_parent;

            self::saveCache($key, $value);

        }

        $product = array_merge($product, $value);

        //no cache
        $product['params'] = self::getProductParams($id_product, $id_lang, $user);

        $first_key_param = null;
//		print_r($product);exit;

        //zisti ktora varianta je najviac na sklade, tu budeme uvadzat pri nahlade
        $product['max_stock'] = 0;

        //zisti min_price

        $product['min_price'] = array(
            'original_price' => 0,
            'sale_percent'   => 0,
            'sale'           => 0,
            'price'          => 0,
            'tax'            => 0,
            'user_discount'  => 0,
            'price_with_tax' => 0,
            'tax_price'      => 0,
            'price_showed'   => 0
        );

        //iba pre parameter connection, ci nie je vsade prazdny
        $show_connection = false;

        foreach ($product['params'] as $k => $r) {
            if ($r['connection'] != '')
                $show_connection = true;

            if ($first_key_param == null)
                $first_key_param = $k;

            if (isset($r['stock']) AND $r['stock'] > $product['max_stock'])
                $product['max_stock'] = $r['stock'];

            if ($r['price_array']['price_showed'] < $product['min_price']['price_showed'] OR $product['min_price']['price_showed'] == 0)
                $product['min_price'] = &$r['price_array'];

        }

        $product['show_connection'] = $show_connection;

        if (isset($product['params'][$first_key_param])) {
            $product['first_param'] = &$product['params'][$first_key_param];
        } else {
            $product['first_param'] = null;
        };

        return $product;
    }

    static function getProductParams($id_product, $id_lang, $user)
    {
        $params = self::getProductParamFluent($id_product)
            ->removeClause('select')
            ->select('id_product_param')->fetchAll('id_product_param');

        $return = array();

        foreach ($params as $p) {
            $return[] = self::getProductIdentifyByParam($p['id_product_param'], $id_lang, $user);
        }

        return $return;
    }

    static function getProductIdentifyByParam($id_product_param, $id_lang, $user)
    {
        $key = 'getProductIdentifyByParam(' . $id_product_param . ',' . $id_lang . ')';

        $r = self::loadCache($key);
        if (!$r) {
            $r = self::saveCache($key, dibi::fetch("
				SELECT *
				FROM
				[product]
				LEFT JOIN [product_lang] USING(id_product)
				JOIN [product_param] USING(id_product)
				WHERE id_product_param = %i", $id_product_param, "AND id_lang = %i", $id_lang)
            );
        }

        unset($r['price']);
        $r['price_array'] = self::getPrice($id_product_param, $user);

        return $r;
    }

    static function addProductLang($values)
    {
        dibi::query("INSERT INTO [product_lang]", $values);
    }

    static function addProductToCategory($id_categories, $id_product)
    {
        if (!empty($id_categories)) {
            foreach ($id_categories as $k => $id) {
                dibi::query("INSERT INTO [category_product]", array('id_category' => $id, 'id_product' => $id_product));
            }
        }
    }

    static function getProductCategories($id_product)
    {
        return dibi::query("SELECT id_category FROM [category_product] WHERE id_product = %i", $id_product)->fetchAll();
    }

    static function deleteProductFromCategories($id_product)
    {
        dibi::query("DELETE FROM [category_product] WHERE id_product = %i", $id_product);
    }

    static function removeAllCategory($id_product)
    {
        dibi::query("DELETE FROM [category_product] WHERE id_product = %i", $id_product);
    }

    static function getDatasource($id_lang = 1)
    {
        return dibi::dataSource("
			SELECT
			*
			FROM
			[product]
			JOIN [category_product] USING(id_product)
			LEFT JOIN [product_lang] USING(id_product)
			WHERE
			id_lang = %i", $id_lang, "

		");
    }

    static function repairAllProductSequence()
    {
//		echo 1;
        $list = self::getFluent(1, false)->orderBy('product_sequence')->fetchAll();

        foreach ($list as $k => $l) {
            self::save(array('product_sequence' => $k + 1), $l->id_product, 1);
        }
    }

    static function getFluent($id_lang = 1, $joinToCategory = true, $only_added = true)
    {

        $q = dibi::select('*, ROUND(MIN(price),2) AS min_price')
            ->from('product')
            ->join('product_lang')
            ->using('(id_product)')
            ->where("id_lang = %i", $id_lang)
            ->leftJoin('product_param')
            ->using('(id_product)')->groupBy('id_product');

        if ($joinToCategory)
            $q->leftJoin('category_product')
                ->using('(id_product)');

        if ($only_added) {
            $q->where('added = 1');
        }

        return $q;
    }

    public static function getFiles($id_product)
    {
        $key   = 'getFiles(' . $id_product . ')';
        $files = self::loadCache($key);
        if (!$files) {
            $files = self::saveCache($key, FilesNode::getAllFiles(self::MODULE_NAME, $id_product, 'all'));
        };

        return $files;
    }

    public static function getFirstFile($id_product)
    {
        $files = self::getFiles($id_product);

        return (isset($files[0])) ? $files[0] : array('src' => 'no-image', 'ext' => 'jpg');
    }

    static function getIdProductByUrl($link_rewrite, $id_lang)
    {
        return dibi::fetchSingle("
		SELECT
		    id_product
		FROM
		    [product] JOIN [product_lang] USING(id_product)
		WHERE
		    link_rewrite = %s", $link_rewrite, "
		    AND id_lang = %i", $id_lang);
    }

    /*
     * product alternative
     */
    static function getProductAlternative($id_product, $sort = false, $limit = false)
    {

        return dibi::query("
			SELECT 
				id_product_alternative
			FROM 
				[product_alternative]
			WHERE 
				id_product = %i", $id_product, "
			%if", $sort, " ORDER BY " . $sort . "%end
			%if", $limit, " LIMIT " . $limit . "%end
			")->fetchPairs('id_product_alternative', 'id_product_alternative');;
    }

    static function getProductAlternativeValues($id_product, $id_lang, $user, $sort = false, $limit = false)
    {
        $alt = self::getProductAlternative($id_product, $sort, $limit);

        $return = array();

        foreach ($alt as $k => $id_product) {
            $return[] = self::getProductWithParams($id_product, $id_lang, $user);
        }

        return $return;
    }

    static function saveProductAlternative($id_product, $add_alternative)
    {
        self::deleteProductAlternative($id_product);
        foreach ($add_alternative as $id_product_alternative) {
            self::addProductAlternative($id_product, $id_product_alternative);
        }
    }

    static function deleteProductAlternative($id_product)
    {
        dibi::query("DELETE FROM [product_alternative] WHERE id_product = %i", $id_product);
    }

    static function addProductAlternative($id_product, $id_product_alternative)
    {
        dibi::query("INSERT INTO [product_alternative]", array('id_product_alternative' => $id_product_alternative, 'id_product' => $id_product));
    }



    /*
     * PRODUCT PARAMS MODEL
     */

    //cena sa upravi podla parametru - SHOW_PRICE_WITH_TAX
    //vstup je cena, a dan
    private static function _repairPriceForDB($price, $tax)
    {
        $show_price_with_tax = NEnvironment::getContext()->parameters['SHOW_PRICE_WITH_TAX'];
        if ($show_price_with_tax) {
            $price = $price / (1 + $tax / 100);
        }

        return $price;
    }

    private static function _repairPriceForView($price, $tax)
    {
        $show_price_with_tax = NEnvironment::getContext()->parameters['SHOW_PRICE_WITH_TAX'];
        if ($show_price_with_tax) {
            $price = $price * (1 + $tax / 100);
        }

        return $price;
    }

    static function addProductParamValue($values)
    {
        dibi::query("INSERT INTO [product_param]", $values);
    }

    static function deleteProductParamValue($id_product_param)
    {
        dibi::query("DELETE FROM [product_param] WHERE id_product_param = %i", $id_product_param);
    }

    static function deleteProductParamValues($id_product)
    {
        dibi::query("DELETE FROM [product_param] WHERE id_product = %i", $id_product);
    }

    static function setProductParamValue($values, $id_product_param)
    {
//		dump($values);exit;
        //uklada cenu podla nastavenia systemu
        //ak sa zadava do systemu cena s DPH, automaticky ju prerata na bez dph a ukladame vzdy bez dph

        //zisti product tax

        if (isset($values['price'])) {
            $values['price'] = str_replace(",", '.', $values['price']);

            $tax = dibi::fetchSingle("SELECT vat.value FROM
				[product] 
				JOIN [product_param] USING(id_product) 
				JOIN [vat] USING(id_vat)
				WHERE id_product_param = %i", $id_product_param);

            $values['price'] = self::_repairPriceForDB($values['price'], $tax);
        }
        dibi::query("UPDATE [product_param] SET ", $values, "WHERE id_product_param = %i", $id_product_param);
        self::repairSequenceProductParam($id_product_param);
//		echo dibi::$sql;
//		exit;
    }

    static function getFirstParam($id_product)
    {
        return dibi::fetch("SELECT * FROM [product_param] WHERE id_product = %i", $id_product, "LIMIT 1");
    }

    static function getTaxCoefForProduct($id_product)
    {
        //ak nie je spustene upravovanie cien, vrati koefiecient 1
        $show_price_with_tax = NEnvironment::getContext()->parameters['SHOW_PRICE_WITH_TAX'];
        if (!$show_price_with_tax)
            return 1;

        $key = 'getTaxCoefForProduct(' . $id_product . ')';

        $tax_coef = self::loadCache($key);

        if ($tax_coef) {
            return $tax_coef;
        } else {
            $tax = dibi::fetchSingle("SELECT vat.value FROM
			[product] 			
			JOIN [vat] USING(id_vat)
			WHERE id_product = %i", $id_product);

            $tax_coef = 1 + $tax / 100;

        }

        return self::saveCache($key, $tax_coef);
    }

    static function getProductParamDatasource($id_product)
    {
        //POZOR upravi cenu podla zadavania dane SHOW_PRICE_WITH_TAX
        $tax_coef = self::getTaxCoefForProduct($id_product);

        $product_param_cols = Tools::getCollum('product_param');
        unset($product_param_cols['price']);

//		dump($product_param_cols);exit;
        return dibi::dataSource("
			SELECT 
			%sql", implode(',', $product_param_cols), "
			, ROUND((price*" . $tax_coef . "),2) AS price
			FROM 
			[product_param] 
			WHERE %if", ($id_product == null), " id_product IS NULL %else id_product = %i", $id_product);
    }

    static function getProductParamFluent($id_product = null)
    {
        $f = dibi::select('*')
            ->from('product_param');

        if ($id_product != null)
            $f->where('id_product = %i', $id_product);

        return $f;
    }

    static function isParamValue($values)
    {
        return dibi::fetchSingle("SELECT 1 FROM [product_param] WHERE %and", $values);
    }

    //treba dorobit aby ak dame len id_product aby to upravilo
    static function repairSequenceProductParam($id_product_param = null, $id_product = null)
    {
        //ak nezadame nic spravi pre vsetky
        if ($id_product_param == null AND $id_product == null) {
            $list = dibi::fetchAll("SELECT id_product_param,id_product FROM [product_param]");
            foreach ($list as $l) {
                if ($l['id_product_param'] != null)
                    self::repairSequenceProductParam($l['id_product_param'], $l['id_product']);
            }
        } else {
            $s = 1;
            if ($id_product == null)
                $id_product = dibi::fetchSingle("SELECT id_product FROM [product_param] WHERE id_product_param = %i", $id_product_param);

            $list = dibi::fetchAll("SELECT sequence, id_product_param FROM [product_param] WHERE id_product = %i", $id_product, "ORDER BY sequence");
            foreach ($list as $l) {
                dibi::query("UPDATE [product_param] SET sequence = %i", $s++, "WHERE id_product_param = %i", $l['id_product_param']);
            }
        }
//		return dibi::dataSource("SELECT * FROM [product_param] WHERE id_product = %i",$id_product);
    }

    //karteziansky sucin, vstup musi mat kluc 0,1,2, inak NEFUNGUJE
    static function array_cartesian_product($arrays)
    {

        //returned array...
        $cartesic = array();

        //calculate expected size of cartesian array...
        $size = (sizeof($arrays) > 0) ? 1 : 0;
        foreach ($arrays as $array) {
            $size = $size * sizeof($array);
        }

        for ($i = 0; $i < $size; $i++) {
            $cartesic[$i] = array();

            for ($j = 0; $j < sizeof($arrays); $j++) {
                $current = @current($arrays[$j]);
                array_push($cartesic[$i], $current);
            }
            //set cursor on next element in the arrays, beginning with the last array
            for ($j = (sizeof($arrays) - 1); $j >= 0; $j--) {
                //if next returns true, then break
                if (next($arrays[$j])) {
                    break;
                } else {    //if next returns false, then reset and go on with previuos array...
                    reset($arrays[$j]);
                }
            }
        }

        return $cartesic;
    }

    static function getPrice($id_product_param, NUser $user = null)
    {
        $param = dibi::fetch("
			SELECT 
				price AS original_price, 
				sale_percent, 
				sale, 
				IF( sale = 1, price - (price/100*sale_percent), price) AS price,
				vat.value AS tax,
				id_product
			FROM 
				product_param 
				JOIN product USING(id_product) 
				JOIN vat USING(id_vat)
			WHERE 
				id_product_param = %i", $id_product_param);
//		echo dibi::$sql;
        if (!$param)
            throw new NBadRequestException('ID product param neexistuje.');
        //ak je user_discount > 0 a product nieje v akcii pouzije sa uzivatelska zlava

//		NDebug::dump($param);
        if ($user != null AND $user->isLoggedIn() AND $param['original_price'] == $param['price']) {
            $param['user_discount'] = $user->getIdentity()->discount;
            $param['price']         = $param['price'] / (1 + $param['user_discount'] / 100);
        }

        $param['price_with_tax'] = (1 + $param['tax'] / 100) * $param['price'];
        $param['tax_price']      = $param['price_with_tax'] - $param['price'];

        $tax_coef = self::getTaxCoefForProduct($param['id_product']);

        $param['price_showed']          = $tax_coef * $param['price'];
        $param['original_price_showed'] = $tax_coef * $param['original_price'];

        return $param;

    }

    static public function slugToId($slug)
    {

        $slug = rtrim($slug, '/');
        $key  = 'slugToId(' . $slug . ')';
        $id   = self::loadCache($key);
        if ($id) {
            return $id;
        } else {
            $id = dibi::fetchSingle("SELECT id_product FROM [product_lang] WHERE link_rewrite LIKE %s", $slug);
            if (!$id) $id = null;
        }

        return self::saveCache($key, $id);
    }

    static public function idToSlug($id)
    {

        $key = 'idToSlug(' . $id . ')';

        $slug = self::loadCache($key);

        if ($slug) {
            return $slug;
        } else {
            $name = dibi::fetchSingle("SELECT link_rewrite FROM [product_lang] WHERE id_product = %i", $id);
            if (!$name) $name = null;
        }

        return self::saveCache($key, $name);

    }

    /*
     * CACHE
     */

    static public function invalidateCache()
    {
        return self::getCache()->clean(array(
            NCache::TAGS => array(get_class())
        ));
    }

    static public function saveCache($key, $data)
    {
        $result = self::getCache()->save($key, $data, array(NCache::TAGS => array(get_class())));

//		self::getCache()->save($key, $data, array(
//			NCache::TAGS => array( get_class() )
//		));

        return $data;
    }

    static public function loadCache($key)
    {
        $cache = self::getCache();

        return $cache[$key];
    }

    static public function getCache()
    {
//		dump( NEnvironment::getCache( self::MODULE_NAME ));exit;
//		return NEnvironment::getService('MyMemcache');
//		$mem = new NMemcachedStorage('localhost', 11211, '', $journal);
////		$journal = NEnvironment::getContext('nette.cacheJournal');
//		
////		dump($mem);exit;
//		return new NCache($mem, self::MODULE_NAME);
//		return new NCache(new NDevNullStorage());
        return NEnvironment::getCache(self::MODULE_NAME);
    }
}