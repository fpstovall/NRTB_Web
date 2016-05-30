<html>
<head>
<title>NRTB: Existing DataSets</title>
<link rel="stylesheet" type="text/css" href="nrtb.css">
</head>
<body>
<a href=index.php><h1>NRTB on <?php print($_SERVER["SERVER_NAME"]) ?></h1></a>
<?php
$connection = new MongoClient(); // connects to localhost:27017
$sims = $connection->nrtb->sim_setup;
$quantas = $connection->nrtb->quanta;
?>
<h2>NRTB Dataset Listing (<?php print($sims->count()) ?> found)</h2>
<table>
<tr><th>SimID</th><th>Time Slice</th><th>Records</th><th>Seconds</th><th colspan=2>Operations</th></tr>
<?php
  $cursor = $sims->find();
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
    if ($qs > 0) {
      print("<td class=good><a href=sim_analysis.php?id=".$rec["sim_id"].">[Summary]</a></td>");
    } else {
      print("<td></td>");
    }
    print("<td class=".$delclass."><a href=sim_delete.php?id=".$rec["sim_id"].">[Delete]</a></td>");
    print("</tr>\n");
  };
?>
</table>

</body>
</html>