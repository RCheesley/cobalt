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
use Cobalt\Table\MenuTable;

use Joomla\Model\AbstractModel;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class Menu extends AbstractModel
{
    public function store()
    {
        $app = \Cobalt\Container::fetch('app');

        //Load Tables
        $row = new MenuTable;
        $data = $app->input->getRequest( 'post' );

        //date generation
        $date = date('Y-m-d H:i:s');
        $data['modified'] = $date;

        //serialize menu items for storage
        $data['menu_items'] = serialize($data['menu_items']);

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

    public function getMenu()
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        $query->select("*")->from("#__menu")->where("id=1");
        $db->setQuery($query);

        $menu = $db->loadObject();
        $menu->menu_items = unserialize($menu->menu_items);

        return $menu;

    }

    public function getMenuTemplate()
    {
        return array('dashboard','deals','people','companies','calendar','documents','goals','reports');
    }

}
