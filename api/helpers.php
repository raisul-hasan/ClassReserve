<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function json_response($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_json_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
    ];
}

function require_login()
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Please log in first.'], 401);
    }

    return $user;
}

function require_role($roles)
{
    $user = require_login();
    if (!in_array($user['role'], (array) $roles, true)) {
        json_response(['error' => 'You do not have permission to do this.'], 403);
    }

    return $user;
}

function clean_string($value)
{
    return trim((string) ($value ?? ''));
}

function valid_datetime($value)
{
    if (!$value) {
        return false;
    }

    $timestamp = strtotime($value);
    return $timestamp !== false;
}
