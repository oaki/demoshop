<?php

/**
 * GUI Acl bootstrap file.
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI Acl
 */



/**
 * Privileges
 *
 */
class Acl_PrivilegesPresenter extends Acl_BasePresenter
{
    /**
     * Init method
     */
    public function startup() {
        parent::startup();
        $this->checkAccess();
    }

    /******************
     * Default
     ******************/
    public function renderDefault() {
        $sql = dibi::query('SELECT id, name, comment FROM ['.TABLE_PRIVILEGES.'] ORDER BY name;');
        $this->template->privileges = $sql->fetchAll();
    }

    /******************
     * Add and Edit
     ******************/
    public function actionAdd() {
    }
    public function actionEdit($id) {
        $sql = dibi::query('SELECT key_name, name, comment FROM ['.TABLE_PRIVILEGES.'] WHERE id=%i;', $id);
        $form = $this->getComponent('addEdit');
        if (count($sql)) {
            $form->setDefaults($sql->fetch());
        }
        else
            $form->addError('This privileg does not exist.');
    }
    protected function createComponentAddEdit($name) {
        $form = new NAppForm($this, $name);
        $renderer = $form->getRenderer();
        $renderer->wrappers['label']['suffix'] = ':';
        if (ACL_PROG_MODE) {
            $form->addText('name', 'Name', 30)
                ->addRule(NForm::FILLED, 'You have to fill name.')
                ->getControlPrototype()->onChange("create_key()");
        }
        else {
            $form->addText('name', 'Name', 30)
                ->addRule(NForm::FILLED, 'You have to fill name.');
        }
        //$form->addGroup('Edit');
        $form->addText('key_name', 'Key', 30)
            ->setDisabled((ACL_PROG_MODE ? false : true));
        $form->addTextArea('comment', 'Comment', 40, 4)
            ->addRule(NForm::MAX_LENGTH, 'Comment must be at least %d characters.', 250);
        if ($this->getAction()=='add')
            $form->addSubmit('add', 'Add');
        else
            $form->addSubmit('edit', 'Edit');
        $form->onSuccess[] = array($this, 'addEditOnFormSubmitted');
    }
    public function addEditOnFormSubmitted(NAppForm $form) {
        // add
        if ($this->getAction()=='add') {
            try {
                $values = $form->getValues();
                dibi::query('INSERT INTO ['.TABLE_PRIVILEGES.'] %v;', $values);
                $this->flashMessage('The privileg has been added.', 'ok');
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Privileges:');
            } catch (Exception $e) {
                $form->addError('The privileg has not been added.');
                throw $e;
            }
        }
        else { // edit
            try {
                $id = $this->getParam('id');
                $values = $form->getValues();
                dibi::query('UPDATE ['.TABLE_PRIVILEGES.'] SET %a WHERE id=%i;', $values, $id);
                $this->flashMessage('The privileg has been edited.', 'ok');
                if (ACL_CACHING AND ACL_PROG_MODE) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Privileges:');
            } catch (Exception $e) {
                $form->addError('The privileg has not been edited.');
                throw $e;
            }
        }
    }

    /******************
     * Delete
     ******************/
    public function actionDelete($id) {
        $sql = dibi::query('SELECT name FROM ['.TABLE_PRIVILEGES.'] WHERE id=%i;', $id);
        if (count($sql)) {
            $this->template->privilege = $sql->fetchSingle();
        }
        else {
            $this->flashMessage('This privilege does not exist.');
            $this->redirect('Privileges:');
        }
    }
    protected function createComponentDelete($name) {
        $form = new NAppForm($this, $name);
        $form->addSubmit('delete', 'Delete');
        $form->addSubmit('cancel', 'Cancel');
        $form->onSuccess[] = array($this, 'deleteOnFormSubmitted');
    }
    public function deleteOnFormSubmitted(NAppForm $form) {
        if ($form['delete']->isSubmittedBy()) {
            try {
                $id = $this->getParam('id');
                dibi::query('DELETE FROM ['.TABLE_PRIVILEGES.'] WHERE id=%i;', $id);
                $this->flashMessage('The privilege has been deleted.', 'ok');
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Privileges:');
            } catch (Exception $e) {
                $form->addError('The privilege has not been deleted.');
                throw $e;
            }
        }
        else
            $this->redirect('Privileges:');
    }
}
