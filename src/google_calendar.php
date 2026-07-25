<?php
// Google Calendar API integration helpers (plain curl, no SDK).

function google_get_valid_access_token(int $userId): ?string
{
    $stmt = db()->prepare(
        'SELECT google_access_token, google_refresh_token, google_token_expires_at FROM users WHERE id = :id'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['google_access_token'])) {
        return null;
    }

    $expiresAt = $user['google_token_expires_at'] ? strtotime($user['google_token_expires_at']) : 0;
    // If access token is still valid for at least 60 seconds, return it
    if ($expiresAt > (time() + 60)) {
        return $user['google_access_token'];
    }

    // Access token is expired or missing. Try refreshing if we have a refresh token
    if (empty($user['google_refresh_token'])) {
        return null;
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => $user['google_refresh_token'],
            'grant_type'    => 'refresh_token',
        ]),
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        return null;
    }

    $newAccessToken = $data['access_token'];
    $expiresIn = (int) ($data['expires_in'] ?? 3600);
    $newExpiresAt = date('Y-m-d H:i:sP', time() + $expiresIn);

    $updateStmt = db()->prepare(
        'UPDATE users SET google_access_token = :access_token, google_token_expires_at = :expires_at WHERE id = :id'
    );
    $updateStmt->execute([
        'access_token' => $newAccessToken,
        'expires_at'   => $newExpiresAt,
        'id'           => $userId,
    ]);

    return $newAccessToken;
}

function google_calendar_add_event(int $userId, array $eventData): array
{
    $token = google_get_valid_access_token($userId);
    if ($token === null) {
        return [
            'ok' => false,
            'error' => 'Google Calendar access is not authorized or token expired.',
            'auth_needed' => true,
        ];
    }

    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($eventData),
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || ($status !== 200 && $status !== 201)) {
        $data = json_decode((string) $response, true);
        $errMsg = $data['error']['message'] ?? 'Failed to create Google Calendar event.';
        $isAuthError = ($status === 401 || (isset($data['error']['code']) && $data['error']['code'] === 401));
        return [
            'ok' => false,
            'error' => $errMsg,
            'auth_needed' => $isAuthError,
        ];
    }

    $data = json_decode($response, true);
    return [
        'ok'        => true,
        'event_id'  => $data['id'] ?? '',
        'html_link' => $data['htmlLink'] ?? '',
    ];
}

function google_calendar_delete_event(int $userId, string $eventId): array
{
    $token = google_get_valid_access_token($userId);
    if ($token === null) {
        return ['ok' => false, 'error' => 'Google Calendar access is not authorized or token expired.'];
    }

    $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode($eventId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 204 || $status === 200) {
        return ['ok' => true];
    }

    $data = json_decode((string) $response, true);
    $errMsg = $data['error']['message'] ?? 'Failed to delete Google Calendar event.';
    return ['ok' => false, 'error' => $errMsg];
}
