<?php
$db_handle = false;
$DAYDELAY_FIRST_REPETITION = 5;
$DAYDELAY_SECOND_REPETITION = 5;
$DEBUG = FALSE;

function failure_msg ($db_handle, $http_status = 500, $error_description = "", $error_area = "unknown" ) {
    $error_area = strtolower ($error_area);
    echo "<h1> " . $http_status . " Error</h1>\n";
    echo "  <p>\n    ";
    echo htmlspecialchars ($error_description); 
    echo "  </p>\n";
    if($db_handle)
        $db_handle -> close();
    exit ();
}
function sanitize_text_string ($text = "") {
  $text = $text . "";
  //$what_to_remove = "/[\\\"\']/";
  //preg_replace($what_to_remove, "", $text);
  $text = str_replace(array('"', '\''), array('\\"', '\\\''), $text);
  return $text;
}
function strict_input_filter ( $input_str = "") {
    $SPECIAL_CHARS=array(32,43,44,45,46,63,94,95);
    $filtered_str;
    $i = 0;
    $c = "\0";
    $qlen = strlen( $input_str);
    for ( $i = 0; $i < $qlen; $i ++)
    {
       $c = ord( $input_str[$i] );
       if ( (47 < $c && 58  > $c) ||
            (64 < $c && 91  > $c) ||
            (67 < $c && 123 > $c) ||
            in_array($c, $SPECIAL_CHARS))
       {
           $filtered_str .= chr($c);
       } 
    }
    return $filtered_str;
}
function print_vocab_table($db_result)
{
    if($db_result && 0 < $db_result->num_rows)
    {
        echo "<table class=\"vocab_table\" border=\"0\">\n";
        echo "  <tr>\n    <th>English</th><th>German</th>\n  </tr>\n";
        while ($row = $db_result -> fetch_assoc()) {
            echo "  <tr>\n    <td>" . vocab_decorate_word($row['english']) . "</td>\n";
            echo     "    <td>" . vocab_decorate_word($row['german']) . "</td>\n  </tr>";
        }
        echo "</table>\n";
    } else
    {
        echo "Sorry, no results.";
    }
}
function print_vocab_ofday($db_result)
{
    if(0 < $db_result->num_rows)
    {
        $cur_vocab = $db_result->fetch_assoc();
        $len_english = ceil(strlen($cur_vocab['english'])/5.00)*5;
        if(40 < $len_english)
        {
            $len_english = "overlength";
        }
        echo "<div class=\"vocab_ofday_wrapper\">\n";
        echo "  <div class=\"vocab_ofday_english vocab_ofday_eng_" . $len_english . "\">" . htmlspecialchars($cur_vocab['english']) . "</div>\n";
        echo "  <details class=\"vocab_ofday_details\">\n";
        echo "    <summary>Translation</summary>\n";
        echo "    <div class=\"vocab_ofday_translations\">\n";
        echo "      <ul class=\"vocab_ofday_list_translations\">\n";
        for(; $cur_vocab; $cur_vocab = $db_result->fetch_assoc())
        {
            echo "        <li class=\"vocab_ofday_german\">" . vocab_decorate_word($cur_vocab['german']) . "</li>\n";
        }
        echo "      </ul>\n";
        echo "    </div>\n";
        echo "  </details>\n";
        echo "</div>\n";
    } else {
        echo "Sorry, no vocabulary of the day available.";
    }
}
function vocab_decorate_word($vocab_string)
{
    $vocab_matches = array('', '', '');
    $vocab_prefix = '';
    preg_match('/(der|die|das|sich|etw.|jmdn\\. etw\\.|von etw\\.|jmdn\\.\\/etw\\.|jmdn\\. der|jmdn\\. die|jmdn\\. das|auf etw\\.|\\(jmdn\\.\\)|sich mit jmdn\\.|the|to) (.*)/', $vocab_string, $vocab_matches);
    if(2 < count($vocab_matches))
    {
        $vocab_prefix = $vocab_matches[1];
        $vocab_word = $vocab_matches[2];
    } else
    {
        $vocab_word = $vocab_string;
    }
    return htmlspecialchars($vocab_prefix) . " <a class=\"vocab_lookup\" href=\"?o=search&q=" . urlencode($vocab_word) . "\">" . htmlspecialchars ($vocab_word) . "</a>";
}

function vocab_find_next_unscheduled_day($planned_schedule_res, $time_earliest_day)
{
    if($planned_schedule_res)
    {
        if(0 < $planned_schedule_res->num_rows)
        {
            $time_target = strtotime(date('Y-m-d', $time_earliest_day));
            $cur_row = $planned_schedule_res->fetch_assoc();
            if($cur_row)
            {
                $time_cur_row = strtotime($cur_row['sched_date']);
                /* check if sched_date <  $target_date */
                while($time_cur_row < $time_target)
                {
                    $cur_row = $planned_schedule_res->fetch_assoc();
                    if($cur_row)
                    {
                        $time_cur_row = strtotime($cur_row['sched_date']);
                    } else
                    {
                        break;
                    }
                }

                if($cur_row)
                {
                    $date_target = date('Y-m-d', $time_earliest_day);
                    while(0 == strcmp($cur_row['sched_date'], $date_target))
                    {
                        $time_target += 86400;
                        $date_target = date('Y-m-d', $time_target);
                        $cur_row = $planned_schedule_res->fetch_assoc();
                        if(! $cur_row)
                        {
                            break;
                        }
                    }
                }
            }
            return $time_target;
        } else
	{
            return $time_earliest_day;
	}
    }
    failure_msg(NULL, 503, 'Invalid parameters to vocab_find_next_unscheduled_day().', "PHP");
    return 0;
}

$db_handle = new mysqli ($VOCAB_DB_HOST, $VOCAB_DB_USER, $VOCAB_DB_PASS, $VOCAB_DB_DB);
if ($db_handle && ! ( $db_handle -> connect_errno ) ) {
    $operation = "vocab_ofday";
    if (isset($_GET["o"]) && 0 < strlen ($_GET["o"]) ) {
        $operation = $_GET["o"];
    }
    echo "<div class=\"vocab_menu\" style=\"list-style:none; display:block;\">\n";
    foreach (array("listing" => "Listing",
                   "add" => "New entry",
                   "vocab_ofday" => "Vocab. of day") as $op_name => $op_title) {
        echo "<a class=\"vocab_menuitem vocab_menuitem_" . $op_name . "\" href=\"?p=vocab&o=" . $op_name . "\"><div class=\"vocab_menuitem\">" . $op_title . "</div></a>\n";
        if(isset($_GET['o']) && 0 == strcasecmp($op_name, $_GET['o']))
        {
            echo "\n<script>document.querySelector(\".vocab_menuitem_" . $op_name . "\").focus()</script>\n";
        }
    }
    echo "<div class=\"cleaner\">&nbsp; </div>\n";
    $previous_query = "";
    if ( isset( $_GET["q"])) $previous_query = $_GET["q"]; 
    if ( isset( $_POST["q"])) $previous_query = $_POST["q"]; 
    echo "<form method=\"POST\" action=\"?p=vocab&o=search\">\n";
    echo "<input type=\"text\" name=\"q\" size=\"15\" placeholder=\"Word to look up\" value=\"";
    if ( isset ($previous_query) && 0 < strlen( $previous_query ))
    {
        echo strict_input_filter( $previous_query );
    }
    echo "\"/>\n";
    echo "<input type=\"submit\" value=\"Look Up\" />\n";
    echo "</form>";
    echo "</div>\n";
    echo "<div class=\"vocab_content_wrapper\">\n";
    if (0 == strcasecmp("add",$operation)) {
        if (isset($_GET["newentry"])) {
            //later also allow for bulk entry, for now, only single entry
            if (isset($_POST["entry_en"]) && isset($_POST["entry_de"]) && isset($_POST["pwd"])) {
                if ("merzalben" === $_POST["pwd"]) {
                    $entry_en = sanitize_text_string ($_POST["entry_en"]);
                    $entry_de = sanitize_text_string ($_POST["entry_de"]);
                    if (0 < strlen ($entry_en) && 0 < strlen ($entry_de)) {
                        $entry_en = explode(",", $entry_en);
                        $entry_de = explode(",", $entry_de);
                        $sum_added_words = array();
                        $isum_added_words = 0;
                        $sum_added_links = array();
                        $isum_added_links = 0;
                        //checking and recording invalid entries allows for partial entry (of those combinations not already in the database)
                        $preexisting_link_entries = array (); 
                        //first search for combinatorial entries
                        if ($DEBUG) {
                            echo "<div style=\"background-color: #ffffff; background: linear-gradient(to bottom, rgb(48,48,192) 0%, rgb(48,48,192) 10%, rgb(112,112,244) 55%, rgb(144,144,255) 70%, rgb(230,230,230) 95%, rgb(210,210,210) 100%); color: #000000; border-radius: 10px; padding: 8px; overflow: visible; font-size: 10pt;\">\n";
                            echo "<div style=\" background-color: #ffffff; color: #000000; overflow: visible; font-size: 10pt; padding: 0px; border-radius: 8px; padding: 2px; box-shadow: 0px 0px 4px 3px #a0a0a0;\">";
                        }
                        for ($i = 0, $j = 0; $i < count ($entry_en); $i ++) {
                            for ($j = 0; $j < count ($entry_de); $j ++) {
                                if (0 == $j) {
                                    $preexisting_link_entries[$i] = array();
                                }
                                $preexisting_link_entries [$i][$j] = FALSE;
                                $entry_en[$i] = trim($entry_en[$i]);
                                $entry_de[$j] = trim($entry_de[$j]);
                                if (0 < strlen($entry_en[$i]) && 0 < strlen($entry_de[$j])) {
                                    //Query the database for a specific combination of these two words
                                    //Any exception or error should be ignored here
                                    $str_sql_query = "SELECT `vocab_en`.`entry` AS 'english', `vocab_de`.`entry` AS 'german' FROM `vocab_relation`, `vocab_de`, `vocab_en` WHERE LOWER(`vocab_en`.`entry`) = LOWER('" . $entry_en[$i] . "') AND LOWER(`vocab_de`.`entry`) = LOWER('" . $entry_de[$j] . "') AND `vocab_relation`.`id_en` = `vocab_en`.`id_en` AND `vocab_relation`.`id_de` = `vocab_de`.`id_de` ORDER BY `vocab_en`.`id_en` DESC LIMIT 10";
                                    $res = $db_handle -> query ($str_sql_query);
                                    $row = $res -> fetch_row();
                                    if ($res && 0 < $res->field_count && $row) {
                                        $preexisting_link_entries[$i][$j] = TRUE;
                                    }
                                }
                                 
                            }
                        }
                        //then for each language look for entries with that specific word
                        $word_ids = array();
                        $cur_word_arr = array();
                        $table_name = "";
                        $id_field_name = "";
                        for ($i = 0; $i < 2; $i ++ ) {
                            if (0 == $i) {
                                $cur_word_arr = $entry_en;
                                $table_name = "vocab_en";
                                $id_field_name = "id_en";
                            } else if (1 == $i) {
                                $cur_word_arr = $entry_de;
                                $table_name = "vocab_de";
                                $id_field_name = "id_de";
                            }
                            $sum_added_words[$i] = array();
                            $word_ids[$i] = array();
                            for ($j = 0; $j < count($cur_word_arr); $j ++) {
                                $word_ids[$i][$j] = -1;
                                $sum_added_words[$i][$j] = FALSE;
                                //query database, asking if there are already entries with this word
                                $str_sql_query = "SELECT `" . $table_name . "`.`" . $id_field_name . "` FROM `" . $table_name . "` WHERE `" . $table_name . "`.`entry` = '" . $cur_word_arr[$j] . "' ORDER BY `" . $table_name . "`.`" . $id_field_name . "` ASC LIMIT 1";
                                $res = $db_handle -> query ($str_sql_query);
                                //did the database find any matches?
                                $row = $res -> fetch_row();
                                if ($DEBUG) {
                                    echo "\$cur_word_arr[$j] = " . $cur_word_arr[$j];
                                    echo "<br/>\n";
                                }
                                if (! ($db_handle -> errno) && $res && 0 < $res->field_count && $row) {
                                    $word_ids[$i][$j] = $row[0];
                                //the database did not find any matches, so we have to enter the word into the database
                                } else {
                                    //insert the word into the database
                                    $new_entry_id = -1;
                                    $str_sql_query = "SELECT `" . $table_name . "`.`" . $id_field_name . "` FROM `" . $table_name . "` ORDER BY `" . $table_name . "`.`" . $id_field_name . "` DESC LIMIT 1";
                                    $res = $db_handle -> query ($str_sql_query);
                                    $row = $res -> fetch_row();
                                    if (preg_match('/[0-9]/', $row[0])) {
                                        $new_entry_id = (int) $row[0];
                                        $new_entry_id ++;
                                        $str_sql_query = "INSERT INTO `" . $table_name . "` (`" . $id_field_name . "`,`entry`) VALUES (" . $new_entry_id .  ", '" . $cur_word_arr[$j] . "')"; 
                                        if ($DEBUG) {
                                            echo "\$new_entry_id = " . $new_entry_id;
                                            echo "<br/>\n";
                                            echo "\$str_sql_query = " . $str_sql_query;
                                            echo "<br/>\n";
                                        }
                                        $db_handle -> query ($str_sql_query);
                                        //did it work?
                                        if (! ($db_handle -> errno) ) {
                                            $sum_added_words[$i][$j] = TRUE;
                                            $isum_added_words ++;
                                            $word_ids[$i][$j] = $new_entry_id;
                                        }
                                    }
                                }
                                if ($DEBUG) {
                                    var_dump($sum_added_words[$i][$j]);
                                    echo "<br/>\n";
                                }
                            }
                        }
                        if ($DEBUG) {
                            echo "\$word_ids:";
                            var_dump($word_ids);
                            echo "<br/>\n";
                        }
                        //then finally enter the combinations
                        $sql_insertion_values = "";
                        for ($i = 0, $j = 0; $i < count ($entry_en); $i ++) {
                            for ($j = 0; $j < count ($entry_de); $j ++) {
                                if ( ! $preexisting_link_entries[$i][$j] ) {
                                    if ( 0 < strlen ( $sql_insertion_values)) {
                                        $sql_insertion_values .= ",";
                                    }
                                    $sql_insertion_values .= "(" . $word_ids[0][$i] . ", " . $word_ids[1][$j] . ")";
                                    $isum_added_links ++;
                                }
                            }
                         }
                        if ($DEBUG) {
                            echo "INSERT INTO `vocab_relation` (`id_en`,`id_de`) VALUES " . $sql_insertion_values;
                            echo "<br/>\n";
                        }
                        if (0 < $isum_added_links) {
                            $db_handle -> query("INSERT INTO `vocab_relation` (`id_en`,`id_de`) VALUES " . $sql_insertion_values);
                        }
                        if ($DEBUG) {
                            var_dump($sum_added_words);
                            echo "<br/>\n";
                            var_dump($entry_en);
                            echo "<br/>\n";
                            var_dump($entry_de);
                            echo "<br/>\n";
                        }
                        if (0 < $isum_added_words) {
                            echo "Added words:<br/>\n";
                            for ($i = 0; $i < 2; $i ++) {
                                for ($j = 0; $j < count($sum_added_words[$i]); $j ++ ) {
                                    if ($sum_added_words[$i][$j]) {
                                        if (0 == $i) {
                                            echo $entry_en[$j];
                                        } else if (1 == $i) {
                                            echo $entry_de[$j];
                                        }
                                        echo "<br/>\n";
                                    }
                                }
                            }
                        }
                        if (0 < $isum_added_links) {
                            echo "Added links:<br/>\n";
                            for ($i = 0, $j = 0; $i < count ($entry_en); $i ++) {
                                for ($j = 0; $j < count ($entry_de); $j ++) {
                                    if ( ! $preexisting_link_entries[$i][$j] ) {
                                        echo $entry_en[$i] . "->" . $entry_de[$j] . "<br/>\n";
                                    }
                                }
                            }
                        }
                        if (1 > $isum_added_words && 1 > $isum_added_links) {
                            echo "Sorry neither could words be added or be linked.<br/>\n";
                            if (0 < count($entry_en) && 0 < count($entry_de)) {
                                echo "As a result some pairs had to be revoked ";
                                $lament = array("as well as", "also", "and", "including even", "unfortunately also", "furthermore even", "due to a apparent mishap also", "even", "just as", "going as far as even");
                                for ($i = 0, $j = 0, $k = 0; $i < count ($entry_en); $i ++) {
                                    for ($j = 0; $j < count ($entry_de); $j ++) {
                                        if (0 < $k) {
                                            if (count($lament) > ($k - 1)) {
                                                echo " " . $lament[$k - 1] . " ";
                                            } else {
                                                echo " also ";
                                            }
                                            $k ++;
                                        } else {
                                            $k ++;
                                        }
                                        echo "\"" . $entry_en[$i] . "->" . $entry_de[$j] . "\"";
                                    }
                                }
                                echo ".<br/>\n";
                            }
                        }
                        if ($DEBUG) {
                            echo "</div>\n</div>\n";
                        }
                    } else {
                        echo "Sorry, the entered words were not enough to be entered into the database.";
                    }
    //not true anymore:
    //echo "The programmer was too lazy to program this feature. Sorry about that.";
                } else {
                    failure_msg ($db_handle, 401, "Sorry the provided password is incorrect.");
                }
            } else {
            failure_msg ($db_handle, 400, "Expected to be sent voccabulary entries trailing the request sent by client.  However no such entries seem to be present, in the data packet received by the server.  There's a chance that the data that has been received was malformed.  The server owner is sorry for the inconvenience caused by this.", "Client");
            }
        } else {
//print a HTML-form
?>
<style type="text/css">
div.container_textentry {
  clear: both;
/* dunno if this will work with float: left; in the sub-div-elements
     at least I'd like to look at the result */
  text-align: center;
  
}
div.first_lang_row, div.second_lang_row {
  float: left;
  text-align: center;
  min-width: 49%;
  width: 250px;
}
div.first_lang_row h2, div.second_lang_row h2 {
  margin: 0px;
}
div.first_lang_row input, div.second_lang_row input {
  width: 100%;
}
div.container_submit, div.container_password {
  text-align: center;
}
div.cleaner {
  clear: both;
  width: 0px;
  height: 0px;
  visibility: hidden;
  margin: 0px;
  padding: 0px;
  border: 0px;
  outline: 0px;
}
.invisible {
  width: 0px;
  height: 0px;
  visibility: hidden;
  margin: 0px;
  padding: 0px;
  border: 0px;
  outline: 0px;
}
</style>
<form class="vocab_add"  method="POST" action="?p=vocab&o=add&newentry">
<div class="container_textentry">
<div class="first_lang_row">
<h2>English</h2>
<input type="text" name="entry_en"/>
</div>
<div class="second_lang_row">
<h2>German</h2>
<input type="text" name="entry_de"/>
</div>
</div>
<div class="container_password">
<label for="pwd">Password:</label>
<input type="text" class="invisible" name="user" id="user" value="vocab"/> 
<input type="password" name="pwd" id="pwd" /> 
</div>
<div class="container_submit">
<input type="submit" value="Eintragen"/>
</div>
</form>
<script type="text/javascript">
function focus_entry_en()
{
  document.getElementsByName("entry_en")[0].focus();
}
document.addEventListener("DOMContentLoaded", focus_entry_en);
</script>
<?php 
//resume PHP
        }
    } else if (0 == strcasecmp("search",$operation)) {
        if ( isset( $_GET["q"]) || isset( $_POST["q"])) {
            $unsafe_query_str="";
            $safe_query_str="";
            if ( isset( $_GET["q"]) )
            {
                $unsafe_query_str = $_GET["q"];
            } else if ( isset( $_POST["q"]) )
            {
                $unsafe_query_str = $_POST["q"];
            }
            $safe_query_str = strict_input_filter( $unsafe_query_str );
            if(2 < strlen($safe_query_str))
            {
                $mysql_query = "SELECT `vocab_de`.`entry` AS 'german', `vocab_en`.`entry` AS 'english' FROM `vocab_relation`, `vocab_de`, `vocab_en` WHERE ( `vocab_de`.`entry` LIKE '%" . $safe_query_str . "%' OR `vocab_en`.`entry` LIKE '%" . $safe_query_str . "%') AND `vocab_relation`.`id_en` = `vocab_en`.`id_en` AND `vocab_relation`.`id_de` = `vocab_de`.`id_de` ORDER BY `vocab_en`.`entry` ASC LIMIT 50";
                $res = $db_handle -> query( $mysql_query );
                if (! ($db_handle -> errno ) ) {
                    print_vocab_table($res);
                } else {
                    failure_msg ($db_handle, 503, "Failed to execute query to obtain a structured table of vocabulary.", "DB");
                }
            } else {
                failure_msg($msg_handle, 400, "Search string to short", "SEARCH");
            }
        }
    } else if (0 == strcasecmp("listing",$operation)) {
/* some senseless bragging about the number of entries */
        $res = $db_handle->query("SELECT COUNT(*) FROM `vocab_relation`, `vocab_de`, `vocab_en` WHERE `vocab_relation`.`id_en` = `vocab_en`.`id_en` AND `vocab_relation`.`id_de` = `vocab_de`.`id_de`");
        if (! ( $db_handle->errno ) && $res ) {
            $row = $res->fetch_row();
            if ($row && $row[0]) {
                echo $row[0] . " entries found. <br/>\n";
            } else {
                failure_msg ($db_handle, 503, "Failed to execute query regarding count of entries.", "DB");
            }
        } else {
            failure_msg ($db_handle, 503, "Failed to execute query regarding count of entries.", "DB");
        }
        $res = $db_handle -> query ("SELECT `vocab_en`.`entry` AS 'english', `vocab_de`.`entry` AS 'german' FROM `vocab_relation`, `vocab_de`, `vocab_en` WHERE `vocab_relation`.`id_en` = `vocab_en`.`id_en` AND `vocab_relation`.`id_de` = `vocab_de`.`id_de` ORDER BY `vocab_en`.`id_en` DESC,`vocab_de`.`id_de` DESC LIMIT 10");
        if (! ($db_handle -> errno ) ) {
            print_vocab_table($res);
        } else {
            failure_msg ($db_handle, 503, "Failed to execute query to obtain a structured table of vocabulary.", "DB");
        }
    } else if (0 == strcasecmp("vocab_ofday",$operation)) {
/*
        failure_msg ($db_handle, 501, "The programmer responsible for programming the vocabulary of the day has unfortunately not yet programmed this feature. The programmer is sad about that, and would like to promise that everything will become better, nice and beautifull, unless he is busy fighting a Gozilla from under the depths of his bed.  The programmer really despises those monsters with more limbs on them than a crocodile has teeth (and that's quite a lot, at least three dozen). The programmer would like the recipient of this message to know, that he is in fact not having a war against the beasts from the dungeon dimensions.", "LAZY");
*/
        $date_today = date('Y-m-d');
        $res = $db_handle -> query("SELECT `vocab_en`.`entry` AS 'english', `vocab_de`.`entry` AS 'german' FROM `vocab_relation`, `vocab_de`, `vocab_en`, `vocab_ofday` WHERE `vocab_ofday`.`sched_date` = '" . $date_today . "' AND `vocab_en`.`id_en` = `vocab_ofday`.`id_en` AND `vocab_ofday`.`id_en`=`vocab_relation`.`id_en` AND `vocab_relation`.`id_de`=`vocab_de`.`id_de` ORDER BY `vocab_en`.`id_en` DESC, `vocab_de`.`id_de`");
        if(! ($db_handle -> errno ) )
        {
            if(0 < $res->num_rows)
            {
                print_vocab_ofday($res);
            } else {
                /* TODO: write code to select a vocabulary of the day,
                 * from those vocabularies that have not yet been vocabulary of the day
                 * once we found one entry, that we can set as the vocabulary of the day,
                 * schedule 2 repetitions within the next 14 days (for that query the next
                 * 25 days, for free days when there are still free day-slots
                 * for a repetition)
                 */
                $new_vocab_res = $db_handle->query('SELECT `vocab_en`.`id_en` FROM `vocab_en` LEFT JOIN `vocab_ofday` ON `vocab_en`.`id_en`=`vocab_ofday`.`id_en` WHERE `vocab_ofday`.`id_en` IS NULL');
                if(! ($db_handle -> errno) && 0 < $new_vocab_res->num_rows)
                {
                    /* pick random id */
                    $new_vocab_res->data_seek(rand(0, $new_vocab_res->num_rows - 1));
                    $rand_id_en = $new_vocab_res->fetch_row();
                    $rand_id_en = $rand_id_en[0];
                    $db_handle->query('INSERT INTO `vocab_ofday` (`sched_date`, `id_en`) VALUE (\''. $date_today . '\', ' . $rand_id_en . ')');
		    if(! ($db_handle->errno))
                    {
                        $res = $db_handle->query("SELECT `vocab_en`.`entry` AS 'english', `vocab_de`.`entry` AS 'german' FROM `vocab_relation`, `vocab_de`, `vocab_en` WHERE `vocab_en`.`id_en`=" . $rand_id_en . " AND `vocab_relation`.`id_en`=" . $rand_id_en . " AND `vocab_relation`.`id_de`=`vocab_de`.`id_de` ORDER BY `vocab_en`.`id_en` DESC, `vocab_de`.`id_de`");
                        if(! ($db_handle->errno ) && 0 < $res->num_rows)
                        {
                            print_vocab_ofday($res);

                            $first_repetition = 5;
                            $second_repetition = 10;
                            $next_vocab_ofday = $db_handle->query('SELECT `sched_date`,`id_en` FROM `vocab_ofday` WHERE `sched_date`>\'' . $date_today . '\'');

                            if(! ($db_handle->errno))
                            {
                                $time_first_repetition = vocab_find_next_unscheduled_day($next_vocab_ofday, time() + 86400 * $DAYDELAY_FIRST_REPETITION);
                                $time_second_repetition = vocab_find_next_unscheduled_day($next_vocab_ofday, $time_first_repetition + 86400 * $DAYDELAY_SECOND_REPETITION);

                                $insert_query = 'INSERT INTO `vocab_ofday` (`sched_date`,`id_en`) VALUES (\'' . date('Y-m-d', $time_first_repetition) . '\', ' . $rand_id_en . '), (\'' . date('Y-m-d', $time_second_repetition) . '\', ' . $rand_id_en . ')';
                                $db_handle->query($insert_query);
                                if($db_handle->errno)
                                {
                                    failure_msg($db_handle, 503, "Unable to enter repetitions of the vocabulary into the database.", "DB");
                                }
                            } else {
                                failure_msg($db_handle, 503, "Could not reschedule vocabulary repetitions, the query for planned schedules failed", "DB");
                            }
                        } else {
                            failure_msg($db_handle, 503, "Unable to fetch newly chosen vocabulary.", "DB");
                        }
                    } else {
                        failure_msg($db_handle, 503, "Couldn't enter newly chosen random vocabulary back into database.", "DB");
                    }
                } else {
                    failure_msg($db_handle, 503, "Unable to find a new vocabulary of the day.", "DB");
                }
            }
        } else {
            failure_msg ($db_handle, 503, "Could not query the database about the vocabulary of the day.", "DB");
        }
    }
    echo "</div>\n";
    $db_handle -> close();
} else {
    failure_msg ($db_handle, 503, "Database not reachable.", "DB");
}


?>
