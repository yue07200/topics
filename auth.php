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
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit();
    } else {
        echo "帳號或密碼錯誤！";
    }

} elseif ($action === 'register') {
    // 處理註冊
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = $_POST['fullname'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $allergens = isset($_POST['allergens']) ? implode(',', $_POST['allergens']) : '';
    $diet = $_POST['diet'];
    $goal = $_POST['goal'];

    if ($password !== $confirm_password) {
        die("兩次密碼不一致！");
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        die("帳號或 Email 已被註冊！");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, fullname, birthdate, gender, allergens, diet, goal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password, $fullname, $birthdate, $gender, $allergens, $diet, $goal]);

    header("Location: login.php?registered=1");
    exit();

} else {
    echo "未知操作！請確認網址含有 ?action=login 或 ?action=register";
}
?>
