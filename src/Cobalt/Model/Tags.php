<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/

namespace Cobalt\Model;

use Cobalt\Helper\DateHelper;
use Cobalt\Table\TagsTable;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class Tags extends DefaultModel
{
	public function store()
	{
		// Load Tables
		$row  = new TagsTable;
		$data = $this->app->input->post->getArray();

		// Date generation
		$date = DateHelper::formatDBDate('now');

		if (!array_key_exists('id', $data))
		{
			$data['created'] = $date;
		}

		$data['modified'] = $date;

		// Bind the form fields to the table
		try
		{
			$row->bind($data);
		}
		catch (\InvalidArgumentException $exception)
		{
			$this->app->enqueueMessage($exception->getMessage(), 'error');

			return false;
		}

		// Make sure the record is valid
		try
		{
			$row->check();
		}
		catch (\Exception $exception)
		{
			$this->app->enqueueMessage($exception->getMessage(), 'error');

			return false;
		}

		// Store the record
		try
		{
			$row->store();
		}
		catch (\Exception $exception)
		{
			$this->app->enqueueMessage($exception->getMessage(), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Get list of stages
	 *
	 * @param   integer  $id  Specific search id
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getTags($id = null)
	{
		$query = $this->db->getQuery(true);

		$query->select('*')
			->from('#__people_tags')
			->order($this->getState()->get('Tags.filter_order') . ' ' . $this->getState()->get('Tags.filter_order_Dir'));

		if ($id)
		{
			$query->where('t.id = ' . $id);
		}

		return $this->db->setQuery($query)->loadAssocList();
	}

	public function populateState()
	{
		//get states
		$filter_order     = $this->app->getUserStateFromRequest('Tags.filter_order', 'filter_order', 't.name');
		$filter_order_Dir = $this->app->getUserStateFromRequest('Tags.filter_order_Dir', 'filter_order_Dir', 'asc');

		//set states
		$this->getState()->set('Tags.filter_order', $filter_order);
		$this->getState()->set('Tags.filter_order_Dir', $filter_order_Dir);
	}

	public function remove($id)
	{
		$table = new TagsTable;
		$table->delete($id);
	}
}
