<?php
$files = glob('specs/*.spec.php');
foreach($files as $spec){
  echo "\n {$spec} \n";
  require $spec;
}
\pecs\run();

?>
