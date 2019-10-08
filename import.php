<?php
include "functions.php";
if (!isset($_SESSION['user_id'])) {
  header("Location: ./login.php");
  exit;
}
if (isset($_POST["import"])) {
  $file = '';
  if (!empty($_POST['m3u_url'])) {
    $file = file_get_contents($_POST['m3u_url']);
  } else if (!empty($_FILES['m3u_file']['tmp_name'])) {
    $file = file_get_contents($_FILES['m3u_file']['tmp_name']);
  }

  preg_match_all('/(?P<tag>#EXTINF:-1)|(?:(?P<prop_key>[-a-z]+)=\"(?P<prop_val>[^"]+)")|(?<something>,[^\r\n]+)|(?<url>http[^\s]+)/', $file, $match);
  $count = count($match[0]);
  $result = [];
  $index = -1;

  for ($i = 0; $i < $count; $i++) {
    $item = $match[0][$i];

    if (!empty($match['tag'][$i])) {
      //is a tag increment the result index
      ++$index;
    } elseif (!empty($match['prop_key'][$i])) {
      //is a prop - split item
      $result[$index][$match['prop_key'][$i]] = $match['prop_val'][$i];
    } elseif (!empty($match['something'][$i])) {
      //is a prop - split item
      $result[$index]['something'] = $item;
    } elseif (!empty($match['url'][$i])) {
      $result[$index]['url'] = $item;
    }
  }

  $rArray = array("type" => 1, "added" => time(), "read_native" => 0, "stream_all" => 0, "direct_source" => 0, "gen_timestamps" => 0, "transcode_attributes" => array(), "stream_display_name" => "", "stream_source" => array(), "category_id" => 0, "stream_icon" => "", "notes" => "", "custom_sid" => "", "custom_ffmpeg" => "", "transcode_profile_id" => 0, "enable_transcode" => 0, "auto_restart" => "[]", "allow_record" => 1, "rtmp_output" => 0, "epg_id" => 0, "channel_id" => "", "epg_lang" => "", "tv_archive_server_id" => 0, "tv_archive_duration" => 0, "delay_minutes" => 0, "external_push" => array());
  $rCols = implode(',', array_keys($rArray));
  $rQuery = "INSERT INTO `streams`(" . $rCols . ") VALUES ";

  foreach ($result as $stream) {
    $rArray["stream_display_name"] = $stream["tvg-name"];
    $rArray["stream_source"] = $stream["url"];
    if (isset($stream["tvg-logo"])) {
      $rArray["stream_icon"] = $stream["tvg-logo"];
    }
    $rArray["channel_id"] = $stream["tvg-id"];

    foreach (array_values($rArray) as $rValue) {
      isset($rValues) ? $rValues .= ',' : $rValues = '';
      if (is_array($rValue)) {
        $rValue = json_encode($rValue);
      }
      if (is_null($rValue)) {
        $rValues .= 'NULL';
      } else {
        $rValues .= '\'' . $db->real_escape_string($rValue) . '\'';
      }
    }

    $rQuery .= "(" . $rValues . "),";
    unset($rValues);
  }

  $rQuery = substr($rQuery, 0, -1) . ";";
  $db->query($rQuery);
}
include "header.php"; ?>
<div class="wrapper boxed-layout">
  <div class="container-fluid">
    <!-- start page title -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box">

          <h4 class="page-title">Import Streams</h4>
        </div>
      </div>
    </div>


    <!-- end page title -->

    <div class="row">
      <div class="col-xl-12">
        <div class="card">
          <div class="card-body">
            <form enctype="multipart/form-data" action="./import.php" method="POST" id="import">

              <div class="form-group row mb-4">
                <label class="col-md-4 col-form-label" for="m3u_url">M3U URL</label>
                <div class="col-md-8">
                  <input type="text" class="form-control" id="m3u_url" name="m3u_url" />
                </div>
              </div>
              <div class="form-group row mb-4">
                <label class="col-md-4 col-form-label" for="m3u_file">M3U File</label>
                <div class="col-md-8">
                  <input type="file" id="m3u_file" name="m3u_file" />
                </div>
              </div>
              <div class="form-group row mb-4">
                <label class="col-md-4 col-form-label" for="category_id">Category Name</label>
                <div class="col-md-8">
                  <select name="category_id" id="category_id" class="form-control" data-toggle="select2">
                    <?php foreach ($rCategories as $rCategory) { ?>
                      <option <?php if (isset($rStream)) {
                                  if (intval($rStream["category_id"]) == intval($rCategory["id"])) {
                                    echo "selected ";
                                  }
                                } else if ((isset($_GET["category"])) && ($_GET["category"] == $rCategory["id"])) {
                                  echo "selected ";
                                } ?>value="<?= $rCategory["id"] ?>"><?= $rCategory["category_name"] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <input name="import" type="submit" class="btn btn-primary" value="Import" />
            </form>
          </div> <!-- end col -->
        </div> <!-- end row -->
      </div>
    </div>
  </div> <!-- end container -->
</div>
<!-- end wrapper -->
<!-- Footer Start -->
<footer class="footer">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12  text-center">Xtream Codes - Admin UI</div>
    </div>
  </div>
</footer>
<!-- end Footer -->
<!-- Vendor js -->
<script src="assets/js/vendor.min.js"></script>
<script src="assets/libs/jquery-toast/jquery.toast.min.js"></script>

<!-- third party js -->
<script src="assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables/dataTables.bootstrap4.js"></script>
<script src="assets/libs/datatables/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables/responsive.bootstrap4.min.js"></script>
<script src="assets/libs/datatables/dataTables.buttons.min.js"></script>
<script src="assets/libs/datatables/buttons.bootstrap4.min.js"></script>
<script src="assets/libs/datatables/buttons.html5.min.js"></script>
<script src="assets/libs/datatables/buttons.flash.min.js"></script>
<script src="assets/libs/datatables/buttons.print.min.js"></script>
<script src="assets/libs/datatables/dataTables.keyTable.min.js"></script>
<script src="assets/libs/datatables/dataTables.select.min.js"></script>
<script src="assets/libs/pdfmake/pdfmake.min.js"></script>
<script src="assets/libs/pdfmake/vfs_fonts.js"></script>
<!-- third party js ends -->