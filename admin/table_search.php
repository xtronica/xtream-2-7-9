<?php
include "functions.php";

$rType = $_GET["id"];
$rStart = intval($_GET["start"]);
$rLimit = intval($_GET["length"]);

if ($rType == "users") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`users`.`password`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", "`users`.`max_connections`", "`users`.`max_connections`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_admin"]) {
        $rWhere[] = "`users`.`is_mag` = 0 AND `users`.`is_e2` = 0";
    } else {
        $rWhere[] = "`users`.`is_mag` = 0 AND `users`.`is_e2` = 0 AND `users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR `users`.`password` LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`max_connections` LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
    if ($rPermissions["is_admin"]) {
        if (strlen($_GET["reseller"]) > 0) {
            $rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`username`, `users`.`password`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections`, (SELECT MAX(`date_start`) FROM `user_activity` WHERE `users`.`id` = `user_activity`.`user_id`) AS `last_active` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if (!$rRow["admin_enabled"]) {
                    $rStatus = '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if (!$rRow["enabled"]) {
                        $rStatus = '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
                        $rStatus = '<i class="text-warning far fa-circle"></i>';
                    } else {
                        $rStatus = '<i class="text-success fas fa-circle"></i>';
                    }
                }
                if ($rRow["active_connections"] > 0) {
                    $rActive = '<i class="text-success fas fa-circle"></i>';
                } else {
                    $rActive = '<i class="text-warning far fa-circle"></i>';
                }
                if ($rRow["is_trial"]) {
                    $rTrial = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rTrial = '<i class="text-secondary far fa-circle"></i>';
                }
                if ($rRow["exp_date"]) {
                    if ($rRow["exp_date"] < time()) {
                        $rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
                    } else {
                        $rExpDate = date("Y-m-d", $rRow["exp_date"]);
                    }
                } else {
                    $rExpDate = "Never";
                }
                if ($rRow["max_connections"] == 0) {
                    $rRow["max_connections"] = "&infin;";
                }
                $rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["allow_download"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="download(\''.$rRow["username"].'\', \''.$rRow["password"].'\');"><i class="mdi mdi-download"></i></button>
                    ';
                }
                $rButtons .= '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'kill\');"><i class="fas fa-hammer"></i></button>
                ';
                if ($rPermissions["is_admin"]) {
                    if ($rRow["admin_enabled"] == 1) {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    } else {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    }
                }
                if ($rRow["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                if ($rRow["last_active"]) {
                    $rLastActive = date("Y-m-d", $rRow["last_active"]);
                } else {
                    $rLastActive = "Never";
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["username"], $rRow["password"], $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rActiveConnections, $rRow["max_connections"], $rLastActive, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "mags") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`mag_devices`.`mac`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR from_base64(`mag_devices`.`mac`) LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
    if ($rPermissions["is_admin"]) {
        if (strlen($_GET["reseller"]) > 0) {
            $rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `mag_devices` ON `mag_devices`.`user_id` = `users`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`username`, `mag_devices`.`mac`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `mag_devices` ON `mag_devices`.`user_id` = `users`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if (!$rRow["admin_enabled"]) {
                    $rStatus = '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if (!$rRow["enabled"]) {
                        $rStatus = '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
                        $rStatus = '<i class="text-warning far fa-circle"></i>';
                    } else {
                        $rStatus = '<i class="text-success fas fa-circle"></i>';
                    }
                }
                if ($rRow["active_connections"] > 0) {
                    $rActive = '<i class="text-success fas fa-circle"></i>';
                } else {
                    $rActive = '<i class="text-warning far fa-circle"></i>';
                }
                if ($rRow["is_trial"]) {
                    $rTrial = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rTrial = '<i class="text-secondary far fa-circle"></i>';
                }
                if ($rRow["exp_date"]) {
                    if ($rRow["exp_date"] < time()) {
                        $rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
                    } else {
                        $rExpDate = date("Y-m-d", $rRow["exp_date"]);
                    }
                } else {
                    $rExpDate = "Never";
                }
                $rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["admin_enabled"] == 1) {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    } else {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    }
                }
                if ($rRow["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["username"], base64_decode($rRow["mac"]), $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "enigmas") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`enigma2_devices`.`mac`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR `enigma2_devices`.`mac` LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
    if ($rPermissions["is_admin"]) {
        if (strlen($_GET["reseller"]) > 0) {
            $rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `enigma2_devices` ON `enigma2_devices`.`user_id` = `users`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`username`, `enigma2_devices`.`mac`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `enigma2_devices` ON `enigma2_devices`.`user_id` = `users`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if (!$rRow["admin_enabled"]) {
                    $rStatus = '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if (!$rRow["enabled"]) {
                        $rStatus = '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
                        $rStatus = '<i class="text-warning far fa-circle"></i>';
                    } else {
                        $rStatus = '<i class="text-success fas fa-circle"></i>';
                    }
                }
                if ($rRow["active_connections"] > 0) {
                    $rActive = '<i class="text-success fas fa-circle"></i>';
                } else {
                    $rActive = '<i class="text-warning far fa-circle"></i>';
                }
                if ($rRow["is_trial"]) {
                    $rTrial = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rTrial = '<i class="text-secondary far fa-circle"></i>';
                }
                if ($rRow["exp_date"]) {
                    if ($rRow["exp_date"] < time()) {
                        $rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
                    } else {
                        $rExpDate = date("Y-m-d", $rRow["exp_date"]);
                    }
                } else {
                    $rExpDate = "Never";
                }
                $rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["admin_enabled"] == 1) {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    } else {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    }
                }
                if ($rRow["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["username"], $rRow["mac"], $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
}
?>