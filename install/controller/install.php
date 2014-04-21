<?php

class crmInstallController
{
    /** Validate database credentials **/
    public function validateDb()
    {
        //json
        $r = array();

        //connect
        $mysql = mysql_connect($_POST['host'], $_POST['user'], $_POST['pass']);

        //check mysql
        if (!$mysql) {

            $r['error'] = mysql_error();
            $r['valid'] = false;

        } else {

            //check database
            $db_selected = mysql_select_db($_POST['name'], $mysql);
            if (!$db_selected) {
                $r['valid'] = false;
                $r['error'] = mysql_error();
            } else {
                $r['valid'] = true;
            }

        }

        //close
        mysql_close($mysql);

        //return
        echo json_encode($r);

    }

    /** Install application **/
    public function install()
    {

        //load our installation model
        include_once(JPATH_BASE."/install/model/install.php");
        include_once('helpers/uri.php');
        
        $model = new crmInstallModel();
        if ( !$model->install() )
        {
            session_start();
            $_SESSION['error'] = $model->getError();
            header('Location: '.CURI::base());
        }

        // require_once JPATH_BASE . '/src/boot.php';

        // TODO login automatically
        // $app = JApplicationWeb::getInstance('cobalt',$model->getRegistry());
        // JFactory::$application = $app;
        // JFactory::$database = $model->getDb();
        // // Initialise the application.
        // $app->initialise();
        // $app->login($model->getAdmin());

        //EDIRECT TO ADMIN PAGE
        header('Location: '.CURI::base()."?view=cobalt");

    }

}
