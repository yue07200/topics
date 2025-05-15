<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入。']);
    exit();
}

$host = 'localhost';
$dbname = 'project';
$user_db = 'root';
$pass_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user_db, $pass_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("資料庫連線失敗 (update_water_intake.php)：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤。']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['userId']) || !isset($input['amountMl']) || (int)$input['userId'] !== (int)$_SESSION['user']['id']) {
    echo json_encode(['success' => false, 'message' => '無效的請求參數或使用者不符。']);
    exit();
}

$userId = (int)$input['userId'];
$amountMl = (int)$input['amountMl'];
$today = date('Y-m-d');

if ($amountMl <= 0) {
    echo json_encode(['success' => false, 'message' => '飲水量必須大於0。']);
    exit();
}

$pdo->beginTransaction();
try {
    // 檢查今天是否已有記錄
    $stmt_check = $pdo->prepare("SELECT id, total_ml FROM daily_water_intake WHERE user_id = ? AND intake_date = ? FOR UPDATE");
    $stmt_check->execute([$userId, $today]);
    $existingRecord = $stmt_check->fetch();

    $newTotalMl = 0;

    if ($existingRecord) {
        // 更新現有記錄
        $newTotalMl = $existingRecord['total_ml'] + $amountMl;
        $stmt_update = $pdo->prepare("UPDATE daily_water_intake SET total_ml = ? WHERE id = ?");
        $stmt_update->execute([$newTotalMl, $existingRecord['id']]);
    } else {
        // 插入新記錄
        $newTotalMl = $amountMl;
        $stmt_insert = $pdo->prepare("INSERT INTO daily_water_intake (user_id, intake_date, total_ml) VALUES (?, ?, ?)");
        $stmt_insert->execute([$userId, $today, $newTotalMl]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'newTotalMl' => $newTotalMl, 'message' => '飲水記錄已更新。']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("更新飲水記錄錯誤 (user_id: {$userId})：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '更新飲水記錄時發生錯誤。']);
}
?>