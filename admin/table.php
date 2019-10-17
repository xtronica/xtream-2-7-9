<?php
include "functions.php";
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (!isset($_SESSION['user_id'])) { exit; }

$joinQuery = "";

if ($_GET["id"] == "users") {
    $rRegisteredUsers = getRegisteredUsernames();
    $rActivity = Array();
    $result = $db->query("SELECT `user_id`, COUNT(`activity_id`) AS `count` FROM `user_activity_now` GROUP BY `user_id`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $rActivity[$row["user_id"]] = intval($row["count"]);
        }
    }
    $rLastActivity = Array();
    $result = $db->query("SELECT `user_id`, MAX(`date_start`) AS `date_start` FROM `user_activity` GROUP BY `user_id`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $rLastActivity[$row["user_id"]] = intval($row["date_start"]);
        }
    }

    $table = 'users';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ($rPermissions["is_admin"]) {
        $extraWhere = "`is_mag` = 0 AND `is_e2` = 0";
    } else {
        $extraWhere = "`is_mag` = 0 AND `is_e2` = 0 AND `member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }

    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'username', 'dt' => 1),
        array('db' => 'password', 'dt' => 2),
        array('db' => 'member_id', 'dt' => 3,
            'formatter' => function( $d, $row ) {
                global $rRegisteredUsers;
                return $rRegisteredUsers[intval($d)];
            }
        ),
        array('db' => 'admin_enabled', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                if ($d == 0) {
                    return '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if ($row["enabled"] == 0) {
                        return '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($row["exp_date"]) && ($row["exp_date"] < time())) {
                        return '<i class="text-warning far fa-circle"></i>';
                    } else {
                        return '<i class="text-success fas fa-circle"></i>';
                    }
                }
            }
        ),
        array('db' => 'id', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                global $rActivity;
                if (isset($rActivity[intval($d)])) {
                    return '<i class="text-success fas fa-circle"></i>';
                } else {
                    return '<i class="text-warning far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'is_trial', 'dt' => 6,
            'formatter' => function( $d, $row ) {
                if ($d) {
                    return '<i class="text-warning fas fa-circle"></i>';
                } else {
                    return '<i class="text-secondary far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'exp_date', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                if ($d) { return date("Y-m-d", $d); } else { return "Never"; }
            }
        ),
        array('db' => 'max_connections', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                global $rActivity;
                if (isset($rActivity[intval($row["id"])])) {
                    $val = $rActivity[intval($row["id"])];
                } else {
                    $val = 0;
                }
                if ($d == 0) { $d = "&infin;"; }
                return "<a href=\"./live_connections.php?user_id=".$row["id"]."\">".$val." / ".$d."</a>";
            }
        ),
        array('db' => 'max_connections', 'dt' => 9,
            'formatter' => function( $d, $row ) {
                global $rLastActivity;
                if (isset($rLastActivity[intval($row["id"])])) {
                    return date("Y-m-d", $rLastActivity[intval($row["id"])]);
                } else {
                    return "Never";
                }
            }
        ),
        array('db' => 'id', 'dt' => 10,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$d.'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$d.'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["allow_download"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="download(\''.$row["username"].'\', \''.$row["password"].'\');"><i class="mdi mdi-download"></i></button>
                    ';
                }
                $rButtons .= '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api('.$d.', \'kill\');"><i class="fas fa-hammer"></i></button>
                ';
                if ($row["admin_enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$d.', \'ban\');"><i class="mdi mdi-power"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$d.', \'unban\');"><i class="mdi mdi-power"></i></button>
                    ';
                }
                if ($row["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$d.', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$d.', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$d.', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                return $rButtons;
            }
        ),
        array('db' => 'admin_notes', 'dt' => 11),
        array('db' => 'reseller_notes', 'dt' => 12),
        array('db' => 'enabled', 'hide' => true),
        array('db' => 'exp_date', 'hide' => true),
    );
} else if ($_GET["id"] == "reg_users") {
    $rMemberGroups = getMemberGroups();
    
    $table = 'reg_users';
    $get = $_GET["id"];
    $primaryKey = 'id';
    
    if ($rPermissions["is_admin"]) {
        $extraWhere = "";
    } else {
        $extraWhere = "`owner_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }

    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'username', 'dt' => 1),
        array('db' => 'email', 'dt' => 2),
        array('db' => 'ip', 'dt' => 3),
        array('db' => 'member_group_id', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                global $rMemberGroups;
                return $rMemberGroups[intval($d)]["group_name"];
            }
        ),
        array('db' => 'status', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                if ($d == 1) {
                    return '<i class="text-success fas fa-circle"></i>';
                } else {
                    return '<i class="text-danger fas fa-circle"></i>';
                }
            }
        ),
        array('db' => 'credits', 'dt' => 6),
        array('db' => 'last_login', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                if ($d) {
                    return date("Y-m-d H:i:s", $d);
                } else {
                    return "NEVER";
                }
            }
        ),
        array('db' => 'id', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./reg_user.php?id='.$d.'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./credits_add.php?id='.$d.'"><button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs"><i class="fe-dollar-sign"></i></button></a>
                    ';
                    $rButtons .= '<a href="./subreseller.php?id='.$d.'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($row["status"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$d.', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$d.', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$d.', \'delete\');"><i class="mdi mdi-close"></i></button>
                    ';
                }
                return $rButtons;
            }
        ),
        array('db' => 'notes', 'dt' => 9)
    );
} else if ($_GET["id"] == "live_connections") {
    if (($rPermissions["is_reseller"]) && (!$rPermissions["reseller_client_connection_logs"])) { exit; }
    $rRegisteredUsers = getRegisteredUsernames();
    $rChannels = getChannels();
    $rStreamingServers = getStreamingServers();
    $rActivity = Array();
    $result = $db->query("SELECT `user_id`, COUNT(`activity_id`) AS `count` FROM `user_activity_now` GROUP BY `user_id`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $rActivity[$row["user_id"]] = intval($row["count"]);
        }
    }
    $table = 'user_activity_now';
    $get = $_GET["activity_id"];
    $primaryKey = 'activity_id';
    
    $joinQuery = "FROM user_activity_now AS `a` INNER JOIN `users` AS `u` ON `a`.`user_id` = `u`.`id`";
    
    if ((isset($_GET["server_id"])) && ($rPermissions["is_admin"])) {
        $extraWhere = "`a`.`server_id` = ".intval($_GET["server_id"]);
    } else if (isset($_GET["stream_id"])) {
        $extraWhere = "`a`.`stream_id` = ".intval($_GET["stream_id"]);
    } else if (isset($_GET["user_id"])) {
        $extraWhere = "`a`.`user_id` = ".intval($_GET["user_id"]);
    } else {
        $extraWhere = "";
    }
    if ($rPermissions["is_reseller"]) {
        if (strlen($extraWhere) > 0) {
            $extraWhere .= " AND ";
        }
        $extraWhere .= "`u`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    $columns = array(
        array('db' => 'a.divergence', 'dt' => 0,
            'formatter' => function( $d, $row ) {
                if ($row["divergence"] <= 10) {
                    return '<i class="text-success fas fa-circle"></i>';
                } else if ($row["divergence"] <= 50) {
                    return '<i class="text-warning fas fa-circle"></i>';
                } else {
                    return '<i class="text-danger fas fa-circle"></i>';
                }
            }
        ),
        array('db' => 'a.user_id', 'dt' => 1,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./user.php?id=".$row["user_id"]."'>".getUser(intval($row["user_id"]))["username"]."</a>";
                } else {
                    return "<a href='./user_reseller.php?id=".$row["user_id"]."'>".getUser(intval($row["user_id"]))["username"]."</a>";
                }
            }
        ),
        array('db' => 'a.stream_id', 'dt' => 2,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./stream.php?id=".$row["stream_id"]."'>".getChannelsByID(intval($row["stream_id"]))["stream_display_name"]."</a>";
                } else {
                    return getChannelsByID(intval($row["stream_id"]))["stream_display_name"];
                }
            }
        ),
        array('db' => 'a.server_id', 'dt' => 3,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./server.php?id=".$row["server_id"]."'>".getStreamingServersByID(intval($row["server_id"]))["server_name"]."</a>";
                } else {
                    return "";
                }
            }
        ),
        array('db' => 'a.user_ip', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                if ($row["user_ip"]) { return "<a target='_blank' href='https://www.ip-tracker.org/locator/ip-lookup.php?ip=".$row["user_ip"]."'>".$row["user_ip"]."</a>"; }
            }
        ),
        array('db' => 'a.user_id', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                global $rActivity;
                $max = getUser(intval($row["user_id"]))["max_connections"];
                if ($max == 0) {
                    $max = "&infin;";
                }
                return $rActivity[intval($row["user_id"])]." / ".$max;
            }
        ),
        array('db' => 'a.geoip_country_code', 'dt' => 6,
            'formatter' => function( $d, $row ) {
                return "<img src='https://www.ip-tracker.org/images/ip-flags/".strtolower($row["geoip_country_code"]).".png'></img>";
            }
        ),
        array('db' => 'a.pid', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                return '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api('.$row["pid"].', \'kill\');"><i class="fas fa-hammer"></i></button>';
            }
        ),
        array('db' => 'u.member_id', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                return $row["member_id"];
            }
        )
    );
} else if ($_GET["id"] == "user_activity") {
    if (($rPermissions["is_reseller"]) && (!$rPermissions["reseller_client_connection_logs"])) { exit; }
    $rRegisteredUsers = getRegisteredUsernames();
    $rChannels = getChannels();
    $rStreamingServers = getStreamingServers();

    $table = 'user_activity';
    $get = $_GET["activity_id"];
    $primaryKey = 'activity_id';
    
    $joinQuery = "FROM user_activity AS `a` INNER JOIN `users` AS `u` ON `a`.`user_id` = `u`.`id`";
    
    if ((isset($_GET["server_id"])) && ($rPermissions["is_admin"])) {
        $extraWhere = "`a`.`server_id` = ".intval($_GET["server_id"]);
    } else if (isset($_GET["stream_id"])) {
        $extraWhere = "`a`.`stream_id` = ".intval($_GET["stream_id"]);
    } else if (isset($_GET["user_id"])) {
        $extraWhere = "`a`.`user_id` = ".intval($_GET["user_id"]);
    } else {
        $extraWhere = "";
    }
    if ($rPermissions["is_reseller"]) {
        if (strlen($extraWhere) > 0) {
            $extraWhere .= " AND ";
        }
        $extraWhere .= "`u`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    $columns = array(
        array('db' => 'a.activity_id', 'dt' => 0,
            'formatter' => function( $d, $row ) {
                return $row["activity_id"];
            }
        ),
        array('db' => 'a.user_id', 'dt' => 1,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./user.php?id=".$row["user_id"]."'>".getUser(intval($row["user_id"]))["username"]."</a>";
                } else {
                    return "<a href='./user_reseller.php?id=".$row["user_id"]."'>".getUser(intval($row["user_id"]))["username"]."</a>";
                }
            }
        ),
        array('db' => 'a.stream_id', 'dt' => 2,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./stream.php?id=".$row["stream_id"]."'>".getChannelsByID(intval($row["stream_id"]))["stream_display_name"]."</a>";
                } else {
                    return getChannelsByID(intval($row["stream_id"]))["stream_display_name"];
                }
            }
        ),
        array('db' => 'a.server_id', 'dt' => 3,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<a href='./server.php?id=".$row["server_id"]."'>".getStreamingServersByID(intval($row["server_id"]))["server_name"]."</a>";
                } else {
                    return "";
                }
            }
        ),
        array('db' => 'a.date_start', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                return date("Y-m-d H:i:s", $row["date_start"]);
            }
        ),
        array('db' => 'a.date_end', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                return date("Y-m-d H:i:s", $row["date_end"]);
            }
        ),
        array('db' => 'a.user_ip', 'dt' => 6,
            'formatter' => function( $d, $row ) {
                if ($row["user_ip"]) { return "<a target='_blank' href='https://www.ip-tracker.org/locator/ip-lookup.php?ip=".$row["user_ip"]."'>".$row["user_ip"]."</a>"; }
            }
        ),
        array('db' => 'a.geoip_country_code', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                return "<img src='https://www.ip-tracker.org/images/ip-flags/".strtolower($row["geoip_country_code"]).".png'></img>";
            }
        ),
        array('db' => 'u.member_id', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                return $row["member_id"];
            }
        )
    );
} else if ($_GET["id"] == "mags") {
    $rRegisteredUsers = getRegisteredUsernames();

    $table = 'users';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ($rPermissions["is_admin"]) {
        $extraWhere = "`is_mag` = 1";
    } else {
        $extraWhere = "`is_mag` = 1 AND `member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    $joinQuery = "FROM `users` AS `u` INNER JOIN `mag_devices` AS `m` ON `m`.`user_id` = `u`.`id`";

    $columns = array(
        array('db' => 'u.id', 'dt' => 0,
            'formatter' => function( $d, $row ) {
                return $row["id"];
            }
        ),
        array('db' => 'u.username', 'dt' => 1,
            'formatter' => function( $d, $row ) {
                return $row["username"];
            }
        ),
        array('db' => 'm.mac', 'dt' => 2,
            'formatter' => function( $d, $row ) {
                return base64_decode($row["mac"]);
            }
        ),
        array('db' => 'u.member_id', 'dt' => 3,
            'formatter' => function( $d, $row ) {
                global $rRegisteredUsers;
                return $rRegisteredUsers[intval($row["member_id"])];
            }
        ),
        array('db' => 'u.admin_enabled', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                if ($row["admin_enabled"] == 0) {
                    return '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if ($row["enabled"] == 0) {
                        return '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($row["exp_date"]) && ($row["exp_date"] < time())) {
                        return '<i class="text-warning far fa-circle"></i>';
                    } else {
                        return '<i class="text-success fas fa-circle"></i>';
                    }
                }
            }
        ),
        array('db' => 'u.id', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                global $rActivity;
                if (isset($rActivity[intval($row["id"])])) {
                    return '<i class="text-success fas fa-circle"></i>';
                } else {
                    return '<i class="text-warning far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'u.is_trial', 'dt' => 6,
            'formatter' => function( $d, $row ) {
                if ($row["is_trial"]) {
                    return '<i class="text-warning fas fa-circle"></i>';
                } else {
                    return '<i class="text-secondary far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'u.exp_date', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                if ($row["exp_date"]) { return date("Y-m-d", $row["exp_date"]); } else { return "Never"; }
            }
        ),
        array('db' => 'u.id', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$row["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$row["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($row["admin_enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                    ';
                }
                if ($row["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                return $rButtons;
            }
        ),
        array('db' => 'u.admin_notes', 'dt' => 9),
        array('db' => 'u.reseller_notes', 'dt' => 10),
        array('db' => 'u.enabled', 'hide' => true)
    );
} else if ($_GET["id"] == "enigmas") {
    $rRegisteredUsers = getRegisteredUsernames();

    $table = 'users';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ($rPermissions["is_admin"]) {
        $extraWhere = "`is_e2` = 1";
    } else {
        $extraWhere = "`is_e2` = 1 AND `member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    $joinQuery = "FROM `users` AS `u` INNER JOIN `enigma2_devices` AS `m` ON `m`.`user_id` = `u`.`id`";

    $columns = array(
        array('db' => 'u.id', 'dt' => 0,
            'formatter' => function( $d, $row ) {
                return $row["id"];
            }
        ),
        array('db' => 'u.username', 'dt' => 1,
            'formatter' => function( $d, $row ) {
                return $row["username"];
            }
        ),
        array('db' => 'm.mac', 'dt' => 2,
            'formatter' => function( $d, $row ) {
                return $row["mac"];
            }
        ),
        array('db' => 'u.member_id', 'dt' => 3,
            'formatter' => function( $d, $row ) {
                global $rRegisteredUsers;
                return $rRegisteredUsers[intval($row["member_id"])];
            }
        ),
        array('db' => 'u.admin_enabled', 'dt' => 4,
            'formatter' => function( $d, $row ) {
                if ($row["admin_enabled"] == 0) {
                    return '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if ($row["enabled"] == 0) {
                        return '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($row["exp_date"]) && ($row["exp_date"] < time())) {
                        return '<i class="text-warning far fa-circle"></i>';
                    } else {
                        return '<i class="text-success fas fa-circle"></i>';
                    }
                }
            }
        ),
        array('db' => 'u.id', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                global $rActivity;
                if (isset($rActivity[intval($row["id"])])) {
                    return '<i class="text-success fas fa-circle"></i>';
                } else {
                    return '<i class="text-warning far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'u.is_trial', 'dt' => 6,
            'formatter' => function( $d, $row ) {
                if ($row["is_trial"]) {
                    return '<i class="text-warning fas fa-circle"></i>';
                } else {
                    return '<i class="text-secondary far fa-circle"></i>';
                }
            }
        ),
        array('db' => 'u.exp_date', 'dt' => 7,
            'formatter' => function( $d, $row ) {
                if ($row["exp_date"]) { return date("Y-m-d", $row["exp_date"]); } else { return "Never"; }
            }
        ),
        array('db' => 'u.id', 'dt' => 8,
            'formatter' => function( $d, $row ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$row["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$row["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($row["admin_enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                    ';
                }
                if ($row["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$row["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                return $rButtons;
            }
        ),
        array('db' => 'u.admin_notes', 'dt' => 9),
        array('db' => 'u.reseller_notes', 'dt' => 10),
        array('db' => 'u.enabled', 'hide' => true)
    );
} else if ($_GET["id"] == "mag_events") {
    if (!$rPermissions["is_admin"]) { exit; }
    $table = 'mag_events';
    $get = $_GET["id"];
    $primaryKey = 'id';
    $extraWhere = "";

    $columns = array(
        array('db' => 'send_time', 'dt' => 0,
            'formatter' => function( $d, $row ) {
                return date("Y-m-d H:i:s", $d);
            }
        ),
        array('db' => 'status', 'dt' => 1),
        array('db' => 'mag_device_id', 'dt' => 2,
            'formatter' => function( $d, $row ) {
                return base64_decode(getMag($d)["mac"]);
            }
        ),
        array('db' => 'event', 'dt' => 3),
        array('db' => 'msg', 'dt' => 4),
        array('db' => 'id', 'dt' => 5,
            'formatter' => function( $d, $row ) {
                $rButtons = '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$d.', \'delete\');"><i class="mdi mdi-close"></i></button>';
                return $rButtons;
            }
        )
    );
} else if ($_GET["id"] == "streams") {
    $rStreamInformation = Array();
    $table = 'streams';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category"])) && (strlen($_GET["category"]) > 0)) {
        $extraWhere = "`type` IN (1,3) AND `category_id` = ".intval($_GET["category"]);
    } else if ((isset($_GET["stream_id"])) && (strlen($_GET["stream_id"]) > 0)) {
        $extraWhere = "`type` IN (1,3) AND `id` = '".$db->real_escape_string($_GET["stream_id"])."'";
    } else {
        $extraWhere = "`type` IN (1,3)";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0,
            'formatter' => function( $d, $row, $server ) {
                return $d;
            }
        ),
        array('db' => 'stream_display_name', 'dt' => 1,
            'formatter' => function( $d, $row, $server ) {
                global $rCategories;
                $rCategory = empty($rCategories[$row["category_id"]]["category_name"]) ? 'No Category' : $rCategories[$row["category_id"]]["category_name"];
                $rCategory = "<span style='font-size:11px;color: #057f91;'>{$rCategory}</span>";
                return "<b>".$row['stream_display_name']."</b><br>".$rCategory."";
            }
        ),
        array('db' => 'id', 'dt' => 2,
            'formatter' => function( $d, $row, $server ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    return "<span style='font-size:11px;color: #057f91;'>".parse_url($server["current_source"])['host']."</span>";
                } else {
                    return "";
                }
            }
        ),
        array('db' => 'id', 'dt' => 3,
            'formatter' => function( $d, $row, $server ) {
                global $rServers, $rPermissions;
                if (isset($rServers[$server["server_id"]]["server_name"])) {
                    if ($rPermissions["is_admin"]) {
                        return $rServers[$server["server_id"]]["server_name"];
                    } else {
                        return "Server #".$server["server_id"];
                    }
                } else {
                    return "No Server Selected";
                }
            }
        ),
        array('db' => 'id', 'dt' => 4,
            'formatter' => function( $d, $row, $server ) {
                return "<a href=\"./live_connections.php?stream_id=".$row["id"]."\">".$server["active_count"]."</a>";
            }
        ),
        array('db' => 'id', 'dt' => 5,
            'formatter' => function( $d, $row, $server ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    global $rStatusArray;
                    if ($server["actual_status"] == 1) {
                        return $server["uptime_text"];
                    } else {
                        return $rStatusArray[$server["actual_status"]];
                    }
                } else {
                    return "";
                }
            }
        ),
        array('db' => 'id', 'dt' => 6,
            'formatter' => function( $d, $row, $server ) {
                global $rPermissions;
                if ($rPermissions["is_admin"]) {
                    if (!$server["server_id"]) { $server["server_id"] = 0; }
                    if ((intval($server["actual_status"]) == 1) OR (intval($server["actual_status"]) == 3) OR ($server["on_demand"] == 1) OR ($server["actual_status"] == 5)) { 
                        $rButtons = '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs api-stop" onClick="api('.$d.', '.$server["server_id"].', \'stop\');"><i class="mdi mdi-stop"></i></button>
                        ';
                        $rStatus = '';
                    } else {
                        $rButtons = '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs api-start" onClick="api('.$d.', '.$server["server_id"].', \'start\');"><i class="mdi mdi-play"></i></button>
                        ';
                        $rStatus = ' disabled';
                    }
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs api-restart" onClick="api('.$d.', '.$server["server_id"].', \'restart\');"'.$rStatus.'><i class="mdi mdi-refresh"></i></button>
                    <a href="./stream.php?id='.$d.'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    <button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$d.', '.$server["server_id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                    return $rButtons;
                } else {
                    return "";
                }
            }
        ),
        array('db' => 'id', 'dt' => 7,
            'formatter' => function( $d, $row, $server ) {
                global $rAdminSettings, $rPermissions;
                if ($rPermissions["is_admin"]) {
                    if (((intval($server["actual_status"]) == 1) OR ($server["on_demand"] == 1) OR ($server["actual_status"] == 5)) && ((strlen($rAdminSettings["admin_username"]) > 0) && (strlen($rAdminSettings["admin_password"]) > 0))) {
                        return '<button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs" onClick="player('.$d.');"><i class="mdi mdi-play"></i></button>';
                    } else {
                        return '<button type="button" disabled class="btn btn-outline-secondary waves-effect waves-light btn-xs"><i class="mdi mdi-play"></i></button>';
                    }
                }
            }
        ),
        array('db' => 'category_id', 'dt' => 8,
            'formatter' => function( $d, $row, $server ) {
                return $server["stream_text"];
            }
        )
    );
} else if ($_GET["id"] == "bouquets_streams") {
    if (!$rPermissions["is_admin"]) { exit; }
    $table = 'streams';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category_id"])) && (strlen($_GET["category_id"]) > 0)) {
        $extraWhere = "(`type` = 1 OR `type` = 3) AND `category_id` = ".intval($_GET["category_id"]);
    } else {
        $extraWhere = "(`type` = 1 OR `type` = 3)";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'stream_display_name', 'dt' => 1),
        array('db' => 'category_id', 'dt' => 2,
            'formatter' => function( $d, $row) {
                global $rCategories;
                return $rCategories[$d]["category_name"];
            }
        ),
        array('db' => 'id', 'dt' => 3,
            'formatter' => function( $d, $row) {
                return '<button data-id="'.$d.'" data-type="stream" type="button" style="display: none;" class="btn-remove btn btn-outline-danger waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'stream\', true);"><i class="mdi mdi-minus"></i></button>
                <button data-id="'.$d.'" data-type="stream" type="button" style="display: none;" class="btn-add btn btn-outline-info waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'stream\', true);"><i class="mdi mdi-plus"></i></button>';
            }
        )
    );
} else if ($_GET["id"] == "streams_short") {
    if (!$rPermissions["is_admin"]) { exit; }
    $table = 'streams';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category_id"])) && (strlen($_GET["category_id"]) > 0)) {
        $extraWhere = "(`type` = 1 OR `type` = 3) AND `category_id` = ".intval($_GET["category_id"]);
    } else {
        $extraWhere = "(`type` = 1 OR `type` = 3)";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'stream_display_name', 'dt' => 1),
        array('db' => 'id', 'dt' => 2,
            'formatter' => function( $d, $row) {
                return '<a href="./stream.php?id='.$d.'"><button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Stream" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>';
            }
        )
    );
} else if ($_GET["id"] == "movies_short") {
    if (!$rPermissions["is_admin"]) { exit; }
    $table = 'streams';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category_id"])) && (strlen($_GET["category_id"]) > 0)) {
        $extraWhere = "`type` = 2 AND `category_id` = ".intval($_GET["category_id"]);
    } else {
        $extraWhere = "`type` = 2";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'stream_display_name', 'dt' => 1),
        array('db' => 'id', 'dt' => 2,
            'formatter' => function( $d, $row) {
                return '<a href="./movie.php?id='.$d.'"><button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Movie" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>';
            }
        )
    );
} else if ($_GET["id"] == "bouquets_vod") {
    if (!$rPermissions["is_admin"]) { exit; }
    $rCategoriesVOD = getCategories("movie");
    $table = 'streams';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category_id"])) && (strlen($_GET["category_id"]) > 0)) {
        $extraWhere = "`type` = 2 AND `category_id` = ".intval($_GET["category_id"]);
    } else {
        $extraWhere = "`type` = 2";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'stream_display_name', 'dt' => 1),
        array('db' => 'category_id', 'dt' => 2,
            'formatter' => function( $d, $row) {
                global $rCategoriesVOD;
                return $rCategoriesVOD[$d]["category_name"];
            }
        ),
        array('db' => 'id', 'dt' => 3,
            'formatter' => function( $d, $row) {
                return '<button data-id="'.$d.'" data-type="vod" type="button" style="display: none;" class="btn-remove btn btn-outline-danger waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'vod\', true);"><i class="mdi mdi-minus"></i></button>
                <button data-id="'.$d.'" data-type="vod" type="button" style="display: none;" class="btn-add btn btn-outline-info waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'vod\', true);"><i class="mdi mdi-plus"></i></button>';
            }
        )
    );
} else if ($_GET["id"] == "bouquets_series") {
    if (!$rPermissions["is_admin"]) { exit; }
    $rCategoriesVOD = getCategories("series");
    $table = 'series';
    $get = $_GET["id"];
    $primaryKey = 'id';
    if ((isset($_GET["category_id"])) && (strlen($_GET["category_id"]) > 0)) {
        $extraWhere = "`category_id` = ".intval($_GET["category_id"]);
    } else {
        $extraWhere = "";
    }
    $columns = array(
        array('db' => 'id', 'dt' => 0),
        array('db' => 'title', 'dt' => 1),
        array('db' => 'category_id', 'dt' => 2,
            'formatter' => function( $d, $row) {
                global $rCategoriesVOD;
                return $rCategoriesVOD[$d]["category_name"];
            }
        ),
        array('db' => 'id', 'dt' => 3,
            'formatter' => function( $d, $row) {
                return '<button data-id="'.$d.'" data-type="series" type="button" style="display: none;" class="btn-remove btn btn-outline-danger waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'series\', true);"><i class="mdi mdi-minus"></i></button>
                <button data-id="'.$d.'" data-type="series" type="button" style="display: none;" class="btn-add btn btn-outline-info waves-effect waves-light btn-xs" onClick="toggleBouquet('.$d.', \'series\', true);"><i class="mdi mdi-plus"></i></button>';
            }
        )
    );
} else {
    exit;
}

$sql_details = array(
    'user' => $_INFO["db_user"],
    'pass' => $_INFO["db_pass"],
    'db'   => $_INFO["db_name"],
    'host' => $_INFO["host"].":".$_INFO["db_port"]
);
 
class SSP {
    /**
     * Create the data output array for the DataTables rows
     *
     * @param array $columns Column information array
     * @param array $data    Data from the SQL get
     * @param bool  $isJoin  Determine the the JOIN/complex query or simple one
     *
     * @return array Formatted data in a row based format
     */
    static function data_output ( $columns, $data, $isJoin = false )
    {
        global $get;
        global $rStreamInformation;
        $out = array();
        for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
            $row = array();
            if ($get == "streams") {
                $rStreamInformation[intval($data[$i]["id"])] = getStreams(null, true, Array($data[$i]["id"]))[0];
                if (count($rStreamInformation[intval($data[$i]["id"])]["servers"]) == 0) {
                    $rStreamInformation[intval($data[$i]["id"])]["servers"][] = Array("id" => 0, "active_count" => 0, "stream_text" => "Not Available", "uptime_text" => "--", "actual_status" => 0);
                }
                foreach ($rStreamInformation[intval($data[$i]["id"])]["servers"] as $rServer) {
                    for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
                        $column = $columns[$j];
                        // Is there a formatter?
                        if ( isset( $column['formatter'] ) ) {
                            $row[ $column['dt'] ] = ($isJoin) ? $column['formatter']( $data[$i][ $column['field'] ], $data[$i], $rServer ) : $column['formatter']( $data[$i][ $column['db'] ], $data[$i], $rServer );
                        } else if (!isset($column["hide"])) {
                            $row[ $column['dt'] ] = ($isJoin) ? $data[$i][ $columns[$j]['field'] ] : $data[$i][ $columns[$j]['db'] ];
                        }
                    }
                    $out[] = $row;
                }
            } else {
                for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
                    $column = $columns[$j];
                    // Is there a formatter?
                    if ( isset( $column['formatter'] ) ) {
                        $row[ $column['dt'] ] = ($isJoin) ? $column['formatter']( $data[$i][ $column['field'] ], $data[$i] ) : $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
                    } else if (!isset($column["hide"])) {
                        $row[ $column['dt'] ] = ($isJoin) ? $data[$i][ $columns[$j]['field'] ] : $data[$i][ $columns[$j]['db'] ];
                    }
                }
                $out[] = $row;
            }
        }
        return $out;
    }
    /**
     * Paging
     *
     * Construct the LIMIT clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL limit clause
     */
    static function limit ( $request, $columns )
    {
        $limit = '';
        if ( isset($request['start']) && $request['length'] != -1 ) {
            $limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
        } else {
            $limit = "LIMIT 50";
        }
        return $limit;
    }
    /**
     * Ordering
     *
     * Construct the ORDER BY clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @param bool  $isJoin  Determine the the JOIN/complex query or simple one
     *
     *  @return string SQL order by clause
     */
    static function order ( $request, $columns, $isJoin = false )
    {
        $order = '';
        if ( isset($request['order']) && count($request['order']) ) {
            $orderBy = array();
            $dtColumns = SSP::pluck( $columns, 'dt' );
            for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
                // Convert the column index into the column data property
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];
                $columnIdx = array_search( $requestColumn['data'], $dtColumns );
                $column = $columns[ $columnIdx ];
                if ( $requestColumn['orderable'] == 'true' ) {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';
                    $orderBy[] = ($isJoin) ? $column['db'].' '.$dir : '`'.$column['db'].'` '.$dir;
                }
            }
            $order = 'ORDER BY '.implode(', ', $orderBy);
        }
        return $order;
    }
    /**
     * Searching / Filtering
     *
     * Construct the WHERE clause for server-side processing SQL query.
     *
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here performance on large
     * databases would be very poor
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @param  array $bindings Array of values for PDO bindings, used in the sql_exec() function
     *  @param  bool  $isJoin  Determine the the JOIN/complex query or simple one
     *
     *  @return string SQL where clause
     */
    static function filter ( $request, $columns, &$bindings, $isJoin = false, $table = null)
    {
        $globalSearch = array();
        $columnSearch = array();
        $dtColumns = SSP::pluck( $columns, 'dt' );
        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str = $request['search']['value'];
            for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
                $requestColumn = $request['columns'][$i];
                $columnIdx = array_search( $requestColumn['data'], $dtColumns );
                $column = $columns[ $columnIdx ];
                if ( $requestColumn['searchable'] == 'true' ) {
                    if (($column["db"] == "mac") && ($table == "mag_devices")) { $str = base64_encode($str); }
                    $binding = SSP::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
                    $globalSearch[] = ($isJoin) ? $column['db']." LIKE ".$binding : "`".$column['db']."` LIKE ".$binding;
                }
            }
        }
        // Individual column filtering
        for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
            $requestColumn = $request['columns'][$i];
            $columnIdx = array_search( $requestColumn['data'], $dtColumns );
            $column = $columns[ $columnIdx ];
            $str = $requestColumn['search']['value'];
            if ( $requestColumn['searchable'] == 'true' &&
                $str != '' ) {
                if (($column["db"] == "mac") && ($table == "mag_devices")) { $str = base64_encode($str); }
                $binding = SSP::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
                $columnSearch[] = ($isJoin) ? $column['db']." LIKE ".$binding : "`".$column['db']."` LIKE ".$binding;
            }
        }
        // Combine the filters into a single string
        $where = '';
        if ( count( $globalSearch ) ) {
            $where = '('.implode(' OR ', $globalSearch).')';
        }
        if ( count( $columnSearch ) ) {
            $where = $where === '' ?
                implode(' AND ', $columnSearch) :
                $where .' AND '. implode(' AND ', $columnSearch);
        }
        if ( $where !== '' ) {
            $where = 'WHERE '.$where;
        }
        return $where;
    }
    /**
     * Perform the SQL queries needed for an server-side processing requested,
     * utilising the helper functions of this class, limit(), order() and
     * filter() among others. The returned array is ready to be encoded as JSON
     * in response to an SSP request, or can be modified if needed before
     * sending back to the client.
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $sql_details SQL connection details - see sql_connect()
     *  @param  string $table SQL table to query
     *  @param  string $primaryKey Primary key of the table
     *  @param  array $columns Column information array
     *  @param  array $joinQuery Join query String
     *  @param  string $extraWhere Where query String
     *  @param  string $groupBy groupBy by any field will apply
     *  @param  string $having HAVING by any condition will apply
     *
     *  @return array  Server-side processing response array
     *
     */
    static function simple ( $request, $sql_details, $table, $primaryKey, $columns, $joinQuery = NULL, $extraWhere = '', $groupBy = '', $having = '')
    {
        $bindings = array();
        $db = SSP::sql_connect( $sql_details );
        // Build the SQL query string from the request
        $limit = SSP::limit( $request, $columns );
        $order = SSP::order( $request, $columns, $joinQuery );
        $where = SSP::filter( $request, $columns, $bindings, $joinQuery, $table);
        // IF Extra where set then set and prepare query
        if($extraWhere)
            $extraWhere = ($where) ? ' AND '.$extraWhere : ' WHERE '.$extraWhere;
        $groupBy = ($groupBy) ? ' GROUP BY '.$groupBy .' ' : '';
        $having = ($having) ? ' HAVING '.$having .' ' : '';
        // Main query to actually get the data
        if($joinQuery){
            $col = SSP::pluck($columns, 'db', $joinQuery);
            $query =  "SELECT SQL_CALC_FOUND_ROWS ".implode(", ", $col)."
             $joinQuery
             $where
             $extraWhere
             $groupBy
       $having
             $order
             $limit";
        }else{
            $query =  "SELECT SQL_CALC_FOUND_ROWS `".implode("`, `", SSP::pluck($columns, 'db'))."`
             FROM `$table`
             $where
             $extraWhere
             $groupBy
       $having
             $order
             $limit";
        }
        $data = SSP::sql_exec( $db, $bindings,$query);
        // Data set length after filtering
        $resFilterLength = SSP::sql_exec( $db,
            "SELECT FOUND_ROWS()"
        );
        $recordsFiltered = $resFilterLength[0][0];
        // Total data set length
        $resTotalLength = SSP::sql_exec( $db,
            "SELECT COUNT(`{$primaryKey}`)
             FROM   `$table`"
        );
        if ($rPermissions["is_admin"]) {
            $recordsTotal = $resTotalLength[0][0];
        } else {
            $recordsTotal = $recordsFiltered;
        }
        /*
         * Output
         */
        return array(
            "draw"            => intval( $request['draw'] ),
            "recordsTotal"    => intval( $recordsTotal ),
            "recordsFiltered" => intval( $recordsFiltered ),
            "data"            => SSP::data_output( $columns, $data, $joinQuery )
        );
    }
    /**
     * Connect to the database
     *
     * @param  array $sql_details SQL server connection details array, with the
     *   properties:
     *     * host - host name
     *     * db   - database name
     *     * user - user name
     *     * pass - user password
     * @return resource Database connection handle
     */
    static function sql_connect ( $sql_details )
    {
        try {
            $db = @new PDO(
                "mysql:host={$sql_details['host']};dbname={$sql_details['db']}",
                $sql_details['user'],
                $sql_details['pass'],
                array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
            );
            $db->query("SET NAMES 'utf8'");
        }
        catch (PDOException $e) {
            SSP::fatal(
                "An error occurred while connecting to the database. ".
                "The error reported by the server was: ".$e->getMessage()
            );
        }
        return $db;
    }
    /**
     * Execute an SQL query on the database
     *
     * @param  resource $db  Database handler
     * @param  array    $bindings Array of PDO binding values from bind() to be
     *   used for safely escaping strings. Note that this can be given as the
     *   SQL query string if no bindings are required.
     * @param  string   $sql SQL query to execute.
     * @return array         Result from the query (all rows)
     */
    static function sql_exec ( $db, $bindings, $sql=null )
    {
        // Argument shifting
        if ( $sql === null ) {
            $sql = $bindings;
        }
        $stmt = $db->prepare( $sql );
        //echo $sql;
        // Bind parameters
        if ( is_array( $bindings ) ) {
            for ( $i=0, $ien=count($bindings) ; $i<$ien ; $i++ ) {
                $binding = $bindings[$i];
                $stmt->bindValue( $binding['key'], $binding['val'], $binding['type'] );
            }
        }
        // Execute
        try {
            $stmt->execute();
        }
        catch (PDOException $e) {
            SSP::fatal( "An SQL error occurred: ".$e->getMessage() );
        }
        // Return all
        return $stmt->fetchAll();
    }
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Internal methods
     */
    /**
     * Throw a fatal error.
     *
     * This writes out an error message in a JSON string which DataTables will
     * see and show to the user in the browser.
     *
     * @param  string $msg Message to send to the client
     */
    static function fatal ( $msg )
    {
        echo json_encode( array(
            "error" => $msg
        ) );
        exit(0);
    }
    /**
     * Create a PDO binding key which can be used for escaping variables safely
     * when executing a query with sql_exec()
     *
     * @param  array &$a    Array of bindings
     * @param  *      $val  Value to bind
     * @param  int    $type PDO field type
     * @return string       Bound key to be used in the SQL where this parameter
     *   would be used.
     */
    static function bind ( &$a, $val, $type )
    {
        $key = ':binding_'.count( $a );
        $a[] = array(
            'key' => $key,
            'val' => $val,
            'type' => $type
        );
        return $key;
    }
    /**
     * Pull a particular property from each assoc. array in a numeric array,
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @param  bool  $isJoin  Determine the the JOIN/complex query or simple one
     *  @return array        Array of property values
     */
    static function pluck ( $a, $prop, $isJoin = false )
    {
        $out = array();
        for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
            $out[] = ($isJoin && isset($a[$i]['as'])) ? $a[$i][$prop]. ' AS '.$a[$i]['as'] : $a[$i][$prop];
        }
        return $out;
    }
}

echo json_encode(SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns, $joinQuery, $extraWhere));
?>