<?php
/*
-------------------------------------------------------------------------
Remote Spport (VNC)
Copyright (C) 2021 by Alessandro Carloni
https://github.com/Kaya84/RemoteSupport

-------------------------------------------------------------------------
LICENSE
This file is part of Camera Input.
Camera Input is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
Camera Input is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with Camera Input. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
 */

function plugin_remotesupport_install()
{
    global $DB;

    $config = new Config();
    $config->setConfigurationValues('plugin:Remotesupport',
        ['run_mode' => 'None',
            'threads' => 100,
            'show_in_tickets' => true,
            'show_in_computers' => true,
            'easy_novnc' => true,
            'fusion' => true]
    );

    Toolbox::logInFile("remotesupport", "Installing plugin");
    $state_online = [
        'name' => 'Online',
        'entities_id' => 0,
        'is_recursive' => 0,
        'comment' => '',
        'states_id' => 0,
        'completename' => 'Online',
        'level' => 1,
        'ancestors_cache' => '[]',
        'sons_cache' => 'NULL',
        'is_visible_computer' => 1,
        'is_visible_monitor' => 0,
        'is_visible_networkequipment' => 0,
        'is_visible_peripheral' => 0,
        'is_visible_phone' => 0,
        'is_visible_printer' => 0,
        'is_visible_softwareversion' => 0,
        'is_visible_softwarelicense' => 0,
        'is_visible_line' => 0,
        'is_visible_certificate' => 0,
        'is_visible_rack' => 0,
        'is_visible_passivedcequipment' => 0,
        'is_visible_enclosure' => 0,
        'is_visible_pdu' => 0,
        'is_visible_cluster' => 0,
        'is_visible_contract' => 0,
        'is_visible_appliance' => 0];

    $ret = $DB->insert(
        'glpi_states', $state_online
    );

    $state_offline = $state_online;
    $state_offline["name"] = 'Offline';
    $state_offline["completename"] = 'Offline';

    $ret = $DB->insert(
        'glpi_states', $state_offline
    );

    return true;
}

function plugin_remotesupport_uninstall()
{
    global $DB;

    Toolbox::logInFile("remotsupport", "Uninstalling plugin");
    CronTask::Unregister('remotesupport');

    $req = $DB->request('glpi_states', ['FIELDS' => ['glpi_states' => ['id', 'name']]], ['OR' => ['name' => 'Online', 'name' => 'Offline']]);

    $ret = $req->next();
    $states_ids[$ret['name']] = $ret['id'];
    $ret = $req->next();
    $states_ids[$ret['name']] = $ret['id'];

    $DB->query('UPDATE glpi_computers SET states_id=NULL WHERE id=' . $states_ids["Offline"]);
    $DB->query('UPDATE glpi_computers SET states_id=NULL WHERE id=' . $states_ids["Online"]);
    $DB->query('DELETE FROM glpi_states WHERE id=' . $states_ids["Offline"]);
    $DB->query('DELETE FROM glpi_states WHERE id=' . $states_ids["Online"]);

    return true;
}

function plugin_remotesupport_postinit()
{
    global $CFG_GLPI, $DB;
    $config = Config::getConfigurationValues('plugin:Remotesupport');

    if (isset($_GET['id']) && $_GET['id'] != 0 && isset($_GET['_itemtype']) && $_GET['_itemtype'] == "Ticket" && $config["show_in_tickets"]) {
        $id = $_GET['id'];

        //mysql> select * from glpi_tickets_users where tickets_id = 2 and type = 1;

        $req = $DB->request(['FROM' => 'glpi_tickets_users', 'WHERE' => ['tickets_id' => $id, 'type' => 1]]);
        //NB: Estraggo unicamente il primo richiedente
        $row = $req->next();
        $requester = $row['users_id'];
        // select  id, name, users_id from glpi_computers where users_id = 178;

        if ($row['users_id'] != 0) {
            $req2 = $DB->request(['FROM' => 'glpi_computers', 'WHERE' => ['users_id' => $requester, 'is_deleted' => 0]]);
            $url = "";

            while ($row2 = $req2->next()) {
                //$url .= "<li class=\"document\" onclick=\"location.href='vnc://" . $row2['name'] ."'\"$
                $url .= "<li class=\"document\" onclick=\"location.href='vnc://" . $row2['name'] . "'\"><i class=\"fa fa-laptop-medical\"></i>" . $row2['name'] . "</li>";
            }

            if ($url != "") {
                echo "<div><ul class=\"timeline_choices\"><h2>Remote support : </h2>";
                echo $url;
                echo "</ul></div>";
            }
        }
    }
}
