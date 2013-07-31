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

use JFactory;
use Cobalt\Helper\TextHelper;

// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' );

class Remove extends DefaultController
{
    public function execute()
    {
        $app = JFactory::getApplication();
        $modelName = 'Cobalt\\Model\\'.ucwords($app->input->get('model'));
        $controllerName = $app->input->get('controller');

        $objectName = $app->input->get('model');

        $model = new $modelName();

        $ids = $app->input->get('id');

        if ( is_array($ids) ) {
            foreach ($ids as $id) {
                $model->remove($id);
            }
        } else {
            $model->remove($this->id);
        }

        $msg = TextHelper::_('COBALT_'.strtoupper($objectName).'_REMOVED');
        $app->redirect('index.php?view='.$controllerName,$msg);
    }
}
