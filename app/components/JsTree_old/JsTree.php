<?php
/**
 *
 * JsTree control
 *
 * @copyright  Copyright (c) 2010 Josef Kříž
 * @author     Josef Kříž
 * @license    GPLv3
 * 
 */


class JsTree_old extends NControl {

	/** @var string */
	public $keyColumn = "id";

	/** @var string */
	public $parentColumn = "depend";

	/** @var string */
	public $orderColumn = "order";

	/** @var event */
	public $onFormatCheckbox = array();

	/** @var string */
	public $checkboxColumn;

	/** @var string */
    public $table;

	/** @var string */
    public $titleColumn;

	/** @var string */
    public $numberingFrom = 1;

	/** @var array */
    public $where = "";

	/** @var array */
    public $defaultValues = Null;

	/** @var event */
	public $onAfterMove;

	/** @var event */
	public $onAfterDelete;

	/** @var event */
	public $onAfterUpdate;

	/** @var event */
	public $onAfterCreate;

	/** @var event */
	public $onMoveOwn;

	/** @var event */
	public $onDeleteOwn;

	/** @var event */
	public $onUpdateOwn;

	/** @var event */
	public $onCreateOwn;

	/** @var event */
	public $onCheckOwn;

	/** @var event */
	public $onFormatItem;

	/** @var event */
	public $onDataOwn;

	/** @var event */
	public $onClick;

	/** @var bool */
	public $showRoot = false;

	/** @var string */
	public $rootTitle = "root";

	/** @var bool */
	public $ajaxLink = true;

	/** @var bool */
	public $openAll = false;

	/** @var bool */
	public $enableCheckbox = false;

	/** @var bool */
	public $enableContextmenu = false;

	/** @var bool */
	public $enableDragDrop = false;

	/** @var bool */
	public $enableDragDropOrder = false;

	public function handleGetData(){
		$this->template->ajax = true;
		//$this->template->presenter = $this;
		$this->render();
//		NDebug::disableProfiler();
		NEnvironment::getApplication()->getPresenter()->terminate();
	}
	
	public function getData($parent = Null){
		if($parent == 0) $parent = NULL;

		if($this->where) $where = $this->where;
		else $where = array();
		$where[] = array("`{$this->parentColumn}`=%i", $parent);

		$list = dibi::query("
			SELECT *
			FROM `".$this->table."` JOIN [category_lang] USING(id_category)
			WHERE id_lang = 1 %and", $where, " ORDER BY `".$this->orderColumn."`")->fetchAssoc( $this->keyColumn);
		
		return $list;
	}

	public function handleSaveData($id, $ref, $position){
		//die($id . " - " . (int)$ref . " - ". $position);
		$position = $position + $this->numberingFrom;
		$ref = (int)$ref;

		if(count($this->onMoveOwn)>0){
			$this->onMoveOwn($id, $ref, $position);
		}else{
		    
			if($this->enableDragDropOrder){
			    
                                $old = dibi::query("SELECT * FROM `{$this->table}` WHERE `{$this->keyColumn}`=%i", $id)->fetch();
                                dibi::query("UPDATE `{$this->table}` SET `{$this->orderColumn}` = `{$this->orderColumn}`-1 WHERE `{$this->parentColumn}` = %i ", $old->{$this->parentColumn}, " AND `{$this->orderColumn}` > %i", $old->{$this->orderColumn});
				
                                dibi::query("UPDATE `{$this->table}` SET `{$this->orderColumn}` = `{$this->orderColumn}`+1 WHERE `{$this->parentColumn}` = %i ", $ref, " AND `{$this->orderColumn}` >= %i ", $position);
                                dibi::query("UPDATE `{$this->table}` SET `{$this->parentColumn}` = %i", $ref, ", `{$this->orderColumn}` = %i", ($position), " WHERE `{$this->keyColumn}` = %i", $id);
                        }

                        $this->onAfterMove($id);

		}
		die("ok");
	}

	public function handleCreateData($id, $position, $title, $type){
		$position = $position + $this->numberingFrom;

		if(count($this->onCreateOwn)>0){
			$this->onCreateOwn($id, $position, $title, $type, $ret);
		}else{
			$data = $this->defaultValues;
			$data[$this->orderColumn] = $position;
			$data[$this->parentColumn] = (int)$id;
			$data[$this->titleColumn] = $title;

			dibi::query("INSERT INTO `{$this->table}` ", $data);
			$id = dibi::getInsertId();
			$this->onAfterCreate($id);
		}
		die($id."");
	}

	public function deleteItem($id){
		$data = dibi::query("SELECT * FROM `{$this->table}` WHERE `{$this->parentColumn}`=%i", $id)->fetchAll();
		foreach($data as $item){
			$this->deleteItem($item->{$this->keyColumn});
		}
		dibi::query("DELETE FROM `{$this->table}` WHERE `{$this->keyColumn}`=%i LIMIT 1", $id);
	}

	public function handleDeleteData($id){
		if(count($this->onDeleteOwn)>0){
			$this->onDeleteOwn($id);
		}else{
			$this->deleteItem($id);
			$this->onAfterDelete($id);
		}
		die("ok");
	}

	public function handleUpdateData($id, $title){
		if(count($this->onUpdateOwn)>0){
			$this->onUpdateOwn($id);
		}else{
			dibi::query("UPDATE `{$this->table}` SET `{$this->titleColumn}`=%s WHERE `{$this->keyColumn}`=%i LIMIT 1", $title, $id);
			$this->onAfterUpdate($id);
		}
		die("ok");
	}

	public function handleClick($id, $type){
		$this->invalidateControl();
		$this->onClick($id, $type);
	}

	public function formatItem($key){
		foreach($this->template->data[$key] as $item){
			$this->onFormatItem($item);
			if(isset ($this->template->data[$item->id])) $this->formatItem($item->id);
		}
	}

	public function render() {
		$this->template->setFile(dirname(__FILE__) . '/template.phtml');
		$this->template->name = $this->getUniqueId();
		$this->template->titleColumn = $this->titleColumn;
		$this->template->keyColumn = $this->keyColumn;
		$this->template->checkboxColumn = $this->checkboxColumn;
		$this->template->enableCheckbox = $this->enableCheckbox;
		$this->template->enableContextmenu = $this->enableContextmenu;
		$this->template->enableDragDrop = $this->enableDragDrop;
		$this->template->showRoot = $this->showRoot;
		$this->template->ajaxLink = $this->ajaxLink;
		$this->template->openAll = $this->openAll;
		$this->template->rootTitle = $this->rootTitle;
		//$this->template->presenter = $this;
		if($this->where) $where = $this->where;
		else $where = array();
		if(isset ($this->template->ajax)){
			$this->template->data = dibi::query("
			    SELECT *
			    FROM `".$this->table."` JOIN [category_lang] USING(id_category)
			    WHERE
				id_lang = 1 AND
			    %and", $where, " ORDER BY `".$this->orderColumn."`")->fetchAssoc("{$this->parentColumn},{$this->orderColumn}");
			
			if(count($this->onFormatItem)>0){
				$this->formatItem(0);
			}
		}
		
		$this->template->render();
	}

}