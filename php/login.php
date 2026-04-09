<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
session_start();

/** @var \classes\Database $db */
$db = $_SESSION['DB'];

/* 
BEGIN

    SELECT
        id,
        full_name,
        password_hash,
        email_verified
    FROM users
    WHERE email = p_email
    LIMIT 1;

END
*/

if (isset($_POST['data'])) {
    $data = json_decode(base64_decode($_POST['data']), true);
    $email = $data['email'];
    $password = $data['password'];

    $result = $db->login($email, $password);
    if ($result['status'] === 'success') {
        $_SESSION['user'] = $result['user_id'];
    }
    echo json_encode($result);
}