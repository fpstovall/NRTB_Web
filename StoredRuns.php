<html>
<head>
<title>NRTB: Existing DataSets</title>
<link rel="stylesheet" type="text/css" href="nrtb.css">
</head>
<body>
<a href=index.php><h1>NRTB on <?php print($_SERVER["SERVER_NAME"]) ?></h1></a>
<?php
require 'vendor/autoload.php';
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
      print("<td><button class=good type=button onclick=\"summary(".$rec["sim_id"].")\">Summary</button></td>");
    } else {
      print("<td></td>");
    }
    print("<td><button class=$delclass type=button onclick=\"del(".$rec["sim_id"].")\">Delete</button></td>");
    print("</tr>\n");
  };
?>
</table>

<script>
function summary(id){
  window.location.href = "sim_analysis.php?id="+id;
};
function del(id){
  if (confirm("Do you want to delete "+id+"?")) {
    window.location.href = "sim_delete.php?id="+id;
  }
};
</script>

</body>
</html>