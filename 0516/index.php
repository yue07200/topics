<?php
session_start();

// 資料庫連線
$pdo = new PDO("mysql:host=localhost;dbname=project;charset=utf8mb4", "root", "");

// 檢查是否登入
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$avatarPath = $isLoggedIn ? 'user-avatar.png' : 'default-avatar.png';

// 取得使用者詳細資料
$user = null;
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT fullname, birthdate, gender, diet, goal FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>健康管家 Health Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans TC', sans-serif;
        }

        body {
            line-height: 1.6;
            background-color: #f7f9fc;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px 0;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header nav a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        header nav a:hover {
            text-decoration: underline;
        }

        .top-function-nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            background-color: #ffffff;
            padding: 15px 0;
            gap: 20px;
            border-bottom: 1px solid #ddd;
        }

        .top-function-nav a {
            text-decoration: none;
            color: #4CAF50;
            font-size: 1.1rem;
            font-weight: bold;
            transition: color 0.2s;
        }

        .top-function-nav a:hover {
            color: #2e7d32;
        }

        .hero {
            background-color: #e8f5e9;
            padding: 60px 0;
            text-align: center;
        }

        .hero h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .features {
            padding: 40px 0;
        }

        .features h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .features ul {
            list-style: none;
            padding-left: 0;
        }

        .features li {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .function-menu {
            padding: 40px 0;
        }

        .function-menu h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: #333;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .menu-card span {
            font-size: 2rem;
            display: block;
            margin-bottom: 10px;
        }

        .map-preview {
            padding: 40px 0;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        footer {
            background-color: #f1f1f1;
            color: #666;
            text-align: center;
            padding: 20px 0;
            font-size: 0.9rem;
        }
        /* 聊天按鈕 */
        .chatbot-button {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background-color: #4CAF50;
            color: #fff;
            padding: 12px 16px;
            border-radius: 30px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 9999; /* 確保在地圖上方 */
        }

        /* 聊天視窗 */
        .chatbot-window {
            position: fixed;
            right: 20px;
            bottom: 70px;
            width: 300px;
            max-height: 400px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 9999; /* 確保在地圖上方 */
        }


        /* 其他樣式保持不變 */
        .chatbot-header {
            background-color: #4CAF50;
            color: #fff;
            padding: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .chatbot-content {
            padding: 10px;
            overflow-y: auto;
            max-height: 300px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .chatbot-input {
            display: flex;
            border-top: 1px solid #ddd;
        }

        .chatbot-input input {
            flex: 1;
            border: none;
            padding: 10px;
            outline: none;
        }

        .chatbot-input button {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: bold;
        }

        .chatbot-input button:hover {
            background-color: #45a049;
        }

        /* 訊息樣式 */
        .chatbot-message {
            padding: 8px;
            border-radius: 8px;
            max-width: 80%;
        }

        .user-message {
            background-color: #e0f7fa;
            align-self: flex-end;
        }

        .bot-message {
            background-color: #f1f1f1;
            align-self: flex-start;
        }


    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>健康管家</h1>
            <nav>
                <a href="index.html">首頁</a>
                <a href="logout.php">登出</a>
                <a href="login.php">登入/註冊</a>



            </nav>
        </div>
    </header>

    <!-- 功能快速導覽列 -->
    <nav class="top-function-nav container">
        <a href="#map">📍 地點紀錄</a>
        <a href="#">🍱 營養分析</a>
        <a href="#">❤️ 健康建議</a>
        <a href="#">📊 趨勢統計</a>
        <a href="#">🤝 社群挑戰</a>
        <a href="bmi.html">📐 BMI 計算</a>
        <a href="achievements.php">💪 我的成就</a>
    </nav>

    <section class="hero">
        <div class="container">
            <h2>讓飲食更智慧，健康看得見</h2>
            <p>結合 AI 辨識與地理定位，提供全面性的健康飲食分析與社群互動。</p>
        </div>
    </section>


    <section class="map-preview container">
        <h2>地圖預覽</h2>
        <div id="map"></div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 健康管家 Health Tracker</p>
        </div>
    </footer>
    <!-- 聊天機器人按鈕 -->
    <div class="chatbot-button" onclick="toggleChatbot()">
    💬 聊天
    </div>

    <!-- 聊天機器人彈跳視窗 -->
    <div class="chatbot-window" id="chatbot-window">
    <div class="chatbot-header">
        <h3>健康管家小助手</h3>
        <button onclick="toggleChatbot()">✖️</button>
    </div>
    <div class="chatbot-content" id="chatbot-content">
        <div class="chatbot-message bot-message">您好！我是健康管家小助手，有什麼我可以幫助您的嗎？</div>
    </div>
    <div class="chatbot-input">
        <input type="text" id="chatbot-input" placeholder="請輸入您的問題...">
        <button onclick="sendChatMessage()">送出</button>
    </div>
    </div>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
    <script>
        function initMap() {
            const defaultLocation = { lat: 25.0330, lng: 121.5654 }; // 台北市
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: defaultLocation,
            });
        }
        // 控制聊天機器人視窗開關
        function toggleChatbot() {
            const chatbotWindow = document.getElementById("chatbot-window");
            chatbotWindow.style.display = chatbotWindow.style.display === "block" ? "none" : "block";
        }

        // 送出訊息
        async function sendChatMessage() {
            const inputField = document.getElementById("chatbot-input");
            const message = inputField.value.trim();
            if (message) {
                displayUserMessage(message);
                inputField.value = "";

                // 顯示載入動畫
                displayBotMessage("正在回應中...");

                // 發送到後端 API
                const response = await fetch("chatbot_api.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                const botMessage = data.choices?.[0]?.message?.content ?? "抱歉，我無法回答您的問題。";
                updateBotMessage(botMessage);
            }
        }

        // 顯示使用者訊息
        function displayUserMessage(message) {
            const content = document.getElementById("chatbot-content");
            const userMessage = document.createElement("div");
            userMessage.className = "chatbot-message user-message";
            userMessage.textContent = message;
            content.appendChild(userMessage);
            content.scrollTop = content.scrollHeight;
        }

        // 顯示機器人訊息
        function displayBotMessage(message) {
            const content = document.getElementById("chatbot-content");
            const botMessage = document.createElement("div");
            botMessage.className = "chatbot-message bot-message";
            botMessage.id = "bot-message";
            botMessage.textContent = message;
            content.appendChild(botMessage);
            content.scrollTop = content.scrollHeight;
        }

        // 更新機器人訊息（回應）
        function updateBotMessage(message) {
            const botMessage = document.getElementById("bot-message");
            if (botMessage) {
                botMessage.textContent = message;
            }
        }
    </script>
    <?php
    // 登出處理
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    ?>

    <section class="container">
        <h2>歡迎使用健康管家</h2>
        <p>這裡是您的健康管理平台。</p>
        <?php if ($isLoggedIn && $user): ?>
            <h3>您的帳號資訊：</h3>
            <p>帳號名稱：<?php echo htmlspecialchars($username); ?></p>
            <p>姓名：<?php echo htmlspecialchars($user['fullname']); ?></p>
            <p>生日：<?php echo htmlspecialchars($user['birthdate']); ?></p>
            <p>性別：<?php echo htmlspecialchars($user['gender']); ?></p>
            <p>飲食習慣：<?php echo htmlspecialchars($user['diet']); ?></p>
            <p>健康目標：<?php echo htmlspecialchars($user['goal']); ?></p>
        <?php else: ?>
            <p>請先登入以查看您的帳號資訊。</p>
        <?php endif; ?>
    </section>
</body>

</html>