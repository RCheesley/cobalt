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

use JFactory;
use Cobalt\Table\StagesTable;
use Joomla\Registry\Registry;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class Stages extends DefaultModel
{
    public $_view = "stages";

    public function store()
    {
        $app = \Cobalt\Container::fetch('app');

        //Load Tables
        $row = new StagesTable;
        $data = $app->input->getRequest( 'post' );

        //date generation
        $date = date('Y-m-d H:i:s');

        if ( !array_key_exists('id',$data) ) {
            $data['created'] = $date;
        }

        $data['modified'] = $date;
        $data['color'] = str_replace("#","",$data['color']);
        $data['won'] = array_key_exists('won',$data) ? 1 : 0;

        // Bind the form fields to the table
        if (!$row->bind($data)) {
            $this->setError($this->db->getErrorMsg());

            return false;
        }

        // Make sure the record is valid
        if (!$row->check()) {
            $this->setError($this->db->getErrorMsg());

            return false;
        }

        // Store the web link table to the database
        if (!$row->store()) {
            $this->setError($this->db->getErrorMsg());

            return false;
        }

        return true;
    }

    public function _buildQuery()
    {
        //database
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        //query
        $query->select("s.*");
        $query->from("#__stages AS s");

        //sort
        $query->order($this->getState('Stages.filter_order') . ' ' . $this->getState('Stages.filter_order_Dir'));

        return $query;

    }

    /**
     * Get list of stages
     * @param  int   $id specific search id
     * @return mixed $results results
     */
    public function getStages($id=null)
    {
        //database
        $db = JFactory::getDBO();
        $query = $this->_buildQuery();

        //return results
        $db->setQuery($query);

        return $db->loadAssocList();

    }

    public function getStage($id=null)
    {
        $id = $id ? $id : $this->id;

        if ($id > 0 && $id != null) {

            //database
            $db = JFactory::getDBO();
            $query = $this->_buildQuery();

            $query->where("id=".$id);

            //return results
            $db->setQuery($query);

            return $db->loadAssoc();

        } else {
            return (array) new StagesTable;

        }

    }

    public function populateState()
    {
        //get states
        $app = \Cobalt\Container::fetch('app');
        $filter_order = $app->getUserStateFromRequest('Stages.filter_order','filter_order','s.name');
        $filter_order_Dir = $app->getUserStateFromRequest('Stages.filter_order_Dir','filter_order_Dir','asc');

        $state = new Registry;

        //set states
        $state->set('Stages.filter_order', $filter_order);
        $state->set('Stages.filter_order_Dir',$filter_order_Dir);

        $this->setState($state);
    }

    public function remove($id)
    {
        //get dbo
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        //delete id
        $query->delete('#__stages')->where('id = '.$id);
        $db->setQuery($query);
        $db->query();
    }

}
