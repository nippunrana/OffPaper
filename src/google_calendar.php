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

// Raw JSON request against the Calendar API. Returns [status, decoded-body].
function google_calendar_api(string $token, string $method, string $url, ?array $body = null): array
{
    $ch = curl_init($url);
    $headers = ['Authorization: Bearer ' . $token];
    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $response === false ? null : json_decode((string) $response, true)];
}

/**
 * Calendar the app writes events to. With the calendar.app.created scope the
 * app cannot touch the user's primary calendar, so it creates (once) and
 * reuses its own "EarlySnap" calendar; the id is cached in users.google_calendar_id.
 * Returns null if the calendar cannot be created (e.g. token only carries the
 * legacy calendar.events scope) — callers then fall back to 'primary'.
 */
function google_get_app_calendar_id(int $userId, string $token): ?string
{
    $stmt = db()->prepare('SELECT google_calendar_id FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $stored = $stmt->fetchColumn();
    if (!empty($stored)) {
        return $stored;
    }

    [$status, $data] = google_calendar_api($token, 'POST', 'https://www.googleapis.com/calendar/v3/calendars', [
        'summary'     => 'EarlySnap',
        'description' => 'Deadlines and reminders created by EarlySnap (https://earlysnap.com)',
    ]);
    if (($status !== 200 && $status !== 201) || empty($data['id'])) {
        return null;
    }

    $update = db()->prepare('UPDATE users SET google_calendar_id = :cal WHERE id = :id');
    $update->execute(['cal' => $data['id'], 'id' => $userId]);
    return $data['id'];
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

    // Prefer the app-created EarlySnap calendar; fall back to 'primary' for
    // users whose stored grant still carries the legacy calendar.events scope.
    $calendarId = google_get_app_calendar_id($userId, $token) ?? 'primary';

    [$status, $data] = google_calendar_api(
        $token,
        'POST',
        'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
        $eventData
    );

    if ($status !== 200 && $status !== 201) {
        $errMsg = $data['error']['message'] ?? 'Failed to create Google Calendar event.';
        $isAuthError = ($status === 401 || $status === 403);
        return [
            'ok' => false,
            'error' => $errMsg,
            'auth_needed' => $isAuthError,
        ];
    }

    return [
        'ok'          => true,
        'event_id'    => $data['id'] ?? '',
        'html_link'   => $data['htmlLink'] ?? '',
        'calendar_id' => $calendarId,
    ];
}

function google_calendar_delete_event(int $userId, string $eventId, string $calendarId = ''): array
{
    $token = google_get_valid_access_token($userId);
    if ($token === null) {
        return ['ok' => false, 'error' => 'Google Calendar access is not authorized or token expired.'];
    }

    // Events recorded before the calendar_id was stored live on 'primary'.
    if ($calendarId === '') {
        $calendarId = 'primary';
    }

    [$status, $data] = google_calendar_api(
        $token,
        'DELETE',
        'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId)
    );

    if ($status === 204 || $status === 200) {
        return ['ok' => true];
    }

    $errMsg = $data['error']['message'] ?? 'Failed to delete Google Calendar event.';
    return ['ok' => false, 'error' => $errMsg];
}
