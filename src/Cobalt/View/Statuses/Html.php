<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/

namespace Cobalt\View\Statuses;

use JUri;
use JFactory;
use Joomla\View\AbstractHtmlView;
use Cobalt\Helper\UsersHelper;
use Cobalt\Helper\MenuHelper;
use Cobalt\Helper\Toolbar;
use Cobalt\Helper\ToolbarHelper;
use Cobalt\Helper\TextHelper;
use Cobalt\Model\Statuses as StatusesModel;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class Html extends AbstractHtmlView
{
    public function render()
    {
        //authenticate the current user to make sure they are an admin
        UsersHelper::authenticateAdmin();

        // Create toolbar
        $this->toolbar = new Toolbar;

        //document
        $document = JFactory::getDocument();
        $document->addScript(JURI::base().'src/Cobalt/media/js/cobalt-admin.js');

         /** Menu Links **/
        $menu = MenuHelper::getMenuModules();
        $this->menu = $menu;

        $layout = $this->getLayout();

        //gather information for view
        $model = new StatusesModel;
        $model->set("_layout",$layout);
        $this->pagination   = $model->getPagination();

        if ($layout && $layout == 'edit') {

            //toolbar buttons
            $this->toolbar->cancel();
            $this->toolbar->save();

            //javascripts
            $document->addScript(JURI::base().'src/Cobalt/media/js/bootstrap-colorpicker.js');

            //stylesheets
            $document->addStylesheet(JURI::base().'src/Cobalt/media/css/bootstrap-colorpicker.css');

            //get status
            $this->status = $model->getStatus();

            //script declarations
            if ($this->status['color'] != null) {
                $document->addScriptDeclaration('var status_color = "'.$this->status['color'].'";');
            } else {
                $document->addScriptDeclaration('var status_color = "ff0000";');
            }

        } else {

            //buttons
            $this->toolbar->addNew();
            ToolbarHelper::editList('edit');
            $this->toolbar->addDeleteRow();

            //statuses
            $statuses = $model->getStatuses();
            $this->statuses  = $statuses;

            // Initialise state variables.
            $state = $model->getState();
            $this->state = $state;

            $this->listOrder  = $this->state->get('Statuses.filter_order');
            $this->listDirn   = $this->state->get('Statuses.filter_order_Dir');
            $this->saveOrder  = $this->listOrder == 'c.ordering';

        }

        //display
        return parent::render();
    }
}
