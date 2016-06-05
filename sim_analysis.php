<html>
<?php $sim_id = $_GET['id']; ?>
<head>
<title>NRTB: Analyize DataSet</title>
<link rel="stylesheet" type="text/css" href="nrtb.css">
<?php
/****************************************************
This section gathers the data from the database
****************************************************/
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
?>
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Population', 'Min', 'Average']
<?php
  foreach ($accumulator->by_pop as $pop => $data)
  {
    print("\n,['".$pop."', ".$data->min_calc);
    print(",".$data->total_calc/$data->rec_count);
    //print(",".$data->max_calc."]");
    print("]");
  }
?>
]);

    var options = {
      title: 'Calculation uSec by Population',
      legend: { position: 'bottom' }
    };

    var chart = new google.visualization.LineChart(document.getElementById('pop_chart'));

    chart.draw(data, options);
  }
</script>

<script type="text/javascript">
  google.charts.setOnLoadCallback(drawCalcChart);

  function drawCalcChart() {
    var data = google.visualization.arrayToDataTable([
      ['uSec', 'Count (Prior index to Current)']
<?php
  foreach ($accumulator->by_times as $ti => $data)
  {
    print("\n,['".(($ti+1)*100)."', ".$data->rec_count);
    //print(",".$data->total_calc/$data->rec_count);
    //print(",".$data->max_calc."]");
    print("]");
  }
?>
]);

    var options = {
      title: 'Calculation uSec Distribution',
      legend: { position: 'bottom' }
    };

    var cchart = new google.visualization.LineChart(document.getElementById('calc_chart'));

    cchart.draw(data, options);
  }
</script>


</head>
<body>
<a href=StoredRuns.php><h1>NRTB on <?php print($_SERVER["SERVER_NAME"]) ?></h1></a>
<h2>Reporting NRTB Dataset <?php print($sim_id); ?></h2>
<table>
<tr><th>SimID</th><th>Time Slice</th><th>Records</th><th>Seconds</th><th>Operations</th></tr>
<?php
  $cursor = $sims->find(array('sim_id' => $sim_id));
  foreach ($cursor as $id => $rec)
  {
    $qs = $quantas->count(array('sim_id' => $rec["sim_id"]));
    $rowclass = "";
    $delclass = "warning";
    if ($qs == 0) {
      $rowclass = " class=empty";
      $delclass = "good";
    };
    print("<tr".$rowclass.">");
    print("<td>".$rec["sim_id"]."</td>");
    print("<td>".$rec["quanta"]."</td>");
    print("<td class=n>".number_format($qs,0,'',',')."</td>");
    print("<td class=n>".number_format(($rec["quanta"]*$qs), 2, '.', ',')."</td>");
    print("<td><button class=$delclass type=button onclick=\"del(".$rec["sim_id"].")\">Delete</button></td>");
    print("</tr>\n");
  };
?>
</table>
<p><h3>Overall Calculation Times Summary</h3>
<div id="pop_chart" style="width: 900px; height: 500px"></div>
<div id="calc_chart" style="width: 900px; height: 500px"></div>
<table>
<tr><td></td><th colspan=3>Microseconds</th></tr>
<tr><th>Quantas</th><th>Min</th><th>Average</th><th>Max</th></tr>
<?php
  print("<tr>");
  print("<td class=n>".$accumulator->overall->rec_count."</td>");
  print("<td class=n>".$accumulator->overall->min_calc."</td>");
  print("<td class=n>".number_format(($accumulator->overall->total_calc/$accumulator->overall->rec_count), 2, '.', ',')."</td>");
  print("<td class=n>".$accumulator->overall->max_calc."</td></tr>");
?>
</table>
<p><h3>Calculation Times by Object Count Detail</h3>
<table>
<tr><td></td><td></td><th colspan=3>Microseconds</th></tr>
<tr><th>Population</th><th>Count</th><th>Min</th><th>Average</th><th>Max</th></tr>
<?php
foreach ($accumulator->by_pop as $key => $row)
{
  print("<tr><td>".$key."</td>");
  print("<td class=n>".$row->rec_count."</td>");
  print("<td class=n>".$row->min_calc."</td>");
  print("<td class=n>".number_format(($row->total_calc/$row->rec_count), 2, '.', ',')."</td>");
  print("<td class=n>".$row->max_calc."</td></tr>");
}
?>
</table>
<p><h3>Calculation Time Breakdown Detail</h3>
<table>
<tr><td></td><td></td><th colspan=3>Microseconds</th></tr>
<tr><th>Microsecond Range</th><th>Count</th><th>Min</th><th>Average</th><th>Max</th></tr>
<?php
foreach ($accumulator->by_times as $key => $crow)
{
  print("<tr><td>".($key*100)." to ".((($key+1)*100)-1)."</td>");
  print("<td class=n>".$crow->rec_count."</td>");
  print("<td class=n>".$crow->min_calc."</td>");
  print("<td class=n>".number_format(($crow->total_calc/$crow->rec_count), 2, '.', ',')."</td>");
  print("<td class=n>".$crow->max_calc."</td></tr>");
}
?>
</table>

<script>
function del(id){
  if (confirm("Do you want to delete "+id+"?")) {
    window.location.href = "sim_delete.php?id="+id;
  }
};
</script>

</body>
</html>