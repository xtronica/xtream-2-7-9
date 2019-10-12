<?php
// Xtream UI - Panel Update
$rPath = "/home/xtreamcodes/iptv_xtream_codes/adtools/settings.json";
$rSettings = json_decode(file_get_contents($rPath), True);

if (($rSettings) && ($rSettings["auto_update"])) {
    if (time() - $rSettings["auto_update_check"] > $rSetings["auto_update_periodicity"]) {
        $rSettings["auto_update_check"] = time();
        $rUpdate = file_get_contents($rSettings["git_url"]."/master/adtools/settings.json");
        if (($rUpdate["version"]) && (intval($rUpdate["version"]) > intval($rSettings["version"]))) {
            // New version available!
            exec($rUpdate["update_script"]);
            // Set changes to settings here then save.
            $rUpdate["auto_update_check"] = $rSettings["auto_update_check"];
            file_put_contents($rPath, json_encode($rUpdate));
        } else {
            file_put_contents($rPath, json_encode($rSettings));
        }
    }
}
?>