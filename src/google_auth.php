<?php
// Server-side Google OAuth 2.0 (authorization code flow), plain curl, no SDK.

function google_auth_url(string $state): string
{
    $params = [
        'response_type' => 'code',
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'scope' => 'openid email profile https://www.googleapis.com/auth/calendar.app.created',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $state,
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_exchange_code(string $code): ?array
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
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

    return [
        'access_token'  => $data['access_token'],
        'refresh_token' => $data['refresh_token'] ?? null,
        'expires_in'    => (int) ($data['expires_in'] ?? 3600),
    ];
}

function google_fetch_profile(string $accessToken): ?array
{
    $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['sub']) || empty($data['email'])) {
        return null;
    }

    return [
        'sub' => $data['sub'],
        'email' => $data['email'],
        'email_verified' => filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'name' => $data['name'] ?? null,
    ];
}
