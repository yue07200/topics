<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

$host = 'localhost';
$dbname = 'project';
$user_db = 'root'; // Renamed to avoid conflict if $user is global elsewhere
$pass_db = '';     // Renamed to avoid conflict if $pass is global elsewhere

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user_db, $pass_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("資料庫連線失敗 (achievements.php)：" . $e->getMessage());
    die("系統暫時無法服務，請稍後再試。");
}

// 獲取今日飲水數據
$today = date('Y-m-d');
$todaysIntake = 0;
$stmt_today_water = $pdo->prepare("SELECT total_ml FROM daily_water_intake WHERE user_id = ? AND intake_date = ?");
$stmt_today_water->execute([$userId, $today]);
$todayWaterData = $stmt_today_water->fetch();
if ($todayWaterData) {
    $todaysIntake = (int)$todayWaterData['total_ml'];
}
$dailyWaterGoal = 2000;
$cupSizeMl = 240;

// 獲取今日運動總時長 (假設運動日期輸入框預設是今天)
$todaysExerciseDuration = 0;
$stmt_today_exercise = $pdo->prepare("SELECT SUM(duration_minutes) as total_duration FROM daily_exercise_log WHERE user_id = ? AND exercise_date = ?");
$stmt_today_exercise->execute([$userId, $today]);
$todayExerciseData = $stmt_today_exercise->fetch();
if ($todayExerciseData && $todayExerciseData['total_duration']) {
    $todaysExerciseDuration = (int)$todayExerciseData['total_duration'];
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的健康記錄 - 健康管家</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: #f7f9fc;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .main-container { /* Changed from .container to avoid conflict with bootstrap if used */
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px; /* Wider for better layout */
            margin: 0 auto 20px auto;
        }
        .page-title {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .back-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #388e3c;
        }

        .tracker-section {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 25px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .tracker-summary-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 1px solid #e0e0e0; /* Separator when expanded */
        }
        .tracker-summary-bar:hover {
            background-color: #f9f9f9;
        }
        .tracker-summary-bar h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #333; /* Darker for summary title */
            display: flex;
            align-items: center;
        }
        .tracker-summary-bar h2 i { /* Icon style */
            margin-right: 12px;
            color: #4CAF50; /* Green icon */
        }
        .summary-info {
            font-size: 0.95rem;
            color: #555;
        }
        .toggle-details-btn {
            background: none;
            border: none;
            font-size: 1.5rem; /* Chevron icon size */
            color: #4CAF50;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .tracker-details.expanded .toggle-details-btn {
            transform: rotate(180deg);
        }

        .tracker-details-content {
            padding: 20px;
            display: none; /* Hidden by default */
            border-top: 1px solid #e0e0e0; /* Add separator only if summary bar doesn't have one always */
        }
        .tracker-section.expanded .tracker-details-content {
            display: block;
        }
        .tracker-section.expanded .tracker-summary-bar {
             border-bottom-color: #e0e0e0; /* Ensure border is visible when expanded */
        }


        /* Input groups and buttons within details */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box; /* Important */
        }
        .form-group .cup-info, .form-group .input-hint { font-size: 0.85em; color: #666; margin-top: 5px; }
        .action-button {
            background-color: #4CAF50; /* Default green */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            width: 100%; /* Make buttons full width in their context */
            margin-top: 10px;
        }
        .action-button:hover:not(:disabled) { background-color: #388e3c; }
        .action-button:disabled { background-color: #ccc; cursor: not-allowed; }
        .status-message { margin-top: 10px; text-align: center; font-size: 0.9rem; }

        /* Progress bar for water */
        .progress-bar-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            margin-top: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background-color: #2196F3;
            width: 0%;
            border-radius: 10px;
            text-align: center;
            line-height: 20px;
            color: white;
            font-size: 0.8rem;
            transition: width 0.5s ease, background-color 0.3s ease;
        }
        /* History section (shared style) */
        .history-controls { text-align: center; margin-top:15px; margin-bottom: 15px; }
        .history-controls button {
            background-color: #5c6bc0; /* Indigo */
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .history-controls button:hover { background-color: #3949ab; }

        .recent-log-list { list-style: none; padding-left: 0; font-size: 0.9rem; }
        .recent-log-list li { padding: 6px 0; border-bottom: 1px dotted #eee; }
        .recent-log-list li:last-child { border-bottom: none; }

        /* Modal and Chart Styles (from previous responses) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #ddd; width: 90%; max-width: 750px; border-radius: 10px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.2); display: flex; flex-direction: column; align-items: center; }
        .close-btn { color: #aaa; position: absolute; top: 10px; right: 15px; font-size: 30px; font-weight: bold; line-height: 1; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; }
        .chart-wrapper { width: 100%; max-width: 600px; height: 400px; margin-top: 20px; margin-bottom: 10px; }
        #modalWaterIntakeChart, #modalExerciseChart { display: block; width: 100% !important; height: 100% !important; }

    </style>
</head>
<body>
    <a href="index.php" class="back-btn">返回首頁</a>

    <div class="main-container">
        <h1 class="page-title">我的健康記錄</h1>

        <div class="tracker-section" id="waterTrackerSection">
            <div class="tracker-summary-bar" data-target="waterDetailsContent">
                <h2><i class="fas fa-tint"></i>每日飲水</h2>
                <div class="summary-info">
                    今日：<strong id="todaysIntakeDisplay"><?php echo $todaysIntake; ?></strong> / <?php echo $dailyWaterGoal; ?> ml
                    <div class="progress-bar-container" style="width: 100px; height:10px; display:inline-block; margin-left:10px; vertical-align: middle;">
                        <div class="progress-bar" id="summaryWaterProgressBar"></div>
                    </div>
                </div>
                <button class="toggle-details-btn"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="tracker-details-content" id="waterDetailsContent">
                <div class="form-group">
                    <label for="waterCups">杯數：</label>
                    <input type="number" id="waterCups" name="waterCups" min="1" value="1">
                    <p class="cup-info">(一杯約 <?php echo $cupSizeMl; ?>ml，類似中杯手搖飲或馬克杯大小)</p>
                </div>
                <button id="addWaterButton" class="action-button">增加飲水 (+<span id="mlToAddDisplay"><?php echo $cupSizeMl; ?></span>ml)</button>
                <div id="waterLogStatus" class="status-message"></div>
                <div class="history-controls">
                    <button id="showWaterHistoryButton">飲水歷史圖表</button>
                </div>
            </div>
        </div>

        <div class="tracker-section" id="exerciseTrackerSection">
            <div class="tracker-summary-bar" data-target="exerciseDetailsContent">
                <h2><i class="fas fa-running"></i>運動活力</h2>
                <div class="summary-info">
                    <span id="selectedDateForExerciseSummary">今天</span>總時長：<strong id="todaysExerciseDurationSummaryDisplay"><?php echo $todaysExerciseDuration; ?></strong> 分鐘
                </div>
                <button class="toggle-details-btn"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="tracker-details-content" id="exerciseDetailsContent">
                <h3>記錄運動</h3>
                <div class="form-group">
                    <label for="exerciseDate">運動日期：</label>
                    <input type="date" id="exerciseDate" name="exerciseDate">
                </div>
                <div class="form-group">
                    <label for="exerciseType">運動類型：</label>
                    <select id="exerciseType" name="exerciseType">
                        <option value="">請選擇類型</option>
                        <option value="running">跑步</option>
                        <option value="walking">健走</option>
                        <option value="cycling">騎自行車</option>
                        <option value="swimming">游泳</option>
                        <option value="yoga">瑜伽</option>
                        <option value="weight_training">重量訓練</option>
                        <option value="aerobics">有氧運動</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exerciseDuration">運動時長（分鐘）：</label>
                    <input type="number" id="exerciseDuration" name="exerciseDuration" min="1" placeholder="例如：30">
                </div>
                <div class="form-group">
                    <label for="exerciseNotes">備註（可選）：</label>
                    <textarea id="exerciseNotes" name="exerciseNotes" rows="2" placeholder="例如：今天狀態很好！"></textarea>
                </div>
                <button id="logExerciseButton" class="action-button" style="background-color: #FF9800;">記錄運動</button>
                <div id="exerciseLogStatus" class="status-message"></div>

                <div class="history-controls">
                    <button id="showExerciseHistoryButton">運動歷史圖表</button>
                </div>
                <div id="recentExerciseList" style="margin-top:15px;">
                    <h4>最近的運動記錄：</h4>
                    <ul id="exerciseListItems" class="recent-log-list"></ul>
                </div>
            </div>
        </div>

        </div>

    <div id="waterHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal-id="waterHistoryModal">&times;</span>
            <h2>飲水歷史折線圖</h2>
            <div class="chart-wrapper">
                <canvas id="modalWaterIntakeChart"></canvas>
            </div>
        </div>
    </div>

    <div id="exerciseHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal-id="exerciseHistoryModal">&times;</span>
            <h2>運動歷史圖表</h2>
            <div class="chart-wrapper">
                <canvas id="modalExerciseChart"></canvas> </div>
        </div>
    </div>

<script>
    const userId = <?php echo $userId; ?>;

    // --- Generic Toggle Details ---
    // --- Generic Toggle Details ---
    document.querySelectorAll('.tracker-summary-bar').forEach(bar => {
        bar.addEventListener('click', function(event) {
            const toggleButton = this.querySelector('.toggle-details-btn');
            let clickedOnToggleButtonIcon = false;
            if (toggleButton && toggleButton.contains(event.target)) {
                clickedOnToggleButtonIcon = true; // Clicked on the toggle button or its icon
            }

            // Prevent toggling IF the click was on an interactive element INSIDE the summary bar,
            // BUT NOT on the toggle button itself.
            const closestInteractiveElement = event.target.closest('button, a, input, select, textarea');
            if (closestInteractiveElement && closestInteractiveElement !== toggleButton && !toggleButton.contains(closestInteractiveElement)) {
                // Clicked on something else interactive within the bar that isn't the toggle button
                return;
            }

            const targetId = this.dataset.target;
            const detailsContent = document.getElementById(targetId);
            const section = this.closest('.tracker-section');

            if (detailsContent && section) { // Ensure section is also found
                const isExpanded = section.classList.toggle('expanded');
                const iconElement = toggleButton ? toggleButton.querySelector('i') : null; // Get icon from the actual toggle button

                if (iconElement) {
                    iconElement.classList.toggle('fa-chevron-down', !isExpanded);
                    iconElement.classList.toggle('fa-chevron-up', isExpanded);
                }
            } else {
                console.warn("Details content or section not found for targetId:", targetId, "or section:", section);
            }
        });
    });


    // --- Water Tracker ---
    let todaysIntake = <?php echo $todaysIntake; ?>;
    const dailyWaterGoal = <?php echo $dailyWaterGoal; ?>;
    const cupSizeMl = <?php echo $cupSizeMl; ?>;

    const todaysIntakeDisplay = document.getElementById('todaysIntakeDisplay');
    const summaryWaterProgressBar = document.getElementById('summaryWaterProgressBar'); // For summary bar
    const waterCupsInput = document.getElementById('waterCups');
    const addWaterButton = document.getElementById('addWaterButton');
    const mlToAddDisplay = document.getElementById('mlToAddDisplay');
    const waterLogStatus = document.getElementById('waterLogStatus');
    const showWaterHistoryButton = document.getElementById('showWaterHistoryButton');
    const waterHistoryModal = document.getElementById('waterHistoryModal');
    let modalWaterIntakeChart = null;

    function updateWaterSummary() {
        todaysIntakeDisplay.textContent = todaysIntake;
        let progressPercent = (todaysIntake / dailyWaterGoal) * 100;
        summaryWaterProgressBar.style.width = Math.min(100, progressPercent) + '%';
        // summaryWaterProgressBar.textContent = Math.round(progressPercent) + '%'; // Text might be too small for summary bar
        if (todaysIntake >= dailyWaterGoal) {
            summaryWaterProgressBar.style.backgroundColor = '#4CAF50';
        } else {
            summaryWaterProgressBar.style.backgroundColor = '#2196F3';
        }
    }

    if(waterCupsInput) {
        waterCupsInput.addEventListener('input', function() {
            const cups = parseInt(this.value) || 0;
            mlToAddDisplay.textContent = cups * cupSizeMl;
        });
    }

    if(addWaterButton) {
        addWaterButton.addEventListener('click', function() {
            const cups = parseInt(waterCupsInput.value);
            if (isNaN(cups) || cups <= 0) {
                waterLogStatus.textContent = '請輸入有效的杯數！';
                waterLogStatus.style.color = 'red';
                return;
            }
            const amountMl = cups * cupSizeMl;
            this.disabled = true;
            this.textContent = '記錄中...';
            waterLogStatus.textContent = '';

            fetch('update_water_intake.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId: userId, amountMl: amountMl })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    todaysIntake = data.newTotalMl;
                    updateWaterSummary();
                    waterCupsInput.value = 1;
                    mlToAddDisplay.textContent = cupSizeMl;
                    waterLogStatus.textContent = data.message;
                    waterLogStatus.style.color = 'green';
                    if (waterHistoryModal.style.display === "block" && modalWaterIntakeChart) {
                        fetchWaterHistoryAndDrawChart();
                    }
                } else {
                    waterLogStatus.textContent = '記錄飲水失敗：' + data.message;
                    waterLogStatus.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('記錄飲水錯誤:', error);
                waterLogStatus.textContent = '記錄飲水時發生錯誤。';
                waterLogStatus.style.color = 'red';
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = `增加飲水 (+${mlToAddDisplay.textContent}ml)`;
            });
        });
    }

    function fetchWaterHistoryAndDrawChart() {
        fetch(`get_water_history.php?userId=${userId}`)
            .then(response => response.json())
            .then(data => {
                const canvasId = 'modalWaterIntakeChart';
                const ctx = document.getElementById(canvasId).getContext('2d');
                if (modalWaterIntakeChart) {
                    modalWaterIntakeChart.destroy();
                }
                if (data.success && data.history.length > 0) {
                    const labels = data.history.map(item => item.intake_date);
                    const Sdata = data.history.map(item => item.total_ml);
                    modalWaterIntakeChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: '每日飲水量 (ml)', data: Sdata, borderColor: '#2196F3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)', tension: 0.1, fill: true
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: '飲水量 (ml)' }}, x: { title: { display: true, text: '日期' }}}}
                    });
                } else {
                    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                    ctx.font = "16px 'Noto Sans TC'"; ctx.textAlign = "center";
                    ctx.fillText(data.history.length === 0 ? "尚無飲水記錄可顯示。" : ("獲取歷史失敗: " + (data.message || "未知錯誤")), ctx.canvas.width / 2, ctx.canvas.height / 2);
                    modalWaterIntakeChart = null;
                }
            }).catch(error => console.error('獲取飲水歷史錯誤:', error));
    }

    if(showWaterHistoryButton) {
        showWaterHistoryButton.addEventListener('click', function() {
            waterHistoryModal.style.display = "block";
            fetchWaterHistoryAndDrawChart();
        });
    }

    // --- Exercise Tracker ---
    let todaysExerciseDuration = <?php echo $todaysExerciseDuration; ?>;
    const exerciseDateInput = document.getElementById('exerciseDate');
    const exerciseTypeSelect = document.getElementById('exerciseType');
    const exerciseDurationInput = document.getElementById('exerciseDuration');
    const exerciseNotesInput = document.getElementById('exerciseNotes');
    const logExerciseButton = document.getElementById('logExerciseButton');
    const exerciseLogStatus = document.getElementById('exerciseLogStatus');
    const todaysExerciseDurationSummaryDisplay = document.getElementById('todaysExerciseDurationSummaryDisplay');
    const selectedDateForExerciseSummary = document.getElementById('selectedDateForExerciseSummary');
    const showExerciseHistoryButton = document.getElementById('showExerciseHistoryButton');
    const exerciseHistoryModal = document.getElementById('exerciseHistoryModal');
    let modalExerciseChart = null;
    const exerciseListItems = document.getElementById('exerciseListItems');

    function formatDateForAPI(date) { /* ... (same as before) ... */
        const d = new Date(date); let month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
        if (month.length < 2) month = '0' + month; if (day.length < 2) day = '0' + day; return [year, month, day].join('-');
    }
    function formatDate(date) { /* ... (same as before) ... */
        const d = new Date(date); return `${d.getMonth() + 1}月${d.getDate()}日`;
    }

    if(exerciseDateInput){
        exerciseDateInput.valueAsDate = new Date(); // Default to today
        selectedDateForExerciseSummary.textContent = formatDate(new Date());
        // Function to fetch and update daily total for exercise section summary
        function updateExerciseDateSummary(dateStr) {
            fetch(`get_exercise_history.php?date=${dateStr}`) // Modify backend to accept a specific date for total
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.daily_total !== undefined) { // Backend should return daily_total for the specific date
                        todaysExerciseDurationSummaryDisplay.textContent = data.daily_total;
                        todaysExerciseDuration = data.daily_total; // Update global var if needed
                    } else {
                        // If no record for the day, or if backend doesn't support specific date query well yet,
                        // it might show 0 or the overall today's total.
                        // For now, if a specific date is selected and no data comes back for it, show 0 for that date.
                        if(dateStr !== formatDateForAPI(new Date())) { // If not today
                           todaysExerciseDurationSummaryDisplay.textContent = "0";
                        } else { // if it is today, use the PHP preloaded value
                           todaysExerciseDurationSummaryDisplay.textContent = <?php echo $todaysExerciseDuration; ?>;
                        }
                    }
                    selectedDateForExerciseSummary.textContent = formatDate(dateStr);
                })
                .catch(error => {
                    console.error('Error fetching daily exercise total:', error);
                     todaysExerciseDurationSummaryDisplay.textContent = "0"; // Fallback
                     selectedDateForExerciseSummary.textContent = formatDate(dateStr);
                });
        }
        // updateExerciseDateSummary(formatDateForAPI(new Date())); // Initial call for today

        exerciseDateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                updateExerciseDateSummary(selectedDate);
            } else { // Should not happen if date input is used
                selectedDateForExerciseSummary.textContent = "選擇的日期";
                todaysExerciseDurationSummaryDisplay.textContent = "0";
            }
        });
    }


    if(logExerciseButton) {
        logExerciseButton.addEventListener('click', function() {
            const exerciseDate = exerciseDateInput.value;
            const exerciseType = exerciseTypeSelect.value;
            const durationMinutes = parseInt(exerciseDurationInput.value);
            const notes = exerciseNotesInput.value;

            if (!exerciseDate) { exerciseLogStatus.textContent = '請選擇運動日期！'; exerciseLogStatus.style.color = 'red'; return; }
            if (!exerciseType) { exerciseLogStatus.textContent = '請選擇運動類型！'; exerciseLogStatus.style.color = 'red'; return; }
            if (isNaN(durationMinutes) || durationMinutes <= 0) { exerciseLogStatus.textContent = '請輸入有效的運動時長！'; exerciseLogStatus.style.color = 'red'; return; }

            this.disabled = true; this.textContent = '記錄中...'; exerciseLogStatus.textContent = '';

            fetch('log_exercise.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ exercise_date: exerciseDate, exercise_type: exerciseType, duration_minutes: durationMinutes, notes: notes })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exerciseLogStatus.textContent = data.message;
                    exerciseLogStatus.style.color = 'green';
                    todaysExerciseDuration = data.daily_total_duration || 0; // Update global from direct response
                    todaysExerciseDurationSummaryDisplay.textContent = todaysExerciseDuration; // Update summary
                    selectedDateForExerciseSummary.textContent = formatDate(exerciseDate); // Ensure summary date is correct

                    exerciseDurationInput.value = ''; exerciseNotesInput.value = '';
                    fetchRecentExerciseList();
                    if (exerciseHistoryModal.style.display === "block" && modalExerciseChart) {
                        fetchExerciseHistoryAndDrawChart();
                    }
                } else {
                    exerciseLogStatus.textContent = '記錄失敗：' + data.message;
                    exerciseLogStatus.style.color = 'red';
                }
            })
            .catch(error => { console.error('記錄運動錯誤:', error); exerciseLogStatus.textContent = '記錄時發生錯誤。'; exerciseLogStatus.style.color = 'red';})
            .finally(() => { this.disabled = false; this.textContent = '記錄運動'; });
        });
    }

    function fetchExerciseHistoryAndDrawChart() {
        fetch('get_exercise_history.php')
            .then(response => response.json())
            .then(data => {
                const canvasId = 'modalExerciseChart';
                const ctx = document.getElementById(canvasId).getContext('2d');
                 if (modalExerciseChart) {
                    modalExerciseChart.destroy();
                }
                if (data.success && data.grouped_history && data.grouped_history.length > 0) {
                    const labels = data.grouped_history.map(item => item.exercise_date);
                    const Sdata = data.grouped_history.map(item => item.total_duration);
                    modalExerciseChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{ label: '每日運動總時長 (分鐘)', data: Sdata, backgroundColor: 'rgba(255, 152, 0, 0.6)', borderColor: 'rgba(255, 152, 0, 1)', borderWidth: 1 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: '分鐘' }}, x: { title: { display: true, text: '日期' }}}}
                    });
                } else {
                    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                    ctx.font = "16px 'Noto Sans TC'"; ctx.textAlign = "center";
                    ctx.fillText((data.grouped_history && data.grouped_history.length === 0) ? "尚無運動圖表記錄可顯示。" : ("獲取圖表失敗: " + (data.message||"未知錯誤")), ctx.canvas.width / 2, ctx.canvas.height / 2);
                    modalExerciseChart = null;
                }
            }).catch(error => console.error('獲取運動圖表歷史錯誤:', error));
    }

    function fetchRecentExerciseList() {
        fetch('get_exercise_history.php')
            .then(response => response.json())
            .then(data => {
                exerciseListItems.innerHTML = '';
                if (data.success && data.detailed_history && data.detailed_history.length > 0) {
                    data.detailed_history.slice(0, 5).forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = `${formatDate(item.exercise_date)} - ${item.exercise_type}: ${item.duration_minutes} 分鐘 ${item.notes ? '(' + item.notes + ')' : ''}`;
                        exerciseListItems.appendChild(li);
                    });
                } else {
                    exerciseListItems.innerHTML = '<li>尚無運動記錄。</li>';
                }
            }).catch(error => console.error('獲取最近運動記錄錯誤:', error));
    }

    if(showExerciseHistoryButton) {
        showExerciseHistoryButton.addEventListener('click', function() {
            exerciseHistoryModal.style.display = "block";
            fetchExerciseHistoryAndDrawChart();
        });
    }

    // Generic modal close buttons
    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.dataset.modalId;
            if(modalId) {
                const modalToClose = document.getElementById(modalId);
                if(modalToClose) modalToClose.style.display = "none";
                if (modalId === 'waterHistoryModal' && modalWaterIntakeChart) { modalWaterIntakeChart.destroy(); modalWaterIntakeChart = null; }
                if (modalId === 'exerciseHistoryModal' && modalExerciseChart) { modalExerciseChart.destroy(); modalExerciseChart = null; }
            }
        });
    });

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
            if (event.target.id === 'waterHistoryModal' && modalWaterIntakeChart) { modalWaterIntakeChart.destroy(); modalWaterIntakeChart = null; }
            if (event.target.id === 'exerciseHistoryModal' && modalExerciseChart) { modalExerciseChart.destroy(); modalExerciseChart = null; }
        }
    }

    // Initial page load calls
    updateWaterSummary();
    if(mlToAddDisplay) mlToAddDisplay.textContent = cupSizeMl;
    fetchRecentExerciseList();

</script>
</body>
</html>