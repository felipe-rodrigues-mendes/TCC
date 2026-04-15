<?php
require 'controllers/DistributionController.php';
$dao = new PointOfCollectionDAO();
$ctrl = new DistributionController();
$ref = new ReflectionClass($ctrl);
$norm = $ref->getMethod('normalizeText');
$norm->setAccessible(true);
foreach ($dao->findAll() as $p) {
    echo $p['id'] . "|" . $p['nome'] . "|" . $norm->invoke($ctrl, (string)$p['nome']) . "|" . $p['logradouro'] . "|" . $norm->invoke($ctrl, (string)$p['logradouro']) . PHP_EOL;
}
?>
