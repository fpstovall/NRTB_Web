<html>
<?php $sim_id = $_GET['id']; ?>
<head>
<title>NRTB: Analyize DataSet</title>
<link rel="stylesheet" type="text/css" href="nrtb.css">
<?php
/****************************************************
This section gathers the data from the database
****************************************************/
require 'vendor/autoload.php';
$connection = new MongoClient(); // connects to localhost:27017
$sims = $connection->nrtb->sim_setup;
$quantas = $connection->nrtb->quanta;

// accumulator class
class time_accumulator
{
  // varibles
  public $rec_count = 0;
  public $total_calc = 0;
  public $min_calc = 1e6;
  public $max_calc = 0;
  // methods
  public function accum($quanta)
  {
    $this->rec_count += 1;
    $t = $quanta["ticks"];
    $this->total_calc += $t;
    $this->min_calc = min($this->min_calc,$t);
    $this->max_calc = max($this->max_calc,$t);
  }
}

class gather_all
{
  public $overall;
  public $by_pop;
  public $by_times;
  // methods
  public function __construct() 
  {
    $this->overall = new time_accumulator();
    $this->by_pop = array();
    $this->by_times = array();
  }
  public function accum($quanta)
  {
    // get overall data;
    $this->overall->accum($quanta);
    // get keys
    $count = count($quanta["objects"]);
    $t = intval(floor($quanta["ticks"] / 50));
    // accum by population
    if (array_key_exists($count,$this->by_pop) == false)
    {
      $this->by_pop[$count] = new time_accumulator();
    }
    $this->by_pop[$count]->accum($quanta);
    // accume by grouped time.
    if (array_key_exists($t,$this->by_times) == false)
    {
      $this->by_times[$t] = new time_accumulator();
    }
    $this->by_times[$t]->accum($quanta);
  }
}

$accumulator = new gather_all();

// gather the data
$cursor = $quantas->find(array('sim_id' => $sim_id));
foreach ($cursor as $id => $rec)
{
  global $accumulator;
  $accumulator->accum($rec);
}
ksort($accumulator->by_pop);
ksort($accumulator->by_times);

// open the mysql connection. 
$nrtb = new mysqli("localhost","rstovall@localhost","","nrtb");

// populate the run_data record.
$cursor = $sims->find(array('sim_id' => $sim_id));
$query = "";
foreach ($cursor as $id => $rec)
{ 
  $query = "insert into run_data (run_id, quanta, timeslices, usec_min, usec_avg, usec_max) values ";
  $query += "'"+$rec["sim_id"]+"'";
  $query += ","+$rec["quanta"];
  $query += ","+$accumulator->overall->rec_count;
  $query += ","+$accumulator->overall->min_calc;
  $query += ","+($accumulator->overall->total_calc / $accumulator->overall->rec_count);
  $query += ","+$accumulator->overall->max_calc;
  $query += ")";
}
$nrtb->query($query);

// TODO: store conf options to conf_options

// TODO: Save all the population summary lines to pop_summary.

// TODO: Save all the time summary lines to usec_sumamry.

$nrtb->close();
?>

