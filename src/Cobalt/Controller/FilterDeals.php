<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/

namespace Cobalt\Controller;

use Cobalt\Model\Deal as DealModel;
use Cobalt\Helper\ViewHelper;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class FilterDeals extends DefaultController
{
    public function execute()
    {
        //set view
        $view = ViewHelper::getView('deals','raw');
        $view->setLayout('list');

        //get filters
        $type = $this->input->get('type');
        $stage = $this->input->get('stage');
        $user = $this->input->get('user');
        $close = $this->input->get('close');
        $team = $this->input->get('team_id');

        //get deals
        $model = new DealModel;
        $deals = $model->getDeals(null,$type,$user,$stage,$close,$team);

        //assign references
        $view->deals = $deals;
        $view->pagination = $model->getPagination();

        //display
        echo $view->render();
    }

}
