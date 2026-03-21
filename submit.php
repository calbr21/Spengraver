<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$name     = trim(strip_tags($_POST['name']    ?? ''));
$email    = trim(strip_tags($_POST['email']   ?? ''));
$interest = trim(strip_tags($_POST['interest'] ?? ''));
$message  = trim(strip_tags($_POST['message'] ?? ''));

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

$interest_line = $interest ? "*Interest:* $interest\n" : '';

$payload = [
    'blocks' => [
        [
            'type' => 'header',
            'text' => [
                'type'  => 'plain_text',
                'text'  => '✉️ New Enquiry – Spengraver',
                'emoji' => true,
            ],
        ],
        [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '<@U0AMX5LUQRK> you have a new enquiry!',
            ],
        ],
        [
            'type'   => 'section',
            'fields' => [
                [
                    'type' => 'mrkdwn',
                    'text' => "*Name:*\n$name",
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => "*Email:*\n<mailto:$email|$email>",
                ],
            ],
        ],
        $interest ? [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*Interested in:*\n$interest",
            ],
        ] : null,
        [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*Message:*\n$message",
            ],
        ],
        [
            'type' => 'divider',
        ],
        [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => 'Sent from the Spengraver contact form',
                ],
            ],
        ],
    ],
];

// Remove null blocks (optional interest block when empty)
$payload['blocks'] = array_values(array_filter($payload['blocks']));

$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode($payload),
        'ignore_errors' => true,
    ],
]);

$response = file_get_contents($SLACK_WEBHOOK_URL, false, $context);

if ($response === 'ok') {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
