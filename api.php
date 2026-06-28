<?php
// Set RECOMP_TOKEN env var on server, or change this fallback
define('API_TOKEN', getenv('RECOMP_TOKEN') ?: 'dacaae2a9d2cda5fa9247b490575c2f0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0700, true);
    }

    $db = new PDO('sqlite:' . $dataDir . '/recomp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS config (key TEXT PRIMARY KEY, value TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS entries (date TEXT PRIMARY KEY, weight REAL)');
    $db->exec('CREATE TABLE IF NOT EXISTS measurements (date TEXT PRIMARY KEY, waist REAL)');

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'load') {
            $stmt = $db->query('SELECT key, value FROM config');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $config = null;
            if (count($rows) > 0) {
                $config = [];
                foreach ($rows as $row) {
                    $config[$row['key']] = $row['value'];
                }
            }

            $stmt = $db->query('SELECT date, weight FROM entries ORDER BY date ASC');
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($entries as &$e) {
                $e['weight'] = (float)$e['weight'];
            }

            $stmt = $db->query('SELECT date, waist FROM measurements ORDER BY date ASC');
            $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($measurements as &$m) {
                $m['waist'] = (float)$m['waist'];
            }

            echo json_encode(['ok' => true, 'config' => $config, 'entries' => $entries, 'measurements' => $measurements]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $action = $body['action'] ?? '';

        if ($action === 'saveConfig') {
            $db->beginTransaction();
            $db->exec('DELETE FROM config');
            $stmt = $db->prepare('INSERT INTO config (key, value) VALUES (?, ?)');
            foreach ($body['config'] as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }
            $db->commit();
            echo json_encode(['ok' => true]);

        } elseif ($action === 'logWeight') {
            $stmt = $db->prepare('INSERT OR REPLACE INTO entries (date, weight) VALUES (?, ?)');
            $stmt->execute([$body['date'], (float)$body['weight']]);
            echo json_encode(['ok' => true]);

        } elseif ($action === 'deleteEntry') {
            $stmt = $db->prepare('DELETE FROM entries WHERE date = ?');
            $stmt->execute([$body['date']]);
            echo json_encode(['ok' => true]);

        } elseif ($action === 'logWaist') {
            $stmt = $db->prepare('INSERT OR REPLACE INTO measurements (date, waist) VALUES (?, ?)');
            $stmt->execute([$body['date'], (float)$body['waist']]);
            echo json_encode(['ok' => true]);

        } elseif ($action === 'deleteWaist') {
            $stmt = $db->prepare('DELETE FROM measurements WHERE date = ?');
            $stmt->execute([$body['date']]);
            echo json_encode(['ok' => true]);

        } elseif ($action === 'saveAll') {
            $db->beginTransaction();
            $db->exec('DELETE FROM config');
            $db->exec('DELETE FROM entries');
            if (!empty($body['config'])) {
                $stmt = $db->prepare('INSERT INTO config (key, value) VALUES (?, ?)');
                foreach ($body['config'] as $k => $v) {
                    $stmt->execute([$k, (string)$v]);
                }
            }
            if (!empty($body['entries'])) {
                $stmt = $db->prepare('INSERT INTO entries (date, weight) VALUES (?, ?)');
                foreach ($body['entries'] as $e) {
                    $stmt->execute([$e['date'], (float)$e['weight']]);
                }
            }
            $db->exec('DELETE FROM measurements');
            if (!empty($body['measurements'])) {
                $stmt = $db->prepare('INSERT INTO measurements (date, waist) VALUES (?, ?)');
                foreach ($body['measurements'] as $m) {
                    $stmt->execute([$m['date'], (float)$m['waist']]);
                }
            }
            $db->commit();
            echo json_encode(['ok' => true]);

        } else {
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
