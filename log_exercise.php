<?php
session_start();
header('Content-Type: application/json');

// 檢查使用者是否登入
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入或 Session 無效。']);
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
    error_log("資料庫連線失敗 (log_exercise.php)：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗，請稍後再試。']);
    exit();
}
// --- 資料庫連線設定結束 ---

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['exercise_type']) || !isset($input['duration_minutes']) || !isset($input['exercise_date'])) {
    echo json_encode(['success' => false, 'message' => '無效的請求參數。']);
    exit();
}

$exerciseType = trim($input['exercise_type']);
$durationMinutes = (int)$input['duration_minutes'];
$exerciseDate = $input['exercise_date']; // 格式應為 YYYY-MM-DD
$notes = isset($input['notes']) ? trim($input['notes']) : null;

// 基本驗證
if (empty($exerciseType)) {
    echo json_encode(['success' => false, 'message' => '請選擇運動類型。']);
    exit();
}
if ($durationMinutes <= 0) {
    echo json_encode(['success' => false, 'message' => '運動時長必須大於0分鐘。']);
    exit();
}
// 驗證日期格式 (簡單驗證)
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $exerciseDate)) {
    echo json_encode(['success' => false, 'message' => '運動日期格式不正確。']);
    exit();
}


$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO daily_exercise_log (user_id, exercise_date, exercise_type, duration_minutes, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $exerciseDate, $exerciseType, $durationMinutes, $notes]);
    $logId = $pdo->lastInsertId();

    // ---- 成就判斷邏輯 (未來擴展) ----
    // 1. 檢查 'first_run' (假設 $exerciseType === 'running')
    //    - 查詢 daily_exercise_log 是否 user_id 和 exercise_type='running' 的記錄只有一條 (剛插入的)
    //    - 如果是，則解鎖 'first_run' 成就，寫入 unlocked_user_achievements
    // 2. 更新累計運動時長
    //    - 查詢使用者所有運動記錄的總時長
    //    - 檢查是否達到 'total_exercise_60_min' 等成就的 target_value
    // 3. 更新連續運動天數
    //    - 查詢該使用者在 daily_exercise_log 中，exercise_date 連續的天數
    //    - 檢查是否達到 '3_day_exercise_streak' 等成就的 target_value
    // ---------------------------------

    $pdo->commit();
    // 返回今日該日期的總運動時長 (如果需要在前端立即更新)
    $stmt_total_today = $pdo->prepare("SELECT SUM(duration_minutes) as total_duration FROM daily_exercise_log WHERE user_id = ? AND exercise_date = ?");
    $stmt_total_today->execute([$userId, $exerciseDate]);
    $dailyTotal = $stmt_total_today->fetch();

    echo json_encode([
        'success' => true,
        'message' => '運動記錄已成功添加！',
        'log_id' => $logId,
        'daily_total_duration' => $dailyTotal ? (int)$dailyTotal['total_duration'] : 0
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("記錄運動錯誤 (user_id: {$userId}, type: {$exerciseType})：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '記錄運動時發生錯誤，請稍後再試。']);
}
?>