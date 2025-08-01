<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class ApiController
{
    /* =========================
       Helpers génériques
    ========================== */

    private function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // base64url helpers (RFC 7515)
    private function b64url(string $bin): string {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
    private function b64url_decode(string $s) {
        $s = strtr($s, '-_', '+/');
        return base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    // Secret JWT partagé (login + vérif)
    private function jwtSecret(): string {
        static $secret = null;
        if ($secret !== null) return $secret;

        // 1) variables d'environnement (recommandé)
        if (!empty($_ENV['JWT_SECRET'])) { $secret = (string)$_ENV['JWT_SECRET']; return $secret; }
        if (!empty($_SERVER['JWT_SECRET'])) { $secret = (string)$_SERVER['JWT_SECRET']; return $secret; }

        // 2) depuis config/config.php si défini
        $cfgFile = __DIR__ . '/../../config/config.php';
        if (is_file($cfgFile)) {
            $cfg = require $cfgFile; // attend un array
            if (!empty($cfg['app']['jwt_secret'])) {
                $secret = (string)$cfg['app']['jwt_secret'];
                return $secret;
            }
        }

        // 3) fallback dev (à changer en prod)
        $secret = 'change-me-super-secret';
        return $secret;
    }

    private function jwtEncode(array $payload): string {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h = $this->b64url(json_encode($header, JSON_UNESCAPED_UNICODE));
        $p = $this->b64url(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = hash_hmac('sha256', "$h.$p", $this->jwtSecret(), true);
        $s = $this->b64url($sig);
        return "$h.$p.$s";
    }

    private function jwtDecode(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        [$h64, $p64, $s64] = $parts;

        $expected = $this->b64url(hash_hmac('sha256', "$h64.$p64", $this->jwtSecret(), true));
        if (!hash_equals($expected, $s64)) return null;

        $payloadJson = $this->b64url_decode($p64);
        if ($payloadJson === false) return null;
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return null;

        if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
        return $payload;
    }

    private function bearerToken(): ?string {
        // Divers environnements (Apache, FPM, CGI)
        $h = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['Authorization']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if (!$h && function_exists('apache_request_headers')) {
            $all = apache_request_headers();
            foreach ($all as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) { $h = $v; break; }
            }
        }
        if (!$h) return null;
        if (stripos($h, 'Bearer ') === 0) return trim(substr($h, 7));
        return null;
    }

    private function requireAuth(): array {
        $tok = $this->bearerToken();
        if (!$tok) $this->json(['error' => 'unauthorized'], 401);
        $p = $this->jwtDecode($tok);
        if (!$p) $this->json(['error' => 'unauthorized'], 401);
        return $p; // ['sub'=>id, 'email'=>..., 'is_admin'=>..., 'exp'=>...]
    }

    /* =========================
       AUTH
    ========================== */

    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_admin FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u || !password_verify($password, $u['password_hash'])) {
            $this->json(['error' => 'invalid_credentials'], 401);
        }

        $exp = time() + 3600; // 1h
        $token = $this->jwtEncode([
            'sub'       => (int)$u['id'],
            'email'     => $u['email'],
            'is_admin'  => (int)$u['is_admin'],
            'exp'       => $exp
        ]);

        $this->json([
            'token' => $token,
            'user'  => [
                'id'       => (int)$u['id'],
                'name'     => $u['name'],
                'email'    => $u['email'],
                'is_admin' => (int)$u['is_admin']
            ]
        ]);
    }

    public function me(): void {
        $p = $this->requireAuth();
        $this->json([
            'id'       => (int)$p['sub'],
            'email'    => (string)$p['email'],
            'is_admin' => (int)($p['is_admin'] ?? 0),
        ]);
    }

    /* =========================
       EVENTS — PUBLIC
    ========================== */

    public function listPublic(): void {
        $pdo = Database::pdo();
        $limit  = max(1, (int)($_GET['limit']  ?? 12));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        // Montre uniquement les confirmés publiquement
        $stmt = $pdo->prepare(
            'SELECT SQL_CALC_FOUND_ROWS id,name,description,event_date,price,latitude,longitude,contact_name,contact_email,photo_path,created_at,updated_at
             FROM events
             WHERE is_confirmed=1
             ORDER BY event_date DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        $next  = ($offset + $limit < $total) ? $offset + $limit : null;

        $this->json(['items' => $items, 'total' => $total, 'nextOffset' => $next]);
    }

    /* =========================
       EVENTS — PRIVÉ (auth)
    ========================== */

    public function list(): void {
        $p = $this->requireAuth();
        $pdo = Database::pdo();
        $limit  = max(1, (int)($_GET['limit']  ?? 12));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare(
            'SELECT SQL_CALC_FOUND_ROWS id,name,description,event_date,price,latitude,longitude,contact_name,contact_email,photo_path,created_at,updated_at
             FROM events
             ORDER BY event_date DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        $next  = ($offset + $limit < $total) ? $offset + $limit : null;

        $this->json(['items' => $items, 'total' => $total, 'nextOffset' => $next]);
    }

    public function detail(array $params): void {
        $p  = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->execute([$id]);
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ev) $this->json(['error' => 'not_found'], 404);

        $this->json($ev);
    }

    public function create(): void {
        $p = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO events(name,description,event_date,price,latitude,longitude,contact_name,contact_email,is_confirmed,created_at,updated_at)
             VALUES(?,?,?,?,?,?,?,?,0,NOW(),NOW())'
        );
        $stmt->execute([
            (string)($input['name'] ?? ''),
            (string)($input['description'] ?? ''),
            (string)($input['event_date'] ?? ''),
            (int)   ($input['price'] ?? 0),
            (string)($input['latitude'] ?? 0),
            (string)($input['longitude'] ?? 0),
            (string)($input['contact_name'] ?? ''),
            (string)($input['contact_email'] ?? '')
        ]);

        $this->json(['id' => (int)$pdo->lastInsertId()]);
    }

    public function update(array $params): void {
        $p  = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE events
             SET name=?, description=?, event_date=?, price=?, latitude=?, longitude=?, contact_name=?, contact_email=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([
            (string)($input['name'] ?? ''),
            (string)($input['description'] ?? ''),
            (string)($input['event_date'] ?? ''),
            (int)   ($input['price'] ?? 0),
            (string)($input['latitude'] ?? 0),
            (string)($input['longitude'] ?? 0),
            (string)($input['contact_name'] ?? ''),
            (string)($input['contact_email'] ?? ''),
            $id
        ]);

        $this->json(['ok' => true]);
    }

    public function delete(array $params): void {
        $p  = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$id]);

        $this->json(['ok' => true]);
    }

    /* =========================
       Uploads
    ========================== */

    public function uploadCover(array $params): void {
        $p  = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);

        if (!isset($_FILES['file'])) $this->json(['error' => 'no_file'], 400);

        $dir = __DIR__ . '/../../public/uploads';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $name = $_FILES['file']['name'] ?? 'cover.jpg';
        $ext  = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
        $fname = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest  = $dir . '/' . $fname;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $this->json(['error' => 'move_failed'], 500);
        }

        $rel = '/uploads/' . $fname;
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE events SET photo_path=?, updated_at=NOW() WHERE id=?')->execute([$rel, $id]);

        $this->json(['path' => $rel]);
    }

    public function uploadPhotos(array $params): void {
        $p  = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);

        if (empty($_FILES['files'])) $this->json(['error' => 'no_files'], 400);

        $dir = __DIR__ . '/../../public/uploads';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $out = [];
        $tmpList = $_FILES['files']['tmp_name'] ?? [];
        $nameList = $_FILES['files']['name'] ?? [];

        // Normaliser si single file
        if (!is_array($tmpList)) { $tmpList = [$tmpList]; $nameList = [$nameList]; }

        foreach ($tmpList as $i => $tmp) {
            if (!$tmp) continue;
            $orig = $nameList[$i] ?? ('p'.$i.'.jpg');
            $ext  = pathinfo($orig, PATHINFO_EXTENSION) ?: 'jpg';
            $fname = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest  = $dir . '/' . $fname;
            if (move_uploaded_file($tmp, $dest)) {
                $out[] = '/uploads/' . $fname;
                // Ici, si vous avez une table events_photos, insérez $id + path
            }
        }

        $this->json(['paths' => $out]);
    }

    /* =========================
       Compte
    ========================== */

    public function updateProfile(): void {
        $p = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($input['name'] ?? '');

        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, (int)$p['sub']]);

        $this->json(['ok' => true]);
    }

    public function requestEmailChange(): void {
        $p = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $new = trim($input['new_email'] ?? '');

        if (!filter_var($new, FILTER_VALIDATE_EMAIL)) $this->json(['error' => 'invalid_email'], 400);

        $code = (string)random_int(100000, 999999);
        $pdo  = Database::pdo();
        $pdo->prepare('UPDATE users SET email_new=?, email_change_code=? WHERE id=?')
            ->execute([$new, $code, (int)$p['sub']]);

        // mail($new, 'Code de confirmation', "Votre code: $code");
        $this->json(['ok' => true]);
    }

    public function confirmEmailChange(): void {
        $p = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $code = (string)($input['code'] ?? '');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT email_new, email_change_code FROM users WHERE id=?');
        $stmt->execute([(int)$p['sub']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u || !$u['email_new'] || (string)$u['email_change_code'] !== $code) {
            $this->json(['error' => 'invalid_code'], 400);
        }

        $pdo->prepare('UPDATE users SET email=?, email_new=NULL, email_change_code=NULL WHERE id=?')
            ->execute([$u['email_new'], (int)$p['sub']]);

        $this->json(['ok' => true]);
    }

    public function changePassword(): void {
        $p = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $current = (string)($input['current_password'] ?? '');
        $new     = (string)($input['new_password'] ?? '');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
        $stmt->execute([(int)$p['sub']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u || !password_verify($current, $u['password_hash'])) {
            $this->json(['error' => 'bad_password'], 400);
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, (int)$p['sub']]);

        $this->json(['ok' => true]);
    }
}
