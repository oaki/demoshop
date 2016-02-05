<?php
/**
 * My paginator 
 *
 * @author     Pavol Bincik
 * @copyright  Copyright (c) 2009 Pavol Bincik
 * @package    Nette Extras
 */
class MyPaginator extends NControl
{
	/** @var Paginator */
	private $paginator;
	
	/** @var url*/
	private $url = '';
	
	
	/* @var var name in URL*/
	private $var_name = 'page';
	
	/* @var aktualna strana*/
	public $page = 1;

	/** @var pocet poloziek na strane*/
	public $itemsPerPage;
	
	/** @var pocet vsetkych poloziek na strane*/
	public $itemCount = 0;
	
	/** @var datasource - query*/
	private $datasource = NULL;
	

	public function __construct(DibiDataSource $datasource, $itemsPerPage = 20){
		
		$this->datasource = $datasource;
		
		$this->itemsPerPage = $itemsPerPage;
		$this->getPaginator();
		
		parse_str($_SERVER['QUERY_STRING'], $vars);
		
		if(isset($vars[$this->var_name])){
			$this->paginator->setPage($vars[$this->var_name]);
		}	
		
		unset($vars[$this->var_name]);
			
		$this->url = $_SERVER['SCRIPT_NAME'].'?'.http_build_query($vars);
		
		$this->paginator->setItemsPerPage($this->itemsPerPage);
		
		$this->paginator->setItemCount( count($this->datasource) );
	}
	
	
	/**
	 * @return Nette\Paginator
	 */
	public function getPaginator()
	{
		if (!$this->paginator) {
			$this->paginator = new NPaginator;
		}
		return $this->paginator;
	}

	public function getDataSourceItem(){
		return $this->datasource->applyLimit($this->itemsPerPage, $this->paginator->offset);
	}

	/**
	 * Renders paginator.
	 * @return void
	 */
	public function render()
	{
		
		
		
		$page = $this->paginator->page;
		
		if ($this->paginator->pageCount < 2) {
			$steps = array($page);

		} else {
			$arr = range(max($this->paginator->firstPage, $page - 3), min($this->paginator->lastPage, $page + 3));
			$count = 4;
			$quotient = ($this->paginator->pageCount - 1) / $count;
			for ($i = 0; $i <= $count; $i++) {
				$arr[] = round($quotient * $i) + $this->paginator->firstPage;
			}
			sort($arr);
			$steps = array_values(array_unique($arr));
		}

		$template = new NTemplate();
		$template->registerFilter ( new NLatteFilter() );
		
		$template->steps = $steps;
		
		$template->url = $this->url;
		
		$template->var_name = $this->var_name;
		
		$template->paginator = $this->paginator;
		
		$template->setFile(dirname(__FILE__).'/template.phtml');
		
		return $template->render();
		
	}



	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->getPaginator()->page = $this->page;
	}

}