<?php
require_once "bootstrap.php"; $shardManager->selectShard(0); $data = $conn->fetchAll('SELECT * FROM Customers'); print_r($data); 