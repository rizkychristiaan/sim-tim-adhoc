<?php
/**
 * ==============================================================================
 * FILE: app/pages/mark_notif.php (Handler Penanda Notifikasi Dibaca)
 * FUNGSI UTAMA:
 *   1. Memproses penandaan notifikasi sebagai "Sudah Dibaca" (is_read = 1).
 *   2. Menerima aksi "read_all" (semua notifikasi) atau "read_one" (satu notifikasi).
 *   3. Mendukung respons AJAX JSON maupun redirect HTTP biasa.
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'read_all';

if ($action === 'read_all') {
    $stmt = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
} elseif ($action === 'read_one') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = ? AND id = ?");
    $stmt->execute([$user_id, $id]);
}

if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $referer);
exit();
?>
