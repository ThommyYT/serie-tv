<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
session_start();

/** @var \classes\Database $db */
$db = $_SESSION['DB'];

/* procedure sql register: 
INSERT INTO users (
            full_name,
            email,
            password_hash,
            email_verified
        )
        VALUES (
            p_name,
            p_email,
            p_password_hash,
            0
        );
*/

/* ts register:
$('#registerForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            const name = ($('#regName')?.val() as String).trim();
            const email = ($('#regEmail')?.val() as String).trim();
            const pass = $('#regPassword').val() as String;
            const confirm = $('#regConfirmPassword').val() as String;

            if (pass !== confirm) {
                alert("Le password non corrispondono");
                return;
            }

            var postData = btoa(JSON.stringify({
                name: name,
                email: email,
                password: pass
            }));
            
            $.post(
                "./php/register.php",
                { data: postData },
                function(res) {
                    // res deve essere JSON { success: true/false, ... }
                    if (res.success) {
                        localStorage.setItem(
                            "user",
                            btoa(JSON.stringify({
                                id: res.user.id,
                                name: res.user.name,
                                expires: Date.now() + 3600 * 1000
                            }))
                        );

                        inst.hide();
                        updateUserUI();
                    } else {
                        alert(res.error || "Errore registrazione");
                    }
                },
                "json"
            ).fail(function() {
                alert("Errore server alla registrazione");
            });

        });

*/

if (isset($_POST['data'])) {
    $data = json_decode(base64_decode($_POST['data']), true);
    $full_name = $data['name'];
    $email = $data['email'];
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

    $result = $db->register($full_name, $email, $password_hash);
    echo json_encode($result);
}
