<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
$dataFile = '/var/www/html/data/guestbook.json';
$maxMessageLength = 200;
$maxNameLength = 20;
$rateLimit = 60; // seconds between posts from same IP

// Ensure data directory exists
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Initialize data file if it doesn't exist
if (!file_exists($dataFile)) {
    $initialData = [
        'messages' => [
            [
                'name' => 'VirtualPirate',
                'message' => 'Welcome to my cyber sanctuary. Leave your mark in the digital void...',
                'timestamp' => '2025-01-01T00:00:00Z',
                'id' => 'init_001',
                'ip' => '127.0.0.1'
            ],
            [
                'name' => 'CyberExplorer',
                'message' => 'Amazing animation! The nodes feel alive. This takes me back to the early days of the web.',
                'timestamp' => '2025-01-02T14:30:00Z',
                'id' => 'init_002',
                'ip' => '127.0.0.1'
            ],
            [
                'name' => 'RetroHacker',
                'message' => 'Love the Audiowide font choice and the neon aesthetic. Very cyberpunk vibes! 🔥',
                'timestamp' => '2025-01-03T09:15:00Z',
                'id' => 'init_003',
                'ip' => '127.0.0.1'
            ],
            [
                'name' => 'DigitalNomad',
                'message' => 'The spatial partitioning in your animation is brilliant. Smooth performance even on my old laptop!',
                'timestamp' => '2025-01-04T16:45:00Z',
                'id' => 'init_004',
                'ip' => '127.0.0.1'
            ]
        ]
    ];
    file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Helper functions
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = trim($_SERVER[$key]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function sanitizeInput($input, $maxLength) {
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return substr($input, 0, $maxLength);
}

function loadMessages() {
    global $dataFile;
    $data = json_decode(file_get_contents($dataFile), true);
    return $data['messages'] ?? [];
}

function saveMessages($messages) {
    global $dataFile;
    $data = ['messages' => $messages];

    // Atomic write using temporary file
    $tempFile = $dataFile . '.tmp';
    $result = file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    if ($result !== false) {
        return rename($tempFile, $dataFile);
    }
    return false;
}

function checkRateLimit($ip) {
    global $rateLimit;
    $messages = loadMessages();

    foreach (array_reverse($messages) as $message) {
        if (isset($message['ip']) && $message['ip'] === $ip) {
            $lastPost = strtotime($message['timestamp']);
            $timeDiff = time() - $lastPost;

            if ($timeDiff < $rateLimit) {
                return $rateLimit - $timeDiff;
            }
            break;
        }
    }
    return 0;
}

// Handle requests
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return messages (without IP addresses for privacy)
        $messages = loadMessages();
        $publicMessages = array_map(function($msg) {
            unset($msg['ip']);
            return $msg;
        }, $messages);

        echo json_encode(['success' => true, 'messages' => $publicMessages]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add new message
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $name = sanitizeInput($input['name'] ?? '', $maxNameLength);
        $message = sanitizeInput($input['message'] ?? '', $maxMessageLength);

        if (empty($name) || empty($message)) {
            throw new Exception('Name and message are required');
        }

        $clientIP = getClientIP();
        $remainingTime = checkRateLimit($clientIP);

        if ($remainingTime > 0) {
            throw new Exception("Rate limit exceeded. Please wait {$remainingTime} seconds.");
        }

        $newMessage = [
            'name' => $name,
            'message' => $message,
            'timestamp' => date('c'),
            'id' => 'msg_' . time() . '_' . bin2hex(random_bytes(4)),
            'ip' => $clientIP
        ];

        $messages = loadMessages();
        $messages[] = $newMessage;

        // Keep only last 100 messages to prevent unlimited growth
        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }

        if (saveMessages($messages)) {
            // Return success without IP
            unset($newMessage['ip']);
            echo json_encode(['success' => true, 'message' => $newMessage]);
        } else {
            throw new Exception('Failed to save message');
        }

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>