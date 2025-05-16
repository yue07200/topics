<?php
session_start();

// 資料庫連線設定
$host = 'localhost';
$dbname = 'project';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

// 判斷是登入或註冊
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    // 處理登入
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 儲存整個使用者資料在 Session（確保是陣列）
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'birthdate' => $user['birthdate'],
            'gender' => $user['gender'],
            'allergens' => $user['allergens'],
            'diet' => $user['diet'],
            'goal' => $user['goal']
        ];
        header("Location: index.php");
        exit();
    } else {
        // 登入失敗
        echo "<script>alert('帳號或密碼錯誤！'); window.location.href='login.php';</script>";
        exit();
    }

} elseif ($action === 'register') {
    // 處理註冊
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $fullname = trim($_POST['fullname']);
    $birthdate = trim($_POST['birthdate']);
    $gender = $_POST['gender'];
    $allergens = isset($_POST['allergens']) ? implode(',', array_map('htmlspecialchars', $_POST['allergens'])) : '';
    $diet = $_POST['diet'];
    $goal = $_POST['goal'];

    // 檢查密碼一致性
    if ($password !== $confirm_password) {
        echo "<script>alert('兩次密碼不一致！'); window.location.href='login.php#register';</script>";
        exit();
    }

    // 檢查帳號或 Email 是否已被註冊
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo "<script>alert('帳號或 Email 已被註冊！'); window.location.href='login.php#register';</script>";
        exit();
    }

    // 雜湊密碼
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 寫入資料庫
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, fullname, birthdate, gender, allergens, diet, goal) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password, $fullname, $birthdate, $gender, $allergens, $diet, $goal]);

    // 自動登入（將新使用者資料存入 Session）
    $newUserId = $pdo->lastInsertId();
    $_SESSION['user'] = [
        'id' => $newUserId,
        'username' => $username,
        'email' => $email,
        'fullname' => $fullname,
        'birthdate' => $birthdate,
        'gender' => $gender,
        'allergens' => $allergens,
        'diet' => $diet,
        'goal' => $goal
    ];
    
    header("Location: index.php");
    exit();

} else {
    // 未知操作處理
    echo "未知操作！請確認網址含有 ?action=login 或 ?action=register";
    exit();
}

?>