<?php
/**
 * GUI for Acl
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI for Acl
 */

/**
 * Blank Presenter, which redirects to UsersPresenter
 * 
 */
class Acl_HomepagePresenter extends Acl_BasePresenter
{
    public function startup() {
        $this->redirect('Users:Default');
    }
}
