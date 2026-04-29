<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ココチャ・プロトタイプ</title>
    <style>
        /* 簡単なココチャ風デザイン */
        body { background: #f0f0f0; font-family: sans-serif; }
        #chat-box { max-width: 400px; margin: 20px auto; display: flex; flex-direction: column; }
        .message { background: white; padding: 10px; border-radius: 10px; margin-bottom: 10px; display: none; }
        .active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
    </style>
</head>
<body onclick="nextMessage()">

<div id="chat-box">
    <div class="message">魔王様、お疲れ様です！</div>
    <div class="message">今日のノヴァ大陸は一段と星が綺麗ですね。</div>
    <div class="message">あ、またどこか遠くを見て……。</div>
    <div class="message">（タップで次へ進むプロトタイプです）</div>
</div>

<script>
    let currentIdx = 0;
    const messages = document.querySelectorAll('.message');

    function nextMessage() {
        if (currentIdx < messages.length) {
            messages[currentIdx].classList.add('active');
            currentIdx++;
            window.scrollTo(0, document.body.scrollHeight);
        }
    }
</script>
</body>
</html>