<?php

include "../../../inc/includes.php";

declare (ticks = 1);

global $DB, $agents;

$check_arr = [];
$comps = [];
$pfInventoryComputerComputer = new PluginFusioninventoryInventoryComputerComputer();
foreach (getAllDataFromTable(PluginFusioninventoryAgent::getTable()) as $a) {

    $check = [];
    $a_computerextend = $pfInventoryComputerComputer->hasAutomaticInventory($a["computers_id"]);

    $check["url"] = "http://" . $a_computerextend["remote_addr"] . ":62354/status";
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
$env = array('debug' => 'false');

$process = proc_open(__DIR__ . '/check_status', $descriptorspec, $pipes, $cwd, $env);

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

    echo "command returned $return_value\n";
}

$req = $DB->request('glpi_states', ['FIELDS' => ['glpi_states' => ['id', 'name']]], ['OR' => ['name' => 'Online', 'name' => 'Offline']]);

$ret = $req->next();
$states_ids[$ret['name']] = $ret['id'];
$ret = $req->next();
$states_ids[$ret['name']] = $ret['id'];
print_r($states_ids);

$DB->update("glpi_computers", [
    'states_id' => $states_ids["Offline"]],
    ['1' => '1']
);

foreach ($checked as $s) {
    echo $s->computers_id . " ";

    $comp = new Computer();
    $comp->getFromDB($s->computers_id);
    $comp->fields["states_id"] = $states_ids["Online"];
    $DB->update("glpi_computers", [
        'states_id' => $comp->fields["states_id"]],
        ['id' => $s->computers_id]
    );
    echo $comp->fields["contact"] . "\n";

}

//    print_r($a_computerextend);
exit(0);
