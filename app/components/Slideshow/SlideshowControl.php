<?php

class SlideshowControl extends BaseControl {
	const MODULE_NAME = 'slideshow';
	
	public function render($id_node)
	{
		$slide = SlideShowModel::init();
		
		$template = $this->template;
		$template->slideshow = $slide->get($id_node);
//		NDebug::barDump($template->slideshow);
		$template->setFile(dirname(__FILE__) . '/Slideshow.phtml');
			
		$template->render();
	}
	
}
