<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$sim_id = $_GET['id']; 
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
    $t = intval(floor($quanta["ticks"] / 100));
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

// Write overall 
print('{ "overall":{');
print("\"count\":$accumulator->overall["rec_count"],");
print("\"total_calc\":$accumulator->overall["total_calc"],");
print("\"min_calc\":$accumulator->overall["min_calc"],");
print("\"max_calc\":$accumulator->overall["max_calc"]");
print("}\n");


foreach ($accumulator->by_pop as $pop => $data)
{
  print("\n,['".$pop."', ".$data->min_calc);
  print(",".$data->total_calc/$data->rec_count);
  //print(",".$data->max_calc."]");
  print("]");
}

foreach ($accumulator->by_times as $ti => $data)
{
  print("\n,['".(($ti+1)*100)."', ".$data->rec_count);
  //print(",".$data->total_calc/$data->rec_count);
  //print(",".$data->max_calc."]");
  print("]");
}
?>
