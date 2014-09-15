<?php
/*------------------------------------------------------------------------
# Cobalt
# ------------------------------------------------------------------------
# @author Cobalt
# @copyright Copyright (C) 2012 cobaltcrm.org All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.cobaltcrm.org
-------------------------------------------------------------------------*/
// no direct access
defined( '_CEXEC' ) or die( 'Restricted access' ); ?>

<div class="page-header">

    <div class="modal fade" id="personModal" tabindex="-1" role="dialog" aria-labelledby="personModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    <div class="btn-group pull-right">
        <a rel="tooltip" title="<?php echo TextHelper::_('COBALT_PEOPLE_ADD'); ?>" data-placement="bottom" class="btn btn-success" role="button" href="<?php echo RouteHelper::_('index.php?view=people&layout=edit&format=raw&tmpl=component'); ?>" data-target="#personModal" data-toggle="modal"><i class="glyphicon glyphicon-plus icon-white"></i></a>
        <a rel="tooltip" title="<?php echo TextHelper::_('COBALT_IMPORT_PEOPLE'); ?>" data-placement="bottom"  class="btn btn-default" href="<?php echo RouteHelper::_('index.php?view=import&import_type=companies'); ?>"><i class="glyphicon glyphicon-circle-arrow-up"></i></a>
        <?php if ( UsersHelper::canExport() ) { ?>
        <a rel="tooltip" title="<?php echo TextHelper::_('COBALT_EXPORT_PEOPLE'); ?>" data-placement="bottom" class="btn btn-default" href="javascript:void(0)" onclick="exportCsv()"><i class="glyphicon glyphicon-share"></i></a>
        <?php } ?>
    </div>

    <h1><?php echo ucwords(TextHelper::_('COBALT_PEOPLE_HEADER')); ?></h1>
</div>
<ul class="list-inline filter-sentence">
    <li><span><?php echo TextHelper::_('COBALT_SHOW'); ?></span></li>
    <li class="dropdown">
        <a class="dropdown-toggle update-toggle-text" data-toggle="dropdown" role="button" id="people_type_link" href="javascript:void(0);"><span class="dropdown-label"><?php echo $this->people_type_name; ?><span></a>
        <ul class="dropdown-menu" role="menu" aria-labelledby="people_type_link">
            <?php foreach ($this->people_types as $title => $text) {
            echo "<li><a href='javascript:void(0);' class='filter_".$title."' onclick=\"companyType('".$title."')\">".$text."</a></li>";
            }?>
        </ul>
    </li>
    <li><span><?php echo TextHelper::_('COBALT_OWNED_BY'); ?></span></li>
    <li class="dropdown">
        <a class="dropdown-toggle update-toggle-text" href="javascript:void(0);" data-toggle="dropdown" role="button" id="people_user_link"><span class="dropdown-label"><?php echo $this->user_name; ?></span></a>
        <ul class="dropdown-menu update-toggle-text" role="menu" aria-labelledby="people_user_link">
            <li><a href="javascript:void(0);" class="filter_user_<?php echo $this->user_id; ?>" onclick="peopleUser(<?php echo $this->user_id; ?>,0)"><span class="dropdown-label"><?php echo TextHelper::_('COBALT_ME'); ?><span></a></li>
            <?php if ($this->member_role != 'basic') { ?>
                 <li><a href="javascript:void(0);" class="filter_user_all" onclick="peopleUser('all',0)"><?php echo TextHelper::_('COBALT_ALL_USERS'); ?></a></li>
            <?php } ?>
            <?php
                if ($this->member_role == 'exec') {
                    if ( count($this->teams) > 0 ) {
                        foreach ($this->teams as $team) {
                             echo "<li><a href='javascript:void(0);' class='filter_team_".$team['team_id']."' onclick='peopleUser(0,".$team['team_id'].")'>".$team['team_name'].TextHelper::_('COBALT_TEAM_APPEND')."</a></li>";
                         }
                    }
                }
                if ( count($this->users) > 0 ) {
                    foreach ($this->users as $user) {
                        echo "<li><a href='javascript:void(0);' class='filter_user_".$user['id']."' onclick='peopleUser(".$user['id'].")'>".$user['first_name']."  ".$user['last_name']."</a></li>";
                    }
                }
            ?>
        </ul>
    </li>
    <li><span><?php echo TextHelper::_('COBALT_WHO'); ?></span></li>
    <li class="dropdown">
        <a class="dropdown-toggle update-toggle-text" href="javascript:void(0);" data-toggle="dropdown" role="button" id="people_stages_link"><span class="dropdown-label"><?php echo $this->stages_name; ?></span></a>
        <ul class="dropdown-menu" role="menu" aria-labelledby="people_stages_link">
            <?php foreach ($this->stages as $title => $text) {
                echo "<li><a href='javascript:void(0);' class='filter_".$title."' onclick=\"peopleUpdated('".$title."')\">".$text."</a></li>";
            }?>
        </ul>
    </li>
    <li><span><?php echo TextHelper::_('COBALT_AND_WITH_STATUS'); ?></span></li>
    <li class="dropdown">
        <a class="update-toggle-text dropdown-toggle" href="javascript:void(0);" data-toggle="dropdown" role="button" id="people_status_link"><span class="dropdown-label"><?php echo $this->status_name; ?></span></a>
        <ul class="dropdown-menu" role="menu" aria-labelledby="people_status_link">
            <li><a class="filter_any" onclick="peopleStatus('any')"><?php echo TextHelper::_('COBALT_ANY_STATUS'); ?></a></li>
            <?php
                foreach ($this->status_list as $key => $status) {
                    echo "<li><a href='javascript:void(0);' class='filter_".$status['id']."' onclick='peopleStatus(".$status['id'].")'>".$status['name']."</a></li>";
                }
            ?>
        </ul>
    </li>
    <li>
        <span><?php echo TextHelper::_('COBALT_NAMED'); ?></span>
    </li>
    <li>
        <input class="form-control filter_input" name="name" type="text" placeholder="<?php echo TextHelper::_('COBALT_ANYTHING'); ?>" value="<?php echo $this->people_filter; ?>">
    </li>
    <li class="filter_sentence">
        <div class="ajax_loader"></div>
    </li>
</ul>

<?php echo TemplateHelper::getListEditActions(); ?>
<form method="post" id="list_form" action="<?php echo RouteHelper::_('index.php?view=people'); ?>">
    <table class="table table-striped table-hover data-table" id="people">
        <?php echo $this->people_list->render(); ?>
    </table>
    <input type="hidden" name="list_type" value="people" />
</form>

