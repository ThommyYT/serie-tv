<?php

namespace classes;

use \PDO;
use \PDOException;

class Database
{
    private $host = "localhost";
    private $db_name = "serietv_streaming";
    private $username = "devuser"; // Cambiato da root
    private $password = "devpass"; // Inserisci la password di devuser
    public $conn;

    // Impedisce la serializzazione della connessione
    public function __sleep()
    {
        return []; // Non serializzare nulla
    }

    public function __wakeup()
    {
        $this->getConnection(); // Riconnetti automaticamente se rianimato
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $exception) {
            // Log degli errori come da linee guida operative
            error_log(date('Y-m-d H:i:s') . " Errore di connessione devuser: " . $exception->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/errorDB.log');
            die("Errore di accesso al database.");
        }
        return $this->conn;
    }

    /**
     * Recupera un valore specifico dalla tabella settings
     */
    public function getSetting(string $key): ?string
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL settings_get(:key)");
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            return $row['setting_value'] ?? null;
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore get setting: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );

            return null;
        }
    }


    /**
     * Salva o aggiorna un setting
     */
    public function saveSetting(string $key, string $value): bool
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {

            $stmt = $this->conn->prepare("CALL settings_set(:key, :value)");

            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->bindValue(':value', $value, PDO::PARAM_STR);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            return $result['success'] ?? false;
        } catch (PDOException $e) {

            error_log(
                date('Y-m-d H:i:s') .
                    " Errore salvataggio setting: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );

            return false;
        }
    }

    // public function login($email, $password) {
    //     if (!$this->conn) {
    //         $this->getConnection();
    //     }

    //     try {
    //         $stmt = $this->conn->prepare("CALL user_login(:email)");
    //         $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    //         $stmt->execute();

    //         $result = $stmt->fetch(PDO::FETCH_ASSOC);

    //         $stmt->closeCursor();

    //         if (password_verify($password, $result['password_hash'])) {
    //             $id = $result['id'];
    //             $name = $result['full_name'];
    //             $email = $result['email'];
    //             return 
    //         }

    //         return false;
    //     } catch (PDOException $e) {

    //         error_log(
    //             date('Y-m-d H:i:s') .
    //                 " Errore login: " .
    //                 $e->getMessage() .
    //                 PHP_EOL,
    //             3,
    //             __DIR__ . '/../logs/errorDB.log'
    //         );

    //         return false;
    //     }
    // }
    /** @return array{status: string, user_id?: int, message?: string} */
    public function register($full_name, $email, $password_hash)
    {
        if (!$this->conn) {
            $this->getConnection();
        }


        /* DB procedure:
            
            BEGIN

            IF EXISTS (
                SELECT 1 FROM users WHERE email = p_email
            ) THEN

                SELECT FALSE AS success, 'EMAIL_EXISTS' AS error;

            ELSE

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

                SELECT TRUE AS success, LAST_INSERT_ID() AS user_id;

            END IF;

            END
        */

        try {
            $stmt = $this->conn->prepare("CALL user_register(:full_name, :email, :password_hash)");

            $stmt->bindValue(':full_name', $full_name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);

            $stmt->execute();

            /** @var array{success: bool, user_id?: int, error?: string} $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            if ($result['success']) {
                return [
                    'status' => 'success'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Unknown error'
                ];
            }
        } catch (PDOException $e) {

            error_log(
                date('Y-m-d H:i:s') .
                    " Errore registrazione: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /** @return array{status: string, user_id?: int, full_name?: string, message?: string, email_verified?: int} */
    public function login($email, $password)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL user_login(:email)");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            /** @var array{id: int, full_name: string, password_hash: string, email_verified: int} $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            // print_r($password);
            // print_r(PHP_EOL);
            // print_r($result);

            if (password_verify($password, $result['password_hash'])) {
                $id = $result['id'];
                $name = $result['full_name'];
                $verified = $result['email_verified'];

                $stmt = $this->conn->prepare("CALL update_last_login(:user_id)");
                $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();

                return [
                    'status' => 'success',
                    'user_id' => $id,
                    'email' => $email,
                    'full_name' => $name,
                    'email_verified' => $verified
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Password errata',
            ];
        } catch (PDOException $e) {

            error_log(
                date('Y-m-d H:i:s') .
                    " Errore login: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /** @return array{status: string, message?: string, user_id?: int, full_name?: string, email?: string, email_verified?: int, role?: string, last_login?: string, created_at?: string, updated_at?: string} */
    public function getUser($user_id)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL user_get(:user_id)");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user) {
                return [
                    'status' => 'not_found'
                ];
            }

            return [
                'status' => 'success',
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'email_verified' => $user['email_verified'],
                'role' => $user['role'],
                'last_login' => date('Y-m-d H:i:s', strtotime($user['last_login'])),
                'created_at' => date('Y-m-d H:i:s', strtotime($user['created_at'])),
                'updated_at' => date('Y-m-d H:i:s', strtotime($user['updated_at'])),
            ];
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore get user: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );

            return [
                'status' => 'error',
                'message' => 'Database error'
            ];
        }
    }

    public function setTokenUser($user_id, $token)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        /* 
        procedure:
        DELIMITER $$

        DROP PROCEDURE IF EXISTS create_verification_token $$
        CREATE PROCEDURE create_verification_token (
            IN p_user_id INT UNSIGNED,
            IN p_token_hash VARCHAR(255),
            IN p_expires_at DATETIME
        )
        BEGIN
            -- elimina eventuale token esistente (1 per utente)
            DELETE FROM verification_tokens WHERE user_id = p_user_id;

            INSERT INTO verification_tokens (
                user_id,
                token,
                expires_at
            )
            VALUES (
                p_user_id,
                p_token_hash,
                p_expires_at
            );

        END $$

        DELIMITER ;
        
        */

        try {
            // hash da salvare nel DB
            $tokenHash = hash('sha256', $token);

            // scadenza (es: 1 ora)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $this->conn->prepare("CALL create_verification_token(:uid, :token, :exp)");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':token', $tokenHash, PDO::PARAM_STR);
            $stmt->bindValue(':exp', $expiresAt, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            return true;
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore set token user: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }

    public function verifyUser($token)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        /* procedure:
        BEGIN
            DECLARE v_user_id INT;

            SELECT user_id
            INTO v_user_id
            FROM verification_tokens
            WHERE token = p_token_hash
            AND expires_at > NOW()
            LIMIT 1;

            IF v_user_id IS NOT NULL THEN

                UPDATE users
                SET email_verified = 1
                WHERE id = v_user_id;

                DELETE FROM verification_tokens
                WHERE user_id = v_user_id;

                SELECT TRUE AS success;

            ELSE
                SELECT FALSE AS success;
            END IF;

        END
        
        */

        try {
            // hash da salvare nel DB
            $tokenHash = hash('sha256', $token);

            $stmt = $this->conn->prepare("CALL verify_email_token(:token)");
            $stmt->bindValue(':token', $tokenHash, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result['success'];
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore verify user: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }

    public function checkFavorite($user_id, $post_id)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL check_favorite(:uid, :pid)");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':pid', $post_id, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            // var_dump($result);
            return (bool) $result['is_present'];
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore check favorite: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }

    public function getFavorites($user_id)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL get_favorites(:uid)");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($result)) {
                return [
                    'status' => 'empty'
                ];
            }

            return [
                'status' => 'success',
                'posts' => $result
            ];
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore get favorites: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }

    public function addFavorite($user_id, $post_id, $post_title)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL add_favorite(:uid, :pid, :ptitle)");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':pid', $post_id, PDO::PARAM_STR);
            $stmt->bindValue(':ptitle', $post_title, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result['success']) {
                return [
                    'status' => 'success'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Unknown error'
                ];
            }
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore add favorite: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }

    public function removeFavorite($user_id, $post_id)
    {
        if (!$this->conn) {
            $this->getConnection();
        }

        try {
            $stmt = $this->conn->prepare("CALL remove_favorite(:uid, :pid)");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':pid', $post_id, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result['success']) {
                return [
                    'status' => 'success'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Unknown error'
                ];
            }
        } catch (PDOException $e) {
            error_log(
                date('Y-m-d H:i:s') .
                    " Errore remove favorite: " .
                    $e->getMessage() .
                    PHP_EOL,
                3,
                __DIR__ . '/../logs/errorDB.log'
            );
            return false;
        }
    }
}
