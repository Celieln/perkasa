<?php

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(){
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(){
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token = null){
    if($token === null) $token = $_POST['csrf_token'] ?? '';
    if(empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']){
        die('CSRF token tidak valid');
    }
}

function redirect($url){
    header("Location: $url");
    exit;
}

function alert($message){

    echo "
    <script>
    alert('$message');
    </script>
    ";

}

function uploadFile($file, $folder){

    $namaFile = $file['name'];
    $tmp      = $file['tmp_name'];

    $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','pdf'];

    if(!in_array($ext, $allowed)){
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg','image/png','application/pdf'];
    if(!in_array($mime, $allowedMime)){
        return false;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $namaFile);
    $safeName = ltrim($safeName, '.');
    if($safeName === '') $safeName = 'file';
    $newName = time() . '_' . $safeName;

    move_uploaded_file($tmp, "../uploads/$folder/".$newName);

    return $newName;
}

function current_role(){
    return isset($_SESSION['role']) ? $_SESSION['role'] : '';
}

function is_superadmin(){
    return current_role() === 'superadmin';
}

function is_admin(){
    return current_role() === 'admin' || is_superadmin();
}

function deny_if_readonly($message = 'Anda tidak memiliki izin untuk melakukan aksi ini'){
    if(current_role() === 'users' || current_role() === 'petugas'){
        echo "<script>alert('".addslashes($message)."');window.history.back();</script>";
        exit;
    }
}

function generate_tracking_code($conn){
    $prefix = 'PRK';
    $exists = true;
    $code = '';

    while($exists){
        $segment1 = strtoupper(substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 4));
        $segment2 = strtoupper(substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 4));
        $code = $prefix . '-' . $segment1 . '-' . $segment2;

        $check = mysqli_query($conn, "SELECT id FROM peserta WHERE tracking_code='$code'");
        $exists = (mysqli_num_rows($check) > 0);
    }

    return $code;
}

function insert_tracking_log($conn, $peserta_id, $status, $keterangan = ''){
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';
    $status = mysqli_real_escape_string($conn, $status);
    $keterangan = mysqli_real_escape_string($conn, $keterangan);

    mysqli_query($conn, "INSERT INTO tracking_log (peserta_id, status, keterangan, created_by)
                          VALUES ('$peserta_id', '$status', '$keterangan', $user_id)");
}

// Status Rekomendasi
function rekom_list(){
    return [
        'belum'   => 'Belum Mengeluarkan Rekom',
        'proses'  => 'Memproses Rekom',
        'selesai' => 'Rekom Telah Selesai',
    ];
}

function rekom_label($status){
    $list = rekom_list();
    return isset($list[$status]) ? $list[$status] : '-';
}

function rekom_step($status){
    $idx = array_search($status, array_keys(rekom_list()));
    return $idx !== false ? $idx + 1 : 1;
}

function rekom_total_steps(){
    return count(rekom_list());
}

function rekom_badge($status){
    $colors = ['belum' => 'secondary', 'proses' => 'warning', 'selesai' => 'success'];
    $color = isset($colors[$status]) ? $colors[$status] : 'secondary';
    return "<span class=\"badge bg-{$color}\">" . rekom_label($status) . "</span>";
}

// Status Klaim
function klaim_list(){
    return [
        'belum'        => 'Belum',
        'proses_cair'  => 'Proses Cair',
        'proses_claim' => 'Proses Claim',
        'sudah_cair'   => 'Sudah Cair',
        'sudah_claim'  => 'Sudah Claim',
    ];
}

function klaim_label($status){
    $list = klaim_list();
    return isset($list[$status]) ? $list[$status] : '-';
}

function klaim_step($status){
    $idx = array_search($status, array_keys(klaim_list()));
    return $idx !== false ? $idx + 1 : 1;
}

function klaim_total_steps(){
    return count(klaim_list());
}

function klaim_badge($status){
    $colors = ['belum' => 'secondary', 'proses_cair' => 'warning', 'proses_claim' => 'info', 'sudah_cair' => 'success', 'sudah_claim' => 'success'];
    $color = isset($colors[$status]) ? $colors[$status] : 'secondary';
    return "<span class=\"badge bg-{$color}\">" . klaim_label($status) . "</span>";
}

function pagination_links($page, $total_pages, $params = []){
    if ($total_pages <= 1) return '';

    $queryStr = '';
    foreach ($params as $k => $v) {
        $queryStr .= '&' . urlencode($k) . '=' . urlencode($v);
    }

    $html = '<nav aria-label="Page navigation" class="mt-4"><ul class="pagination justify-content-center flex-wrap">';

    // Prev
    $prevDisabled = ($page <= 1) ? 'disabled' : '';
    $html .= "<li class=\"page-item $prevDisabled\"><a class=\"page-link\" href=\"?page=" . ($page-1) . "$queryStr\">Prev</a></li>";

    $range = 5;
    $start = max(1, $page - $range);
    $end   = min($total_pages, $page + $range);

    if ($start > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?page=1$queryStr\">1</a></li>";
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? 'active' : '';
        $html .= "<li class=\"page-item $active\"><a class=\"page-link\" href=\"?page=$i$queryStr\">$i</a></li>";
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?page=$total_pages$queryStr\">$total_pages</a></li>";
    }

    // Next
    $nextDisabled = ($page >= $total_pages) ? 'disabled' : '';
    $html .= "<li class=\"page-item $nextDisabled\"><a class=\"page-link\" href=\"?page=" . ($page+1) . "$queryStr\">Next</a></li>";

    $html .= '</ul></nav>';
    return $html;
}

// ============ CAPTCHA SHA-256 SIGNED TOKEN ============

function captcha_generate(){
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $nonce = bin2hex(random_bytes(16));
    $ts = time();

    // Payload (jawaban tidak di-expose ke HTML)
    $payload = json_encode(['a'=>$a,'b'=>$b,'ts'=>$ts,'n'=>$nonce], JSON_UNESCAPED_SLASHES);
    $payload_b64 = rtrim(base64_encode($payload), '=');

    // Sign dengan HMAC-SHA256
    $sig = hash_hmac('sha256', $payload_b64, CAPTCHA_SECRET);
    $token = $payload_b64.'.'.$sig;

    // Simpan di session utk visual question + verifikasi
    $_SESSION['captcha_a'] = $a;
    $_SESSION['captcha_b'] = $b;
    $_SESSION['captcha_nonce'] = $nonce;
    $_SESSION['captcha_time'] = $ts;

    return $token;
}

function captcha_verify($token, $user_answer = null){
    if(empty($token)) return false;

    $parts = explode('.', $token, 2);
    if(count($parts) !== 2) return false;

    list($payload_b64, $sig) = $parts;

    // 1. Verifikasi signature HMAC-SHA256
    $expected_sig = hash_hmac('sha256', $payload_b64, CAPTCHA_SECRET);
    if(!hash_equals($expected_sig, $sig)) return false;

    // 2. Decode payload
    $padded = $payload_b64 . str_repeat('=', (4 - strlen($payload_b64) % 4) % 4);
    $json = base64_decode($padded);
    $data = json_decode($json, true);
    if(!$data || !isset($data['a'],$data['b'],$data['ts'],$data['n'])) return false;

    // 3. Cek timestamp (max 5 menit)
    if(time() - $data['ts'] > 300) return false;

    // 4. Cek nonce (anti replay) — harus sama dgn session
    if(!isset($_SESSION['captcha_nonce']) || $data['n'] !== $_SESSION['captcha_nonce']) return false;

    // 5. Cek jawaban user vs jawaban di token
    $correct_answer = (int)$data['a'] + (int)$data['b'];
    $ok = ((string)(int)$user_answer === (string)$correct_answer);

    // Bersihkan session
    unset($_SESSION['captcha_nonce'], $_SESSION['captcha_time'], $_SESSION['captcha_answer']);

    return $ok;
}

function captcha_html(){
    $token = captcha_generate();
    return '<input type="hidden" name="captcha_token" value="'.h($token).'">';
}

function captcha_question_html(){
    // Tampilkan soal saja (user harus ketik jawaban di field terpisah)
    $a = (int)($_SESSION['captcha_a'] ?? 0);
    $b = (int)($_SESSION['captcha_b'] ?? 0);
    if($a === 0 || $b === 0) return '';
    return '<span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:8px 14px;font-weight:700;font-size:16px;color:#93c5fd;letter-spacing:1px;">'.$a.' + '.$b.' = ?</span>';
}

function timing_check($min_seconds = 3){
    $loaded = $_SESSION['captcha_time'] ?? 0;
    return (time() - $loaded) >= $min_seconds;
}

function honeypot_check($value){
    return empty($value);
}

// ============ KEAMANAN LOGIN & REGISTER ============

function client_ip(){
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ensure_security_tables($conn){
    static $done = false;
    if($done) return true;

    $sql1 = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        attempts INT NOT NULL DEFAULT 0,
        last_attempt DATETIME NULL,
        locked_until DATETIME NULL,
        UNIQUE KEY uk_ip (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if(!mysqli_query($conn, $sql1)) return false;

    $sql2 = "CREATE TABLE IF NOT EXISTS register_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        count INT NOT NULL DEFAULT 0,
        window_start DATETIME NULL,
        UNIQUE KEY uk_ip (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if(!mysqli_query($conn, $sql2)) return false;

    $done = true;
    return true;
}

function login_lock_state($conn, $ip){
    ensure_security_tables($conn);
    $stmt = mysqli_prepare($conn, "SELECT attempts, locked_until FROM login_attempts WHERE ip = ? LIMIT 1");
    if(!$stmt) return ['locked'=>false,'remaining'=>0,'attempts'=>0];
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$row) return ['locked'=>false,'remaining'=>0,'attempts'=>0];
    $locked_until = $row['locked_until'] ? strtotime($row['locked_until']) : 0;
    $remaining = $locked_until - time();
    if($remaining > 0) return ['locked'=>true,'remaining'=>$remaining,'attempts'=>(int)$row['attempts']];
    return ['locked'=>false,'remaining'=>0,'attempts'=>(int)$row['attempts']];
}

function record_login_attempt($conn, $ip, $success){
    ensure_security_tables($conn);
    $now = date('Y-m-d H:i:s');

    if($success){
        $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE ip = ?");
        mysqli_stmt_bind_param($stmt, 's', $ip);
        mysqli_stmt_execute($stmt);
        return;
    }

    // reset counter jika masa lockout sudah lewat
    $stmt = mysqli_prepare($conn, "UPDATE login_attempts SET attempts = 0 WHERE ip = ? AND locked_until IS NOT NULL AND locked_until <= NOW()");
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (ip, attempts, last_attempt, locked_until)
        VALUES (?, 1, ?, NULL)
        ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = VALUES(last_attempt)");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $now);
    mysqli_stmt_execute($stmt);

    // lock 15 menit setelah 5x gagal
    $stmt = mysqli_prepare($conn, "UPDATE login_attempts SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE ip = ? AND attempts >= 5");
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
}

function register_state($conn, $ip){
    ensure_security_tables($conn);
    $stmt = mysqli_prepare($conn, "SELECT count, window_start FROM register_attempts WHERE ip = ? LIMIT 1");
    if(!$stmt) return ['count'=>0];
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$row) return ['count'=>0];
    if(time() - strtotime($row['window_start']) > 3600) return ['count'=>0];
    return ['count'=>(int)$row['count']];
}

function record_register_attempt($conn, $ip){
    ensure_security_tables($conn);
    $now = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn, "INSERT INTO register_attempts (ip, count, window_start)
        VALUES (?, 1, ?)
        ON DUPLICATE KEY UPDATE
            count = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) > 3600, 1, count + 1),
            window_start = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) > 3600, VALUES(window_start), window_start)");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $now);
    mysqli_stmt_execute($stmt);
}

// ============ SETTINGS & MAINTENANCE MODE ============

function ensure_settings_table($conn){
    static $done = false;
    if($done) return true;
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $done = (bool)mysqli_query($conn, $sql);
    return $done;
}

function get_setting($conn, $key, $default = null){
    ensure_settings_table($conn);
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ?");
    if(!$stmt) return $default;
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ? $row['setting_value'] : $default;
}

function set_setting($conn, $key, $value){
    ensure_settings_table($conn);
    $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    return mysqli_stmt_execute($stmt);
}

function is_maintenance($conn){
    return get_setting($conn, 'maintenance_mode', '0') === '1';
}

function set_maintenance($conn, $enabled){
    return set_setting($conn, 'maintenance_mode', $enabled ? '1' : '0');
}
?>