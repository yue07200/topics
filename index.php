<?php
session_start();

// 資料庫連線
$pdo = new PDO("mysql:host=localhost;dbname=project;charset=utf8mb4", "root", "");

// 檢查是否登入，並確保使用者資料是陣列
$isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']);
$username = $isLoggedIn ? $_SESSION['user']['username'] : '';
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
            width: 100vw; /* 確保滿版寬度 */
            max-width: 100%; /* 確保不會被容器限制 */
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
            margin-top: 40px; /* 新增上方的外邊距，讓區塊下移 */
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
        /* 修正 user-info 的 CSS 設計 */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }

        .account-icon {
      width: 32px; height: 32px;
      background-color: #ccc; border-radius: 50%;
      cursor: pointer; display: flex; align-items: center;
      justify-content: center; font-weight: bold;
    }

        .username {
            font-weight: bold;
        }

        .logout-btn {
            margin-left: 10px;
            text-decoration: none;
            color: #ffffff;
            background-color: #4CAF50;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #45a049;
        }

        .login-btn {
            text-decoration: none;
            color: #ffffff;
            background-color: #4CAF50;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #45a049;
        }

        /*新聞*/
        .news-preview {
            padding: 40px 0;
            background-color: #f4f9ff;
            text-align: left;
        }

        .news-preview h2 {
            font-size: 2rem;
            color: #2e5caa;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .news-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .news-item {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            border-left: 5px solid #2e5caa;
        }

        .news-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .news-item h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: #2e5caa;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-item h3::before {
            content: "⭐";
            font-size: 1.4rem;
            color: #ffcc00;
            margin-right: 8px;
        }

        .news-item p {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .news-btn {
            display: inline-block;
            background-color: #2e5caa;
            color: #ffffff;
            padding: 8px 16px;
            font-size: 0.9rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
        }

        .news-btn:hover {
            background-color: #1f3e7a;
        }

        .news-btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(46, 92, 170, 0.2);
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
                <?php if ($isLoggedIn && isset($user) && is_array($user)): ?>
                    <div class="user-info">
                        <a href="?logout=true">登出</a>
                        <div class="account-icon">👤</div>
                        <div><?php echo htmlspecialchars($username); ?></div>
                    </div>
                <?php else: ?>
                    <a href="login.php">登入/註冊</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    
    <!-- 功能快速導覽列 -->
    <nav class="top-function-nav container">
        <a href="#map">📍 地點紀錄</a>
        <a href="#">🍱 營養分析</a>
        <a href="#">❤️ 健康建議</a>
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


    <section class="news-preview container">
    <h2>📢 相關新聞</h2>
    <div class="news-list">
        <div class="news-item">
            <h3>正餐時間外老覺得餓？恐是蛋白質不足 營養師教簡單補足擺脫疲勞</h3>
            <p>蛋白質是維持健康與代謝的重要營養素，攝取不足易感疲憊與飢餓。專家建議每餐攝取30公克蛋白質，從早餐起搭配乳製品、堅果等天然食材，有助提升飽足感與修復力，維持身體活力。</p>
            <a href="https://udn.com/news/story/7266/8738067" class="news-btn">閱讀全文</a>
        </div>
        <div class="news-item">
            <h3>養生女自製健康早餐 「1吃法」險得糖尿病！很多人中</h3>
            <p>五穀粉雖健康，但磨粉後易轉為「糖水」，恐導致肥胖與血糖飆升。建議選原型穀物、控制份量，搭配蛋白質與好油脂，避免天天大量飲用。</p>
            <a href="#" class="news-btn">閱讀全文</a>
        </div>
        <div class="news-item">
            <h3>減肥失敗真相曝光！蛋白質吃不夠才是主因</h3>
            <p>研究指出，蛋白質攝取不足會導致過度進食、熱量超標，反而變胖。減重時應確保飲食中蛋白質達15%，提升飽足感、穩定食慾，才能有效控制體重，避免高脂與高糖飲食。</p>
            <a href="https://tw.news.yahoo.com/%E6%B8%9B%E8%82%A5%E5%A4%B1%E6%95%97%E7%9C%9F%E7%9B%B8%E6%9B%9D%E5%85%89-%E8%9B%8B%E7%99%BD%E8%B3%AA%E5%90%83%E4%B8%8D%E5%A4%A0%E6%89%8D%E6%98%AF%E4%B8%BB%E5%9B%A0-002333952.html" class="news-btn">閱讀全文</a>
        </div>
    </div>
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
    <script src="AIzaSyD9pyeuA9MVPnGqUVLwXvOrd0uTMjRd2QQ" async defer></script>
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
</body>

</html>