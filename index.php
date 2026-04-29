<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ココチャ・プロトタイプ</title>
    <style>
        body { 
            background: #f0f0f0; 
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            cursor: pointer; /* クリックできることを示す */
        }
        #chat-box { max-width: 500px; margin: 0 auto; }
        
        .message { 
            background: white; 
            padding: 15px; 
            border-radius: 15px; 
            margin-bottom: 15px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none; /* 最初は非表示 */
            line-height: 1.5;
        }

        /* 表示された時のスタイル */
        .active { 
            display: block; 
            animation: fadeIn 0.4s ease-out; 
        }

        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* 画面下部の案内 */
        .guide {
            text-align: center;
            color: #888;
            font-size: 0.9em;
            margin-top: 50px;
        }
    </style>
</head>
<body onclick="nextMessage()">

<div id="chat-box">
    <div class="message"><b>魔王:</b> お疲れ様です、魔王様。</div>
    <div class="message"><b>魔王:</b> 画面のタップに反応したようですね！</div>
    <div class="message"><b>魔王:</b> これが「ココチャ」の再生ロジックの基本になります。</div>
    <div class="message"><b>魔王:</b> 次は、キャラのアイコンを出したり、<br>PHPでデータを読み込んだりしてみましょうか。</div>
</div>

<div class="guide">画面のどこかをタップしてください</div>

<script>
    let currentIdx = 0;
    // メッセージ要素をすべて取得
    const messages = document.querySelectorAll('.message');
    
    // ページ読み込み時に要素がちゃんとあるか確認（コンソール用）
    console.log("読み込まれたメッセージ数:", messages.length);

    function nextMessage() {
        if (currentIdx < messages.length) {
            console.log(currentIdx + "番目のメッセージを表示します");
            messages[currentIdx].classList.add('active');
            currentIdx++;
            
            // 画面を一番下までスクロールさせる
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        } else {
            console.log("すべてのメッセージを表示しました");
        }
    }
</script>
</body>
</html>