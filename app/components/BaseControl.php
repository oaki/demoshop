<?php

/**
 * Description of BaseControl
 *
 * @author oaki
 */
class BaseControl extends NControl {

	public function createTemplate($class = NULL)
        {
            $template = parent::createTemplate($class);
			
//			$t = new MyTranslator($this->getPresenter()->lang); // provede základní nastavení pro šablony apod.
            $template->setTranslator( $this->getService('translator') );


			// formatovanie cisliel v templajtoch
			$template->registerHelperLoader('FormatHelper::loadHelper');

			$template->registerHelperLoader('ImageHelper::loadHelper');
			
            return $template;
        }

	public function getService($name, $options = NULL){
		return $this->getPresenter()->getService($name, $options);
	}

	protected function createComponentMsg($name) {
		return new MsgControl($this, $name);
	}
}