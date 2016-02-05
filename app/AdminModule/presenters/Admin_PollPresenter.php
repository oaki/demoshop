<?php

/**
 * Description of Admin_PollPresenter
 *
 * @author oaki
 */
class Admin_PollPresenter extends Admin_BasePresenter {

	function  beforeRender() {
	    parent::beforeRender();

	    $this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    

		$this['header']['css']->addFile('../jscripts/jquery/datePicker/timepicker-cs.css');
		$this['header']['js']->addFile('/jquery/datePicker/timepicker-cs.js');
		$this['header']['js']->addFile('/tabella/jquery.datesupport.js');
		$this['header']['js']->addFile('/tabella/jquery.datepicker.js');
		$this['header']['js']->addFile('/tabella/nette.tabella.js');

	}

	/*
	 * HANDLES
	 */
	function handleAddPoll(NForm $form){
		$values = $form->getValues();
		$id_poll = PollModel::add($values);
		$this->flashMessage('Anketa bola pridaná');
		$this->redirect('Poll:edit', array('id'=>$id_poll));
	}

	function handleEditPoll(NForm $form){
		$values = $form->getValues();
		$id_poll = PollModel::edit($values, $values['id_poll']);
		$this->flashMessage('Anketa bola upravená');
		$this->redirect('Poll:edit', array('id'=>$values['id_poll']));
	}

	function handleDeletePoll($id){
		PollModel::delete($id);
		$this->flashMessage('Anketa bola zmazaná.');
		$this['PollTabella']->invalidateControl();
		$this->invalidateControl('flashMessage');
	}

	function handleAddEmptyAnswer($id_poll){
		$arr = array(
			'id_poll'=>$id_poll,
			'sequence'=>0
		);
		PollModel::addAnswer($arr);
		$this['AnswerTabella']->invalidateControl();
	}

	function handleDeletePollAnswer($id_poll_answer){
		PollModel::deleteAnswer($id_poll_answer);
		$this->flashMessage('Odpoveď bola zmazaná.');
		$this['AnswerTabella']->invalidateControl();
		$this->invalidateControl('flashMessage');
	}

	

	/*
	 * Actions
	 */

	function actionEdit($id){
		
	}

	function  createComponent($name) {
		 switch ($name){
			 case 'PollTabella':
				$grid = new Tabella( PollModel::getFluent()->toDataSource() ,
					array(
						'sorting'=>'desc',
						'order'=>'id_poll',
						'id_table'=>'id_poll',
						'onSubmit' => function( $values ) {
							PollModel::edit($values, $values['id_poll']);
						},
						'onDelete' => function( $id_poll ) {
							PollModel::delete($id_poll);
						}
					)
				);

				$grid->addColumn( "ID","id_poll", array( "width" => 30,'editable'=>true ) );
				$grid->addColumn( "Nadpis","title", array( "width" => 50,'editable'=>true ) );
				$grid->addColumn( "Otázka", "question",array( 'editable'=>true ) );
				$grid->addColumn( "Popis", "description ",array( "width" => 100 ) );
				$grid->addColumn( "Od","from_date", array( "width" => 100 ) );
				$grid->addColumn( "Do","to_date", array( "width" => 100 ) );

				$grid->addColumn("", "",
					array(
						"width" => 30,
						'filter'=>NULL,						
						"renderer" => function( $row ) {
							$el = NHtml::el( "td" );

							$el->add(
								NHtml::el( 'a' )->href(
									NEnvironment::getApplication()->getPresenter()->link( 'deletePoll!' , array('id'=>$row->id_poll))
								)->addClass( 'deleteIcon ajax' )
							);

							$el->add(
								NHtml::el( 'a' )->href(
									NEnvironment::getApplication()->getPresenter()->link( 'edit' , array('id'=>$row->id_poll))
									)->addClass( 'editIcon' )
								);

							$span = NHtml::el('span');
							$el->add($span);

							return $el;
						}
					)
				);

				$this->addComponent( $grid, $name );
			 break;

			case 'baseForm':				
				$f = new MyForm;
				$f->addText('title', 'Názov')->addRule(NForm::FILLED, 'Názov musí byť vyplnený');
				$f->addText("question", "Otázka");
				$f->addText("description", "Popis");
				$f->addDateTimePicker("from_date", "Od")->addRule(NForm::FILLED, 'Zadajte datum a čas.');
				$f->addDateTimePicker("to_date", "Do")->addRule(NForm::FILLED, 'Zadajte datum a čas.');
				
				return $f;
			break;

			case 'addForm':
				
				$f = $this->createComponent('baseForm');
				$f->addSubmit('btn', 'Pridať');
				$f->onSuccess[] = array($this, 'handleAddPoll');
				
				return $f;
				break;

			case 'editPollForm':

				$f = $this->createComponent('baseForm');
				$f->addHidden('id_poll');
				$f->addSubmit('btn', 'Upraviť');
				$f->onSuccess[] = array($this, 'handleEditPoll');

				$values = PollModel::get( $this->getParam('id') );
				$f->setDefaults($values);
				return $f;
				break;



			case 'AnswerTabella':
				$grid = new Tabella( PollModel::getAnswerFluent( $this->getParam('id') )->toDataSource() ,
					array(
						
						'sorting'=>'asc',
						'order'=>'sequence',
						'id_table'=>'id_poll_answer',
						'onSubmit' => function( $values ) {
							PollModel::editAnswer($values, $values['id_poll_answer']);
						},
						'onDelete' => function( $id_poll_answer ) {
							PollModel::deleteAnswer($id_poll_answer);
						}
					)
				);

				$el = NHtml::el( "div" );
					$el->add(
						NHtml::el( 'a' )->href(
							NEnvironment::getApplication()->getPresenter()->link( 'addEmptyAnswer!' , array('id_poll'=>$this->getPresenter()->getParam('id')))
						)->addClass( 'addIcon ajax' )
					);


				$grid->addColumn($el, 'sequence', array('width'=>10,  'filter'=>NULL, "editable" => true ) );

				//$grid->addColumn( "ID","id_poll_answer", array( "width" => 30,'editable'=>false,'filter'=>null ) );
				$grid->addColumn( "Odpoveď","answer", array( 'filter'=>null,"width" => 550,'editable'=>true ) );
//				$grid->addColumn( "Správna?", "correct", array(
//
//                    "options" => array( '0' => 'Nie', '1' => 'Áno' ),
//                    "width" => 50,
//                    "editable" => true,
//					)
//				);
				
				$grid->addColumn("", "",
					array(
						"width" => 30,
						'filter'=>NULL,
						"renderer" => function( $row ) {
							$el = NHtml::el( "td" );

							$el->add(
								NHtml::el( 'a' )->href(
									NEnvironment::getApplication()->getPresenter()->link( 'deletePollAnswer!' , array('id'=>NEnvironment::getApplication()->getPresenter()->getParam('id'),'id_poll_answer'=>$row->id_poll_answer))
								)->addClass( 'deleteIcon ajax' )
							);						

							$span = NHtml::el('span');
							$el->add($span);

							return $el;
						}
					)
				);

				$this->addComponent( $grid, $name );
			 break;
		 default:
			return parent::createComponent($name);
			 break;
		 }
	}//end createComponent
        
}