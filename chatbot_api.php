<?php
// 引入設定檔
require 'config.php';

// 取得 API 金鑰（從設定檔讀取）
$apiKey = OPENAI_API_KEY;

// 取得前端傳入的訊息
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo json_encode(['error' => '訊息不得為空']);
    exit;
}

// 發送 API 請求到 OpenAI
$apiUrl = "https://api.openai.com/v1/chat/completions";
$data = [
    "model" => "gpt-3.5-turbo", // 使用最新的模型
    "messages" => [
        ["role" => "system", "content" => "You are a helpful assistant for Health Tracker."],
        ["role" => "user", "content" => $userMessage]
    ],
    "max_tokens" => 100,
    "temperature" => 0.7
];

$headers = [
    "Content-Type: application/json",
    "Authorization: " . "Bearer " . $apiKey
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['error' => 'API 請求失敗']);
    exit;
}

// 確保回應格式正確
$responseData = json_decode($response, true);
$botMessage = $responseData['choices'][0]['message']['content'] ?? "抱歉，我無法回答您的問題。";

// 回應前端
echo json_encode(['message' => $botMessage]);
?>
