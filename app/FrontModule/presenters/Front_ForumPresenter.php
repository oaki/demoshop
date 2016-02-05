<?php

/**
 * Description of Front_ForumPresenter
 *
 * @author oaki
 */
class Front_ForumPresenter extends Front_BasePresenter {


	public function actionDefault($id, $id2)
   {
   }

	public function renderDefault() {
		$this['header']['css']->addFile('forum.css');
	}

	 /**
    * Forum Control component
    *
    * @access protected
    * @return ForumControl
    * @since 1.0.0
    */
   protected function createComponentForumControl()
   {
     $forumId = 1; // 1 = forum ID from table "forum"
     $model = new ForumControlModel($forumId, $this->getService('dibi'));

     // Params mapping
     $params = array(
       'topicId' => $this->getParam('id'),
       'allTopics' => $this->getParam('id2'),
       'selectedTopicsIds' => array('name' => 'o', 'value' => $this->getParam('o'))
     );

     return new ForumControl($this->context, $model, $params);
   }
}