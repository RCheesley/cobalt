<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/

namespace Cobalt\View\Note;

use JFactory;
use Joomla\View\AbstractHtmlView;

defined( '_CEXEC' ) or die( 'Restricted access' );

//Display partial views
class Phtml extends AbstractHtmlView
{
    public function render()
    {
        $app = JFactory::getApplication();
        $type = $app->input->getCmd('type');
        $id = $app->input->get('id');

        $this->object_id = $id;
        $this->item_type = $type;

        return parent::render();
    }
}
