<?php
/* 
 * @NAME@
 * 
 * Copyright (c) 2009 Jan Smitka <jan@smitka.org>
 * 
 * This source file is part of @NAME@.
 * You are permitted to use, copy, modify, and distribute this application
 * only with permission of the original author.
 * 
 * This application is powered by Nette Framework (http://nettephp.com),
 * developed by Nette Foundation (http://nettefoundation.com/)
 * 
 * @author Jan Smitka <jan@smitka.org>
 * @package @PACKAGE@
 * @copyright Copyright (c) 2009 Jan Smitka <jan@smitka.org>
 */


class MultiSelectDualList extends NMultiSelectBox
{
	public function __construct($label = NULL, array $items = NULL, $size = NULL, $all_items = NULL)
	{
		parent::__construct($label, $items, $size);
		$this->control->class = 'multiselect';
		
	}
}