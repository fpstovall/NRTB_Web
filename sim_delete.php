<html>
<head>
<title>NRTB: Delete DataSet</title>
<link rel="stylesheet" type="text/css" href="nrtb.css">
</head>
<body>
<a href=index.php><h1>NRTB On <?php print($_SERVER["SERVER_NAME"]) ?></h1></a>
<?php
require 'vendor/autoload.php';
$connection = new MongoClient(); // connects to localhost:27017
$sims = $connection->nrtb->sim_setup;
$quantas = $connection->nrtb->quanta;
$sim_id = $_GET["id"]
?>
<h2>Deleted NRTB Dataset <?php print $sim_id; ?></h2>
<?php
  print "<p>Deleted ".$quantas->count(array('sim_id' => $sim_id))." Quanta Records";
  $quantas->remove(array('sim_id' => $sim_id));
  echo "<p>Deleted Sim Setup Record";
  $sims->remove(array('sim_id' => $sim_id));
?>
<p><a class=center href="StoredRuns.php">Task Complete! Click to return to listing</a>
</body>
</html>