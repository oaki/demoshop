<?
class MyHeaderControl extends HeaderControl{

	private $plain_jscripts = array();
	private $original_jscripts = array();

	private $original_css = array();
	
	private $texts = array();


	function addJscript( $jscript ){
		$this->plain_jscripts[] = $jscript;
	}

	function addJscriptFile( $file ){
		$this->original_jscripts[] = $file;
	}

	function addCssFile( $file ){
		$this->original_css[] = $file;
	}
	
	function addText( $text ){
		$this->texts[] = $text;
	}

	public function addKeywords($keywords){
		if($keywords == NULL OR $keywords == '')
			return $this;
		
		return parent::addKeywords($keywords);
	}
	
	public function renderEnd()
	{

		if( !empty($this->original_css) ){
			foreach( $this->original_css as $file){
				echo '<link href="'.$file.'" rel="stylesheet" type="text/css">';
			}
		}

		if( !empty($this->original_jscripts) ){
			foreach( $this->original_jscripts as $file){
				echo '<script type="text/javascript" src="'.$file.'"></script>';
			}
		}

		if( count($this->plain_jscripts) > 0 ){
			echo '<script type="text/javascript">';

			foreach($this->plain_jscripts as $js){
				echo $js."\n";
			}

			echo '</script>';
		}
		
		if( count($this->texts) > 0 ){
			foreach($this->texts as $t){
				echo $t."\n";
			}
		}

		echo NHtml::el('head')->endTag();
	}

}