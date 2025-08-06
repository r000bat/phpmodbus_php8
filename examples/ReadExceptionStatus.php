<?php
require_once __DIR__ . '/../Phpmodbus/ModbusMasterTcp.php';

$ip = '192.168.1.1';
$modbus = new ModbusMasterTcp($ip);

try {
    $status = $modbus->readExceptionStatus(0);
    echo "Exception status bits:\n";
    foreach ($status as $index => $bit) {
        echo 'Bit ' . $index . ': ' . ($bit ? '1' : '0') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo $modbus;
}

