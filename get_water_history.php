<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入。', 'history' => []]);
    exit();
}

// 基本的GET參數驗證，確保 userId 與 session 中的 userId 匹配
$requestedUserId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;
if ($requestedUserId !== (int)$_SESSION['user']['id']) {
    echo json_encode(['success' => false, 'message' => '無權訪問此數據。', 'history' => []]);
    exit();
}
$userId = $requestedUserId;


$host = 'localhost';
$dbname = 'project';
$user_db = 'root';
$pass_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user_db, $pass_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("資料庫連線失敗 (get_water_history.php)：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤。', 'history' => []]);
    exit();
}

try {
    // 獲取最近30天的飲水記錄，或根據你的需求調整
    $stmt = $pdo->prepare("SELECT intake_date, total_ml FROM daily_water_intake WHERE user_id = ? ORDER BY intake_date DESC LIMIT 30");
    $stmt->execute([$userId]);
    $history = $stmt->fetchAll();

    // Chart.js 通常希望日期是升序的，所以如果需要可以反轉陣列
    $history = array_reverse($history);

    echo json_encode(['success' => true, 'history' => $history]);

} catch (Exception $e) {
    error_log("獲取飲水歷史錯誤 (user_id: {$userId})：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '獲取飲水歷史時發生錯誤。', 'history' => []]);
}
?>