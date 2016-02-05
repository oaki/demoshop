<?php

/**
 * Description of Admin_PictogramPresenter
 *
 * @author oaki
 */
class Admin_PictogramPresenter extends Admin_BasePresenter {

	public function renderDefault() {

		$files = new FilesNode( 'pictogram', 1);
		$this->template->fileNode = $files;

	}
        
}