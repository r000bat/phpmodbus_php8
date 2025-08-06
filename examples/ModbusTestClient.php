<?php
    /*
 * Modbus Web Test Client
 * --------------------------------------------------------------
 * Dieses PHP-Webtool dient zur Visualisierung und Diagnose von
 * Modbus-Geräten über TCP oder UDP. Es unterstützt das Lesen von
 * Coils, Discrete Inputs, Input Registers und Holding Registers.
 *
 * Entwickelt zur einfachen Demonstration und Testung mit der
 * Open-Source-Bibliothek "phpmodbus" (https://github.com/aldas/phpmodbus),
 * aber auch unabhängig einsetzbar für eigene Modbus-Anwendungen.
 *
 * Funktionen:
 * - Echtzeit-Abfrage über wählbares Zeitintervall
 * - Bitweise Darstellung als Tabelle (richtig herum: +0 bis +15)
 * - Kompaktes responsives UI mit Farbcodierung
 *
 * MIT License
 * Copyright (c) 2025 Robert Markner
 * GitHub: https://github.com/r000bat
 * Erstellt am: 06.08.2025
 */

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'start') {
        $host     = $_POST['ip'] ?? '127.0.0.1';
        $port     = (int) ($_POST['port'] ?? 502);
        $protocol = $_POST['protocol'] ?? 'TCP';
        $function = $_POST['function'] ?? 'readCoils';
        $start    = (int) ($_POST['start'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 16);

        if ($protocol === 'TCP') {
            require_once __DIR__ . '/../Phpmodbus/ModbusMasterTcp.php';
            $modbus = new ModbusMasterTcp($host);
        } else {
            require_once __DIR__ . '/../Phpmodbus/ModbusMasterUdp.php';
            $modbus = new ModbusMasterUdp($host);
        }

        $modbus->port = $port;

        try {
            switch ($function) {
                case 'readCoils':
                    $recData = $modbus->readCoils(0, $start, $quantity);
                    break;
                case 'readInputDiscretes':
                    $recData = $modbus->readInputDiscretes(0, $start, $quantity);
                    break;
                case 'readInputRegisters':
                    $recData = $modbus->readInputRegisters(0, $start, $quantity);
                    break;
                case 'readMultipleRegisters':
                    $recData = $modbus->readMultipleRegisters(0, $start, $quantity);
                    break;
                default:
                    throw new Exception("Unbekannte Funktion: $function");
            }

            echo '<table class="result">';
            echo '<tr><th>Adresse</th>';
            for ($i = $quantity - 1; $i >= 0; $i--) {
                echo "<th>+$i</th>";
            }
            echo '<th>Binär</th></tr>';

            echo '<tr>';
            echo '<td>' . (10001 + $start) . '</td>';

            $binary = '';
            for ($i = 0; $i < $quantity; $i++) {
                $val = isset($recData[$i]) && $recData[$i] ? 1 : 0;
                echo '<td class="' . ($val ? 'bit-1' : 'bit-0') . '">' . $val . '</td>';
                $binary .= $val;
            }

            echo '<td>' . $binary . '</td>';
            echo '</tr>';
            echo '</table>';

        } catch (Exception $e) {
            echo '<div class="error">Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        exit;
    }
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Modbus Web Test</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 2em;
        }
        h2 {
            color: #333;
            margin-bottom: 1em;
        }
        .wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 2em;
            align-items: flex-start;
        }
        form {
            flex: 0 0 360px;
            background: #fff;
            padding: 1.5em;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-top: 1em;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            padding: 0.5em;
            margin-top: 0.3em;
            font-size: 0.95em;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            margin-top: 1.5em;
            padding: 0.6em 1.2em;
            font-size: 1em;
            border: none;
            border-radius: 4px;
            background-color: #0078D7;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        #clearBtn {
            background: #999;
        }
        #loader {
            display: none;
            margin-top: 1em;
        }
        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(0,0,0,0.3);
            border-top-color: #0078D7;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        #output {
            flex: 1;
            min-width: 300px;
            background: #fff;
            padding: 1.5em;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table.result {
            border-collapse: collapse;
            margin-top: 0.5em;
            width: 100%;
        }
        table.result th, table.result td {
            border: 1px solid #ccc;
            padding: 0.4em;
            text-align: center;
        }
        table.result th {
            background-color: #f0f0f0;
        }
        .bit-1 {
            background-color: #d4edda;
            font-weight: bold;
            color: green;
        }
        .bit-0 {
            background-color: #f8d7da;
            color: #a00;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 1em;
        }
        @media (max-width: 800px) {
            .wrapper {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<h2>Modbus Test Client</h2>

<div class="wrapper">
    <form id="modbusForm">
        <label>IP-Adresse
            <input type="text" name="ip" value="127.0.0.1" required>
        </label>
        <label>Port
            <input type="number" name="port" value="502" required>
        </label>
        <label>Protokoll
            <select name="protocol">
                <option value="TCP">TCP</option>
                <option value="UDP">UDP</option>
            </select>
        </label>
        <label>Funktion
            <select name="function">
                <option value="readCoils">Read Coils (00001+)</option>
                <option value="readInputDiscretes" selected>Read Discrete Inputs (10001+)</option>
                <option value="readInputRegisters">Read Input Registers (30001+)</option>
                <option value="readMultipleRegisters">Read Holding Registers (40001+)</option>
            </select>
        </label>
        <label>Startadresse (Offset, z.B. 0 für 10001)
            <input type="number" name="start" value="0" required>
        </label>
        <label>Anzahl (Bits)
            <input type="number" name="quantity" value="16" required>
        </label>
        <label>Intervall (Sekunden)
            <input type="number" id="interval" value="30" min="5">
        </label>
        <button type="button" id="startBtn">Start</button>
        <button type="button" id="clearBtn">Stop</button>
        <div id="loader"><div class="spinner"></div></div>
    </form>

    <div id="output">
        <!-- Bit-Tabelle erscheint hier -->
    </div>
</div>

<script>
const form = document.getElementById('modbusForm');
const output = document.getElementById('output');
const intervalInput = document.getElementById('interval');
const loader = document.getElementById('loader');

let intervalId = null;

function fetchModbusData() {
    const data = new FormData(form);
    loader.style.display = 'inline-block';

    fetch('ModbusTestClient.php?action=start', {
        method: 'POST',
        body: data
    })
    .then(response => response.text())
    .then(text => {
        output.innerHTML = text;
        loader.style.display = 'none';
    })
    .catch(err => {
        output.innerHTML = '<div class="error">Fehler: ' + err + '</div>';
        loader.style.display = 'none';
    });
}

document.getElementById('startBtn').addEventListener('click', () => {
    if (intervalId) clearInterval(intervalId);
    fetchModbusData();
    const interval = parseInt(intervalInput.value, 10) * 1000;
    if (interval > 0) {
        intervalId = setInterval(fetchModbusData, interval);
    }
});

document.getElementById('clearBtn').addEventListener('click', () => {
    if (intervalId) clearInterval(intervalId);
    intervalId = null;
    output.innerHTML = '';
    loader.style.display = 'none';
});
</script>

</body>
</html>
