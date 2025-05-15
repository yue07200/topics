<?php
session_start();
header('Content-Type: application/json');

// 檢查使用者是否登入
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入或 Session 無效。', 'history' => []]);
    exit();
}
$userId = (int)$_SESSION['user']['id'];

// --- 資料庫連線設定 ---
$host = 'localhost';
$dbname = 'project';
$user_db = 'root';
$pass_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user_db, $pass_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("資料庫連線失敗 (get_exercise_history.php)：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗。', 'history' => []]);
    exit();
}
// --- 資料庫連線設定結束 ---

// 可選：允許前端指定日期範圍或月份
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // 格式 YYYY-MM

try {
    // 獲取指定月份的運動記錄，或最近 N 筆
    // 為了簡化，先獲取最近30條記錄，按日期分組每日總時長
    $stmt = $pdo->prepare(
        "SELECT exercise_date, SUM(duration_minutes) as total_duration
         FROM daily_exercise_log
         WHERE user_id = ?
         GROUP BY exercise_date
         ORDER BY exercise_date DESC
         LIMIT 30"
    );
    $stmt->execute([$userId]);
    $raw_history = $stmt->fetchAll();

    // 如果圖表需要升序日期
    $history = array_reverse($raw_history);

    // 你也可以獲取更詳細的列表，包含每次運動的類型
    $stmt_detailed = $pdo->prepare("SELECT exercise_date, exercise_type, duration_minutes, notes FROM daily_exercise_log WHERE user_id = ? ORDER BY exercise_date DESC, created_at DESC LIMIT 50");
    $stmt_detailed->execute([$userId]);
    $detailed_history = $stmt_detailed->fetchAll();


    echo json_encode(['success' => true, 'grouped_history' => $history, 'detailed_history' => $detailed_history]);

} catch (Exception $e) {
    error_log("獲取運動歷史錯誤 (user_id: {$userId})：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '獲取運動歷史時發生錯誤。', 'history' => [], 'detailed_history' => []]);
}
?>