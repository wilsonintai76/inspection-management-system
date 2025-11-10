<?php
header('Content-Type: application/json');
echo json_encode(['test' => 'cross-audit-test-file', 'time' => date('Y-m-d H:i:s')]);
