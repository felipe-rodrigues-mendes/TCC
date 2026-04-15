<?php
require 'controllers/DistributionController.php';
$ctrl = new DistributionController();
$ref = new ReflectionClass($ctrl);
$m = $ref->getMethod('getAllowedStockPoints');
$m->setAccessible(true);
print_r($m->invoke($ctrl));
?>
