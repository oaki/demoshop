<?php

class CategoryModel extends NObject
{

    public $id_lang;

    function __construct($id_lang)
    {
        $this->id_lang = $id_lang;
    }

    static function get($id_category, $id_lang)
    {
        $key   = 'get(' . $id_category . ',' . $id_lang . ')';
        $value = self::getCache($key);
        if ($value) {
            return $value;
        }

        $value               = self::getFluent()->where('id_category = %i', $id_category, 'AND id_lang = %i', $id_lang)->fetch();
        $value['files']      = FilesNode::getAllFiles('category', $id_category);
        $value['first_file'] = (isset($value['files'][0])) ? $value['files'][0] : array('src' => 'no-image', 'ext' => 'jpg');

        return self::setCache($key, $value);

    }

    /*
     * Opravenie poradia
     */
    function repairSequence($id_parent = null)
    {
        $list = self::getDatasource()->where("id_lang = %i", $this->id_lang, "AND %if", $id_parent == null, " id_parent IS NULL %else  id_parent = %i", $id_parent)->orderBy("sequence")->fetchAll();
        //echo dibi::$sql;exit;
        $i = 0;
        foreach ($list as $l) {
            $this->save(array('sequence' => $i++), $l['id_category']);
            //		echo dibi::$sql;
            $this->repairSequence($l['id_category']);
        }
    }

    function generateTreeToSelect(&$return = null, $id_parent = null, $level = 0, $ignore_category = array())
    {

        $list = self::getDatasource()->where("id_lang = %i", $this->id_lang, "%if", $id_parent == null, "AND id_parent IS NULL %else AND id_parent = %i", $id_parent, "%if", $ignore_category, "AND id_parent != %i", $ignore_category)->fetchAll();

        foreach ($list as $l) {
            $pom = '';
            for ($i = 0; $i < $level * 3; ++$i)
                $pom .= '&nbsp;';

            $return[$l['id_category']] = NHTML::el('option')->setHtml($pom . $l['name'])->setValue($l['id_category']);

            $this->generateTreeToSelect($return, $l['id_category'], $level + 1);
        }

//	    return $return;
    }

    /*
     function generateTreeToSelectNoHTMLElement($return = null, $id_parent = NULL, $level = 0){

         $list = self::getDatasource()->where("id_lang = %i", $this->id_lang,"%if",$id_parent==NULL,"AND id_parent IS NULL %else AND id_parent = %i", $id_parent)->fetchAll();

         foreach( $list as $l ){
             $pom='';
             for($i=0;$i<$level*3;++$i)
                 $pom.='&nbsp;';

             $return[$l['id_category']] = NHtml::el('span')->setHtml($pom.$l['name']);

             $this->generateTreeToSelectNoHTMLElement(&$return, $l['id_category'], $level+1);
         }

 //	    return $return;
     }
 */
    static function getDatasource()
    {
        return dibi::dataSource("
			SELECT
			*, id_category AS id
			FROM
			[category]
			LEFT JOIN [category_lang] USING(id_category)
		");
    }

    static function getTreeCheckProduct($id_product = null)
    {
        return dibi::dataSource("
			SELECT
			*, id_category AS id
			%if", $id_product, "
				,
			IF (
				(SELECT
				1
				FROM
				[category_product]
				WHERE
				id_product = %i", $id_product, "AND category_product.id_category = id LIMIT 1) = 1,
				1,
				0
			) AS cb
			%end
			FROM
			[category]
			LEFT JOIN [category_lang] USING(id_category)


		");
    }

    function save($values, $id_category)
    {

        if (isset($values['id_parent'])) {
            $values['id_parent%iN'] = @$values['id_parent'];
            unset($values['id_parent']);
        }

        dibi::query("
			UPDATE [category]
			LEFT JOIN [category_lang] USING(id_category)
			SET", $values, "
			WHERE
			id_lang = %i", $this->id_lang, " AND id_category = %i", $id_category);
        //	echo dibi::$sql;
        //	exit;
        self::invalidateCache();
    }

    static function add($values)
    {

        $sequence = dibi::fetchSingle("SELECT sequence FROM [category]
			WHERE 
			%if", $values['id_parent'] != '',
            "id_parent = %i", $values['id_parent'],
            "%else id_parent IS NULL %end ORDER BY sequence DESC");
        $sequence++;

        $values['sequence'] = $sequence;

        if ($values['id_parent'] == '')
            unset($values['id_parent']);

        dibi::query("INSERT INTO [category]", $values);

        self::invalidateCache();

        return dibi::insertId();
    }

    static function addCategoryLang($values)
    {
        dibi::query("INSERT INTO [category_lang]", $values);
    }

    static function delete($id_category)
    {
        dibi::query("DELETE FROM [category] WHERE id_category = %i", $id_category);
        dibi::query("DELETE FROM [category_lang] WHERE id_category = %i", $id_category);
        self::invalidateCache();
    }

    static function getFluent($select = '*')
    {
        return dibi::select($select)
            ->from('category')
            ->leftJoin('category_lang')->using('(id_category)');

    }

    function moveDown($id_category)
    {
        $s = self::getFluent()->where('id_category = %i', $id_category)->fetch();

        self::save(array('sequence' => $s['sequence'] + 1.1), $id_category);

        $this->repairSequence();

        self::invalidateCache();

    }

    function moveUp($id_category)
    {
        $s = self::getFluent()->where('id_category = %i', $id_category)->fetch();

        self::save(array('sequence' => $s['sequence'] - 1.1), $id_category);

        $this->repairSequence();

        self::invalidateCache();
    }

    /*
     * todo dorobit vnorovanie kategorie
     */
//    static function getUrl($id_category, $id_lang){
//
//		$l = self::getFluent()->where('id_category = %i', $id_category,'AND id_lang = %i', $id_lang)->fetch();
//		return $l['link_rewrite'];
//    }

    static function getTreeAssoc($id_lang)
    {

        $cache = self::getCache('tree_assoc');

        if ($cache) {
            return $cache;
        } else {
            $tree = self::getFluent()->where('id_lang = %i', $id_lang)->orderBy('sequence')->fetchAssoc('id_parent,#');
            foreach ($tree as $k1 => $t1) {
                foreach ($t1 as $k2 => $category) {
                    $tree[$k1][$k2]['parents'] = self::getParents($category['id_category'], $id_lang);
                }
            }

            return self::setCache('tree_assoc', $tree);
        }
    }

    static function getTree($id_lang)
    {
        $cache = self::getCache('tree');
        if ($cache) {
            return $cache;
        } else {
            return self::setCache('tree', self::getFluent()->where('id_lang = %i', $id_lang)->orderBy('sequence')->fetchAssoc('id_category'));
        }
    }

    //vratit rodicov
    static function getParents($id_category, $id_lang)
    {
        $tree    = self::getTree($id_lang);
        $parents = array();

        return self::doParents($id_category, $tree);
    }

    static function doParents($id_category, $tree, &$parents = array())
    {

        if (isset($tree[$id_category])) {
            $parents[] = $id_category;
            if ($tree[$id_category]['id_parent'] != null) {
                self::doParents($tree[$id_category]['id_parent'], $tree, $parents);
            }
        }

        return $parents;
    }

    static function getUrl($id_category, $id_lang = 1)
    {
        $pom = self::getParents($id_category, $id_lang);

        $tree = self::getTree($id_lang);

        $pom = array_reverse($pom);
//		print_r($pom);
        $return = array();
        foreach ($pom as $k => $i)
            $return[$k] = NStrings::webalize($tree[$i]['name']);

        return implode('/', $return);
    }

    static function invalidateCache()
    {
        $cache = self::getCache();
        $cache->clean(array(NCache::TAGS => array('category')));
    }

    static function getCache($key = false)
    {
        $cache = NEnvironment::getCache('Category');

        if ($key) {
            if (isset($cache[$key])) {
                return $cache[$key];
            } else {
                return false;
            }
        }

        return $cache;
    }

    static function setCache($key, $data)
    {
        $cache = self::getCache();
        $cache->save($key, $data,
            array(NCache::TAGS => array('category'))
        );

        return $data;
    }

    static function repairCategoryRewriteLink($id_lang = 1)
    {
        self::invalidateCache();
        $tree = self::getTree($id_lang);
//		print_r($tree);exit;
        foreach ($tree as $k => $l) {
            dibi::query("
				UPDATE
					[category_lang]
				SET
					link_rewrite = %s", self::getUrl($l['id_category']),
                "WHERE id_category = %i", $l['id_category']);
        }
    }

    static function getIdCategoryFromUrl($link_rewrite, $id_lang = 1)
    {
        return dibi::fetchSingle("SELECT id_category FROM [category_lang] WHERE link_rewrite = %s", $link_rewrite, "AND id_lang = %i", $id_lang);
    }

    public static function slugToId($slug)
    {

        $slug = rtrim($slug, '/');

        $cache = self::getCache();

        $key = 'slugToId(' . $slug . ')';

        if ($id = self::getCache($key)) {

        } else {
            $id = dibi::fetchSingle("SELECT id_category FROM [category_lang] WHERE link_rewrite LIKE %s", $slug);
            if (!$id) $id = 'empty';
            self::setCache($key, $id);
        }

        if ($id === 'empty')
            $id = null;

        return $id;
    }

    public static function idToSlug($id)
    {
        $cache = self::getCache();

        $key = 'idToSlug(' . $id . ')';

        if (isset($cache[$key])) {
            return $cache[$key];
        } else {
            $name = dibi::fetchSingle("SELECT link_rewrite FROM [category_lang] WHERE id_category = %i", $id);
            if (!$name) $name = null;
//			$cache->save( $key, $name);
            self::setCache($key, $name);

            return $name;
        }
    }
}