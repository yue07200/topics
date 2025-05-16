<?php
session_start();
$isLoggedIn = isset($_SESSION['user']);
$username = $isLoggedIn ? $_SESSION['user'] : '';
$avatarPath = $isLoggedIn && !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : 'default-avatar.png';
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>健康管家｜登入註冊</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Noto Sans TC', sans-serif;
    }

    body {
      background-color: #f7f9fc;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      position: relative;
    }

    .container {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 500px;
      width: 100%;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #4CAF50;
    }

    .tab-buttons {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }

    .tab-buttons button {
      flex: 1;
      padding: 10px;
      border: none;
      background-color: #e0e0e0;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }

    .tab-buttons button.active {
      background-color: #4CAF50;
      color: white;
    }

    form {
      display: none;
      flex-direction: column;
      margin-top: 10px;
    }

    form.active {
      display: flex;
    }

    input, select {
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    button[type="submit"] {
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
    }

    button[type="submit"]:hover {
      background-color: #45a049;
    }

    .note {
      text-align: center;
      font-size: 0.9rem;
      color: #666;
      margin-top: 10px;
    }

    .form-label {
      margin: 10px 0 5px;
      font-weight: bold;
      font-size: 0.95rem;
      color: #333;
    }

    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 15px;
      margin-bottom: 15px;
      padding: 10px;
      background-color: #f1f1f1;
      border-radius: 8px;
    }

    .checkbox-group label {
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* 返回首頁按鈕樣式 */
    .home-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
    }

    .home-btn:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- 返回首頁按鈕 -->
    <a href="index.php">
      <button class="home-btn">返回首頁</button>
    </a>

    <h2>健康管家會員系統</h2>

    <div class="tab-buttons">
      <button id="loginTab" class="active">登入</button>
      <button id="registerTab">註冊</button>
    </div>

    <!-- 登入表單 -->
    <form id="loginForm" class="active" action="auth.php?action=login" method="POST">
      <input type="text" name="username" placeholder="帳號 / Email" required />
      <input type="password" name="password" placeholder="密碼" required />
      <button type="submit">登入</button>
      <div class="note">忘記密碼請聯絡管理員</div>
    </form>

    <!-- 註冊表單 -->
    <form id="registerForm" action="auth.php?action=register" method="POST">
      <input type="text" name="username" placeholder="帳號" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="密碼" required />
      <input type="password" name="confirm_password" placeholder="確認密碼" required />
      <input type="text" name="fullname" placeholder="姓名" required />
      <input type="date" name="birthdate" required />

      <select name="gender" required>
        <option value="">選擇性別</option>
        <option value="male">男</option>
        <option value="female">女</option>
        <option value="other">其他</option>
      </select>

      <label class="form-label">請勾選你會過敏的食物：</label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="allergens[]" value="蛋"> 蛋</label>
        <label><input type="checkbox" name="allergens[]" value="奶"> 奶</label>
        <label><input type="checkbox" name="allergens[]" value="花生"> 花生</label>
        <label><input type="checkbox" name="allergens[]" value="海鮮"> 海鮮</label>
        <label><input type="checkbox" name="allergens[]" value="堅果"> 堅果</label>
      </div>

      <label class="form-label" for="diet">飲食習慣：</label>
      <select name="diet" id="diet" required>
        <option value="">請選擇</option>
        <option value="omnivore">一般葷食</option>
        <option value="lacto-ovo">蛋奶素</option>
        <option value="vegan">全素</option>
      </select>

      <label class="form-label" for="goal">健康目標：</label>
      <select name="goal" id="goal" required>
        <option value="">請選擇</option>
        <option value="weight-loss">減重</option>
        <option value="muscle-gain">增肌</option>
        <option value="control-sugar">控制血糖</option>
        <option value="general-health">一般健康</option>
      </select>

      <button type="submit">註冊</button>
      <div class="note">註冊即表示同意使用條款</div>
    </form>
  </div>

  <script>
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    loginTab.addEventListener('click', () => {
      loginTab.classList.add('active');
      registerTab.classList.remove('active');
      loginForm.classList.add('active');
      registerForm.classList.remove('active');
    });

    registerTab.addEventListener('click', () => {
      registerTab.classList.add('active');
      loginTab.classList.remove('active');
      registerForm.classList.add('active');
      loginForm.classList.remove('active');
    });

    // 若網址為 login.php#register 則自動切換到註冊頁
    if (window.location.hash === '#register') {
      registerTab.click();
    }
  </script>
</body>
</html>
