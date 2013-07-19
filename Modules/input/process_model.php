<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Process
{
    private $mysqli;
    private $input;
    private $feed;

    public function __construct($mysqli,$input,$feed)
    {
        $this->mysqli = $mysqli;
        $this->input = $input;
        $this->feed = $feed;
    }

    public function get_process_list()
    {
      $list = array();

      // Process description
      // Arg type
      // public function Name
      // DataType (if applicable)

      $list[1] = array("Log to feed",ProcessArg::FEEDID,"log_to_feed",DataType::TIMESTORE);
      $list[2] = array("Histogram",ProcessArg::FEEDID,"histogram",DataType::HISTOGRAM);

      $list[3] = array("x",ProcessArg::VALUE,"scale");
      $list[4] = array("+",ProcessArg::VALUE,"offset");

      $list[5] = array("Allow positive",ProcessArg::NONE,"allowpositive");
      $list[6] = array("Allow negative",ProcessArg::NONE,"allownegative");

      $list[7] = array("x input",ProcessArg::INPUTID,"times_input");
      $list[8] = array("+ input",ProcessArg::INPUTID,"add_input");
      $list[9] = array("/ input",ProcessArg::INPUTID,"divide_input");
      $list[10] = array("- input",ProcessArg::INPUTID,"subtract_input");

      return $list;
    }

    public function input($time, $value, $processList)
    {
        $process_list = $this->get_process_list();
        $pairs = explode(",",$processList);
        foreach ($pairs as $pair)    			        
        {
          $inputprocess = explode(":", $pair); 				                // Divide into process id and arg
          $processid = (int) $inputprocess[0];						            // Process id

          $arg = 0;
          if (isset($inputprocess[1])) $arg = $inputprocess[1];	 			// Can be value or feed id

          $process_public = $process_list[$processid][2];	            // get process public function name
          $value = $this->$process_public($arg,$time,$value);		      // execute process public function
        }
    }

    public function get_process($id)
    {
      $list = $this->get_process_list();
      
      if ($id>0 && $id<count($list)+1) return $list[$id];
    }

    public function scale($arg, $time, $value) 
    { 
      return $value * $arg; 
    }

    public function offset($arg, $time, $value)
    {
      return $value + $arg;
    }

    public function allowpositive($arg, $time, $value)
    {
      if ($value<0) $value = 0;
      return $value;
    }

    public function allownegative($arg, $time, $value)
    {
      if ($value>0) $value = 0;
      return $value;
    }

    public function log_to_feed($id, $time, $value)
    {
      $this->feed->insert_data($id, $time, $time, $value);
      return $value;
    }

    //---------------------------------------------------------------------------------------
    // Times value by current value of another input
    //---------------------------------------------------------------------------------------
    public function times_input($id, $time, $value)
    {
      $result = $this->mysqli->query("SELECT value FROM input WHERE id = '$id'");
      $row = $result->fetch_array();
      $value = $value * $row['value'];
      return $value;
    }

    public function divide_input($id, $time, $value)
    {
      $result = $this->mysqli->query("SELECT value FROM input WHERE id = '$id'");
      $row = $result->fetch_array();
     
      if($row['value'] > 0){
          return $value / $row['value'];
      }else{
          return null; // should this be null for a divide by zero?
      }
    }

    public function add_input($id, $time, $value)
    {
      $result = $this->mysqli->query("SELECT value FROM input WHERE id = '$id'");
      $row = $result->fetch_array();
      $value = $value + $row['value'];
      return $value;
    }

    public function subtract_input($id, $time, $value)
    {
      $result = $this->mysqli->query("SELECT value FROM input WHERE id = '$id'");
      $row = $result->fetch_array();
      $value = $value - $row['value'];
      return $value;
    }

    public function add_feed($id, $time, $value)
    {
      $result = $this->mysqli->query("SELECT value FROM feeds WHERE id = '$id'");
      $row = $result->fetch_array();
      $value = $value + $row['value'];
      return $value;
    }

    //---------------------------------------------------------------------------------
    // This method converts power to energy vs power (Histogram)
    //---------------------------------------------------------------------------------
    public function histogram($feedid, $time_now, $value)
    {
      $pot = 25;

      $feedname = "feed_" . trim($feedid) . "";
      $new_kwh = 0;

      $new_value = round($value / $pot, 0, PHP_ROUND_HALF_UP) * $pot;

      $time = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now));

      // Get the last time
      $result = $this->mysqli->query("SELECT * FROM feeds WHERE id = '$feedid'");
      $last_row = $result->fetch_array();

      if ($last_row)
      {
        $last_time = strtotime($last_row['time']);
        if (!$last_time)
          $last_time = $time_now;
        // kWh calculation
        $time_elapsed = ($time_now - $last_time);
        $kwh_inc = ($time_elapsed * $value) / 3600000;
      }

      // Get last value
      $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time = '$time' AND data2 = '$new_value'");

      if (!$result) return $value;

      $last_row = $result->fetch_array();

      if (!$last_row)
      {
        $result = $this->mysqli->query("INSERT INTO $feedname (time,data,data2) VALUES ('$time','0.0','$new_value')");

        $updatetime = date("Y-n-j H:i:s", $time_now);
        $this->mysqli->query("UPDATE feeds SET value = $new_value, time = '$updatetime' WHERE id='$feedid'");
        $new_kwh = $kwh_inc;
      }
      else
      {
        $last_kwh = $last_row['data'];
        $new_kwh = $last_kwh + $kwh_inc;
      }

      // update kwhd feed
      $this->mysqli->query("UPDATE $feedname SET data = '$new_kwh' WHERE time = '$time' AND data2 = '$new_value'");

      $updatetime = date("Y-n-j H:i:s", $time_now);
      $this->mysqli->query("UPDATE feeds SET value = '$new_value', time = '$updatetime' WHERE id='$feedid'");

      return $value;
    }

}
  
?>
