<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginRemotesupportRemotesupport extends CommonDBTM
{
    public static function showInfo($item)
    {

        $config = Config::getConfigurationValues('plugin:Remotesupport');

        if ($config["show_in_computers"] == false) {
            return true;
        }

        if ($config["fusion"] == true && $config["easy_novnc"] == true) {
            $pfInventoryComputerComputer = new PluginFusioninventoryInventoryComputerComputer();
            $a_computerextend = $pfInventoryComputerComputer->hasAutomaticInventory($item->getID());
            if (empty($a_computerextend)) {
                return true;
            }

            $href = "https://" . $_SERVER['SERVER_ADDR'] . "/vnc.html?path=vnc%2F" . $a_computerextend['remote_addr'] . "&autoconnect=true&resize=scale&reconnect=true&show_dot=true";
            $name = $a_computerextend['remote_addr'];
        } else {
            $href = "vnc://" . $item->fields["name"];
            $name = $item->fields["name"];
        }
        echo '<table class="tab_glpi" width="100%">';
        echo '<tr>';
        echo '<th>' . __('Remote Support') . '</th>';
        echo '</tr>';
        echo '<tr class="tab_bg_1">';
        echo '<td>';

        $url = "<a target=\"_blank\" href=\"" . $href . "\"><li class=\"document\"><i class=\"fa fa-laptop-medical\"></i>" . $name . "</li></a>";

        if ($url != "") {
            echo "<div><ul class=\"timeline_choices\"><h2>VNC connect : </h2>";
            echo $url;
            echo "</ul></div>";
        }
        echo '</td>';
        echo '</tr>';
        echo '</table>';

    }

    public static function getStatesIds()
    {
        global $DB;

        $states_ids = [];

        $req = $DB->request('glpi_states', ['FIELDS' => ['glpi_states' => ['id', 'name']]], ['OR' => ['name' => 'Online', 'name' => 'Offline']]);

        $ret = $req->next();
        $states_ids[$ret['name']] = $ret['id'];
        $ret = $req->next();
        $states_ids[$ret['name']] = $ret['id'];

        return $states_ids;
    }

    public static function getContactArray()
    {
        global $DB;

        $req = $DB->request('glpi_computers', ['FIELDS' => ['glpi_computers' => ['id', 'contact']]]);

        $contactids = [];
        while ($ret = $req->next()) {
            $contactids[$ret["id"]] = $ret["contact"];
        }

        return $contactids;
    }

    public static function cronRemotesupport($task)
    {
        global $DB;

        $config = Config::getConfigurationValues('plugin:Remotesupport');

        if ($config["fusion"] == false || $config["run_mode"] == "None") {
            return 0;
        }

        Toolbox::logInFile("remotsupport", "Starting search of agents\n");

        if ($config["run_mode"] == "Parallel") {
            $pfConfig = new PluginFusioninventoryConfig();
            $port = $pfConfig->getValue('agent_port');
            $rs_path = Plugin::getPhpDir('remotesupport');

            $check_arr = [];
            $comps = [];
            $pfInventoryComputerComputer = new PluginFusioninventoryInventoryComputerComputer();
            foreach (getAllDataFromTable(PluginFusioninventoryAgent::getTable()) as $a) {

                $check = [];
                $a_computerextend = $pfInventoryComputerComputer->hasAutomaticInventory($a["computers_id"]);

                $check["url"] = "http://" . $a_computerextend["remote_addr"] . ":" . $port . "/status";
                $check["id"] = $a["id"];
                $check["computers_id"] = $a["computers_id"];
                $check["status"] = "unknown";

                $check_arr[] = $check;
                $comps[$a["computers_id"]] = $check;
                //print_r($agent->getAgentStatusURLs());
            }

            $descriptorspec = array(
                0 => array("pipe", "r"), // stdin is a pipe that the child will read from
                1 => array("pipe", "w"), // stdout is a pipe that the child will write to
                2 => array("file", "/tmp/error-output.txt", "a"), // stderr is a file to write to
            );

            $cwd = '/tmp';
            $env = array('debug' => 'false', 'threads' => $config["threads"]);

            $process = proc_open($rs_path . '/bin/check_status', $descriptorspec, $pipes, $cwd, $env);

            if (is_resource($process)) {
                // $pipes now looks like this:
                // 0 => writeable handle connected to child stdin
                // 1 => readable handle connected to child stdout
                // Any error output will be appended to /tmp/error-output.txt

                fwrite($pipes[0], json_encode($check_arr));
                fclose($pipes[0]);

                $checked = json_decode(stream_get_contents($pipes[1]));
                fclose($pipes[1]);

                // It is important that you close any pipes before calling
                // proc_close in order to avoid a deadlock
                $return_value = proc_close($process);

                Toolbox::logInFile("remotsupport", "command returned $return_value\n");
            }

        } else {
            $agents = [];
            $data_set = [];
            $checked = [];

            foreach (getAllDataFromTable(PluginFusioninventoryAgent::getTable()) as $a) {

                $data_set[] = $a["id"];
                $agents[$a["id"]] = $a;
            }

            foreach ($data_set as $id) {
                $agent = new PluginFusioninventoryAgent;
                $agent->getFromDB((int) $id);
                $st = $agent->getStatus();

                if ($st["message"] != "noanswer") {
                    $check = new stdClass();
                    $check->computers_id = $agents[$id]["computers_id"];
                    $checked[] = $check;
                }

            }

        }

        $stids = self::getStatesIds();

        $DB->update("glpi_computers", [
            'states_id' => $stids["Offline"]],
            ['1' => '1']
        );

        $ids = [];
        $cids = self::getContactArray();
        
        foreach ($checked as $s) {

            $comp = new Computer();
            $comp->getFromDB($s->computers_id);
            Toolbox::logInFile("remotsupport", $s->computers_id . " " . $cids[$s->computers_id] . "\n");
            $ids[] = $s->computers_id;
        }

        $DB->update("glpi_computers", [
            'states_id' => $stids["Online"]],
            ['id' => $ids]
        );

        return 0;
    }

    public static function cronInfo($name)
    {
        return [
            'description' => "Agent search remotesupport"];
    }
}
