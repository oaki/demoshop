<?php

class AttachmentControl extends BaseControl{
	
	public $disallow_files = array('jpg','gif','png');
	public $images_files = array('jpg','gif','png');
	public $icon_dir, $abs_icon_dir;
	public $dimensions = array(
		'thumb'=>array(
			'width'=>150,
			'height'=>120,
			'flag'=>5
			),
		'big'=>array(
			'width'=>800,
			'height'=>600,
			'flag'=>1
		)
	);
	
	function __construct(IComponentContainer $parent = NULL, $name = NULL) {
		parent::__construct($parent, $name);
		
		$this->icon_dir  = '/images/attachment/ico-';
		$this->abs_icon_dir  = WWW_DIR.'/images/attachment/ico-';
	}
	
	function renderFiles( array $files){
		$template = $this->template;
		
		foreach($files as $k=>$f){
			if( in_array( $f['ext'], $this->disallow_files ) )
					unset( $files[$k] );
		}		
		
		$template->files = $files;

		$template->setFile(dirname(__FILE__) . '/files.latte');	
			
		$template->render();		
	}
	
	function renderImages( array $files, $id = NULL, array $dimension = NULL ){
		$template = $this->template;
		
		
		$template->id = $id;
		
		if($dimension == NULL)
			$dimension = $this->dimensions;
		
		$template->dimension = $dimension;
		
		foreach($files as $k=>$f){
			if( !in_array( $f['ext'], $this->images_files ) )
					unset( $files[$k] );
		}		
		
		$template->files = $files;

		$template->setFile(dirname(__FILE__) . '/images.latte');	
			
		$template->render();		
	}
	
	
}