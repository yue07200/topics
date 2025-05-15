<?php
session_start();
header('Content-Type: application/json'); // 確保返回的是 JSON

// 檢查使用者是否登入
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '使用者未登入或 Session 無效。']);
    exit();
}
$userId = $_SESSION['user']['id'];

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
    error_log("資料庫連線失敗 (update_achievement.php)：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗，請稍後再試。']);
    exit();
}
// --- 資料庫連線設定結束 ---

// 獲取從前端發送的數據
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['achievementType'])) {
    echo json_encode(['success' => false, 'message' => '無效的請求參數。']);
    exit();
}

$achievementType = $input['achievementType'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 開始資料庫事務，確保資料一致性
$pdo->beginTransaction();

try {
    // 1. 查詢使用者該成就的現有記錄
    $stmt = $pdo->prepare("SELECT id, current_level, last_check_in_date, current_streak FROM user_achievements WHERE user_id = ? AND achievement_type = ? FOR UPDATE"); // FOR UPDATE 防止併發問題
    $stmt->execute([$userId, $achievementType]);
    $achievement = $stmt->fetch();

    $newLevel = 0;
    $newStreak = 0;

    if ($achievement) { // 如果已有記錄
        $lastCheckInDate = $achievement['last_check_in_date'];
        $currentLevel = (int)$achievement['current_level'];
        $currentStreak = (int)$achievement['current_streak'];

        if ($lastCheckInDate === $today) {
            $pdo->rollBack(); // 如果已經回滾，就不需要再次提交
            echo json_encode(['success' => false, 'message' => '今天已經打過卡了！']);
            exit();
        }

        // 更新等級和連續打卡
        $newLevel = $currentLevel + 1; // 每次打卡都升級

        if ($lastCheckInDate === $yesterday) { // 連續打卡
            $newStreak = $currentStreak + 1;
        } else { // 中斷打卡或首次打卡（雖然首次會在 else 分支）
            $newStreak = 1;
        }

        $updateStmt = $pdo->prepare("UPDATE user_achievements SET current_level = ?, last_check_in_date = ?, current_streak = ? WHERE id = ?");
        $updateStmt->execute([$newLevel, $today, $newStreak, $achievement['id']]);

    } else { // 如果是該成就的第一次打卡
        $newLevel = 1; // 初始等級
        $newStreak = 1; // 初始連續天數

        $insertStmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_type, current_level, last_check_in_date, current_streak) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$userId, $achievementType, $newLevel, $today, $newStreak]);
    }

    $pdo->commit(); // 提交事務
    echo json_encode(['success' => true, 'newLevel' => $newLevel, 'newStreak' => $newStreak, 'message' => '打卡成功！']);

} catch (Exception $e) {
    $pdo->rollBack(); // 如果發生錯誤，回滾事務
    error_log("成就更新錯誤 (user_id: {$userId}, type: {$achievementType})：" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '更新成就時發生錯誤，請稍後再試。']);
}

?>