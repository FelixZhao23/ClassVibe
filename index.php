<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassVibe - リアルタイム授業</title>
    
    <!-- 1. ライブラリ読み込み -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body { background-color: #F3F4F6; font-family: 'Noto Sans JP', sans-serif; }
        .bubble::after { content: ''; position: absolute; bottom: -10px; left: 50%; border-width: 10px 10px 0; border-style: solid; border-color: white transparent; transform: translateX(-50%); }
        
        /* Animations */
        @keyframes bounce-fast { 0%, 100% { transform: translateY(0) scale(1.1); } 50% { transform: translateY(-10px) scale(1.1); } }
        @keyframes bounce-slow { 0%, 100% { transform: translateY(-5%); } 50% { transform: translateY(0); } }
        @keyframes shake-gentle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
        @keyframes shake-hard { 0% { transform: translate(1px, 1px) rotate(0deg); } 10% { transform: translate(-3px, -2px) rotate(-5deg); } 50% { transform: translate(-1px, 2px) rotate(-5deg); } 100% { transform: translate(1px, -2px) rotate(-5deg); } }
        @keyframes breath { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }
        @keyframes pulse-red { 0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } }
        @keyframes damage { 0% { transform: scale(1); filter: brightness(1); } 50% { transform: scale(0.9); filter: brightness(0.5) sepia(1) hue-rotate(-50deg) saturate(5); } 100% { transform: scale(1); filter: brightness(1); } }
        @keyframes pulse-glow { 0%, 100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); } 50% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.8); } }

        .animate-bounce-fast { animation: bounce-fast 0.5s infinite; }
        .animate-bounce-slow { animation: bounce-slow 2s infinite; }
        .animate-shake-gentle { animation: shake-gentle 1s infinite; }
        .animate-shake-hard { animation: shake-hard 0.5s infinite; }
        .animate-breath { animation: breath 4s infinite ease-in-out; }
        .animate-damage { animation: damage 0.2s ease-in-out; }
        .animate-pulse-glow { animation: pulse-glow 2s infinite; }
        
        /* Game UI */
        .boss-hp-bar-container { box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); }
        .shadow-text { text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden relative">

    <!-- 1. トップナビゲーション -->
    <header class="bg-white shadow-sm z-20 flex-none h-20">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            
            <!-- 左側：戻る & タイトル & 参加コード -->
            <div class="flex items-center gap-4">
                <a href="teacherbackground.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h1 class="text-xl font-bold text-gray-900 leading-tight" id="course-title">接続中...</h1>
                    </div>
                    <!-- ✨ 参加コード表示 -->
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">Code:</span>
                        <span class="text-2xl font-mono font-black text-blue-600 tracking-widest" id="join-code">----</span>
                    </div>
                </div>
            </div>
            
            <!-- 右側：ツールバー -->
            <div class="flex items-center gap-4">
                <!-- QR拡大 -->
                <div class="hidden md:flex flex-col items-center cursor-pointer group" onclick="toggleFullScreenQR()">
                    <div id="qrcode-mini" class="bg-white p-1 border rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                    <span class="text-[10px] text-gray-400 mt-1">拡大</span>
                </div>

                <!-- ✨ 参加人数 (active_students) -->
                <div class="flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 shadow-sm">
                    <i class="fas fa-users mr-2 text-lg"></i>
                    <div class="text-center leading-none">
                        <span class="text-xs font-normal text-blue-500 block">参加者数</span>
                        <span class="text-lg font-bold" id="active-student-count">0</span>
                    </div>
                </div>

                <!-- 🚫 授業終了ボタン -->
                <button onclick="stopClass()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg shadow font-bold flex items-center gap-2 transition-colors">
                    <i class="fas fa-stop-circle"></i>
                    授業終了
                </button>
            </div>
        </div>
    </header>

    <!-- 2. メインコンテンツ -->
    <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- 左カラム (4/12): キャラクター & ゲーム操作 -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                
                <!-- 🤖 Mochi-chan -->
                <div id="mascot-card" class="bg-white rounded-2xl shadow p-6 flex flex-col items-center justify-center relative min-h-[300px] transition-colors duration-500">
                    <div id="mascot-bubble" class="bubble bg-white px-6 py-3 rounded-2xl shadow-md text-gray-700 font-bold mb-8 text-center animate-bounce z-10">準備はいいですか？</div>
                    <div class="relative z-10 scale-125 md:scale-150 transform transition-transform">
                        <div id="mochi-body" class="w-40 h-32 bg-white rounded-[40%] border-[5px] border-slate-900 relative flex items-center justify-center shadow-2xl transition-all duration-300 animate-breath">
                            <div class="relative w-full h-full">
                                <div id="mochi-eyes"></div>
                                <div id="mochi-mouth" class="absolute left-1/2 transform -translate-x-1/2 border-slate-900 top-16 w-4 h-1 bg-slate-900 rounded-full"></div>
                                <div id="mochi-cheeks"><div class="absolute top-16 left-4 w-7 h-5 bg-pink-300 rounded-full opacity-60 blur-sm"></div><div class="absolute top-16 right-4 w-7 h-5 bg-pink-300 rounded-full opacity-60 blur-sm"></div></div>
                            </div>
                        </div>
                        <div class="w-32 h-4 bg-black/20 rounded-full blur-md mt-4 mx-auto"></div>
                    </div>
                    <div class="text-center mt-8"><span id="mascot-status-text" class="text-gray-400 font-bold text-lg">待機中...</span></div>
                </div>

                <!-- 🎮 クラスイベント -->
                <div class="bg-white p-5 rounded-2xl shadow border-l-4 border-indigo-500">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center text-lg">
                        <i class="fas fa-gamepad mr-2 text-indigo-500"></i> クラスアクティビティ
                    </h3>
                    <div class="space-y-3">
                        <div class="bg-gradient-to-r from-red-500 to-blue-500 text-white rounded-xl p-4">
                            <div class="font-bold">⚔️ 赤青対抗戦（常時進行）</div>
                            <div class="text-xs opacity-90 mt-1">授業開始から終了まで自動で累積します</div>
                        </div>

                        <!-- 🆕 3. RealReaction Mode -->
                        <button onclick="startRealReaction()" id="real-reaction-btn" class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-purple-600 to-pink-500 text-white rounded-xl shadow hover:opacity-90 transition transform hover:-translate-y-1">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">📊</span>
                                <div class="text-left">
                                    <div class="font-bold">リアルリアクション</div>
                                    <div class="text-xs opacity-90">1人1回のみ投票</div>
                                </div>
                            </div>
                            <i class="fas fa-play-circle text-2xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 右カラム (8/12): データモニタリング -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- 📊 6つのリアクションパネル -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- Positive -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-green-500"><div class="text-xs text-gray-400 font-bold uppercase">よくわかった</div><div class="text-3xl font-bold text-gray-800" id="val-happy">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-pink-500"><div class="text-xs text-gray-400 font-bold uppercase">ちょっとわからない</div><div class="text-3xl font-bold text-gray-800" id="val-amazing">0</div></div>
                    
                    <!-- Negative -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-yellow-500"><div class="text-xs text-gray-400 font-bold uppercase">むずかしい</div><div class="text-3xl font-bold text-gray-800" id="val-confused">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-blue-500"><div class="text-xs text-gray-400 font-bold uppercase">ぜんぜんわからない</div><div class="text-3xl font-bold text-gray-800" id="val-question">0</div></div>

                    <!-- Idle/Bored -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-gray-400 bg-gray-50"><div class="text-xs text-gray-500 font-bold uppercase">サボり中</div><div class="text-3xl font-bold text-gray-600" id="val-sleepy">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-gray-400 bg-gray-50"><div class="text-xs text-gray-500 font-bold uppercase">面倒</div><div class="text-3xl font-bold text-gray-600" id="val-bored">0</div></div>
                </div>

                <!-- ⚔️ 常時対戦 + ❤️ クラス体力 -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-bold text-gray-700 mb-3">⚔️ 赤青対抗戦（累積）</h3>
                            <div class="flex items-end justify-between mb-2">
                                <div class="text-red-500 font-black text-4xl" id="persist-score-red">0</div>
                                <div class="text-gray-400 text-sm font-bold">VS</div>
                                <div class="text-blue-500 font-black text-4xl" id="persist-score-blue">0</div>
                            </div>
                            <div class="relative w-full h-6 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                                <div id="persist-battle-red-bar" class="absolute left-0 top-0 h-full bg-gradient-to-r from-red-600 to-red-400" style="width:50%"></div>
                                <div id="persist-battle-blue-bar" class="absolute right-0 top-0 h-full bg-gradient-to-l from-blue-600 to-blue-400" style="width:50%"></div>
                                <div class="absolute inset-y-0 left-1/2 w-1 bg-white/70 blur-[0.5px]"></div>
                                <div class="absolute inset-y-0 left-1/2 w-0.5 bg-gray-300/80"></div>
                                <div id="persist-battle-spark" class="absolute top-1/2 -translate-y-1/2 left-1/2 -translate-x-1/2 w-5 h-5 rounded-full bg-gradient-to-br from-yellow-200 via-amber-400 to-orange-500 shadow-lg shadow-orange-400/50 animate-pulse"></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-700 mb-3">❤️ クラス集団HP</h3>
                            <div class="flex justify-between items-center mb-2">
                                <div class="text-2xl font-black text-emerald-600" id="class-hp-text">200 / 200</div>
                                <span id="class-hp-badge" class="text-xs font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">生存中</span>
                            </div>
                            <div class="relative w-full h-5 bg-gray-200 rounded-full overflow-hidden">
                                <div id="class-hp-bar" class="h-full bg-gradient-to-r from-emerald-500 to-lime-400 transition-all duration-300" style="width:100%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">どのボタンでもHP維持 / 無操作が続くとHP減少</p>
                        </div>
                    </div>
                </div>

                <!-- 📈 グラフエリア -->
                <div class="bg-white rounded-2xl shadow-lg p-6 flex-1 min-h-[350px]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-700 text-lg"><i class="fas fa-chart-line text-blue-500 mr-2"></i>クラスの熱量 (リアルタイム)</h3>
                        <div class="flex gap-4 text-xs font-bold">
                            <div class="flex items-center gap-1"><span class="w-3 h-3 bg-green-400 rounded-full"></span> ポジティブ</div>
                            <div class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-400 rounded-full"></span> ネガティブ</div>
                        </div>
                    </div>
                    <div class="relative w-full h-[300px]">
                        <canvas id="reactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    

    <!-- 🆕 RealReaction Modal -->
    <div id="real-reaction-modal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl mx-4 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-500 px-8 py-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-black flex items-center gap-3">
                            <span class="animate-pulse-glow">📊</span> リアルリアクション
                        </h2>
                        <p class="text-purple-100 text-sm mt-1">学生は1人1回のみ投票できます</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm opacity-80">実施時間</div>
                        <div class="text-2xl font-mono font-bold" id="rr-timer">00:00</div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- 参加状況 -->
                <div class="mb-6 bg-blue-50 border-2 border-blue-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white text-xl">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="text-sm text-blue-600 font-bold">投票状況</div>
                            <div class="text-2xl font-black text-blue-900">
                                <span id="rr-voted-count">0</span> / <span id="rr-total-count">0</span> 人
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-black text-blue-600" id="rr-percentage">0%</div>
                        <div class="text-xs text-blue-500">参加率</div>
                    </div>
                </div>

                <!-- リアクション集計 -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-smile text-green-500 text-2xl"></i>
                                <span class="font-bold text-green-700">よくわかった</span>
                            </div>
                            <span class="text-3xl font-black text-green-600" id="rr-val-happy">0</span>
                        </div>
                    </div>

                    <div class="bg-pink-50 border-2 border-pink-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-star text-pink-500 text-2xl"></i>
                                <span class="font-bold text-pink-700">ちょっとわからない</span>
                            </div>
                            <span class="text-3xl font-black text-pink-600" id="rr-val-amazing">0</span>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-dizzy text-yellow-500 text-2xl"></i>
                                <span class="font-bold text-yellow-700">難しい</span>
                            </div>
                            <span class="text-3xl font-black text-yellow-600" id="rr-val-confused">0</span>
                        </div>
                    </div>

                    <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-hand-paper text-blue-500 text-2xl"></i>
                                <span class="font-bold text-blue-700">ぜんぜんわからない</span>
                            </div>
                            <span class="text-3xl font-black text-blue-600" id="rr-val-question">0</span>
                        </div>
                    </div>

                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed text-indigo-400 text-2xl"></i>
                                <span class="font-bold text-indigo-600">サボり中</span>
                            </div>
                            <span class="text-3xl font-black text-indigo-500" id="rr-val-sleepy">0</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-meh-blank text-gray-400 text-2xl"></i>
                                <span class="font-bold text-gray-600">面倒</span>
                            </div>
                            <span class="text-3xl font-black text-gray-500" id="rr-val-bored">0</span>
                        </div>
                    </div>
                </div>

                <!-- 停止ボタン -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button onclick="stopRealReaction()" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-black text-xl py-4 rounded-xl shadow-lg transition-all transform hover:scale-105 flex items-center justify-center gap-3">
                        <i class="fas fa-stop-circle text-2xl"></i>
                        投票を終了して履歴に保存
                    </button>
                    <button onclick="abortRealReaction()" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-black text-xl py-4 rounded-xl shadow transition-all flex items-center justify-center gap-3">
                        <i class="fas fa-ban text-xl"></i>
                        この投票を破棄
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div id="qr-modal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center backdrop-blur-sm" onclick="toggleFullScreenQR()">
        <div class="bg-white p-10 rounded-3xl text-center shadow-2xl transform scale-110" onclick="event.stopPropagation()">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">QRコードで参加</h2>
            <p class="text-gray-500 mb-6">参加コード: <span class="text-blue-600 font-mono font-bold text-xl" id="modal-code">----</span></p>
            <div class="flex justify-center bg-white p-2 rounded-xl border border-gray-200"><div id="qrcode-large"></div></div>
            <p class="text-sm text-gray-400 mt-8 cursor-pointer hover:text-gray-600" onclick="toggleFullScreenQR()">閉じる</p>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // 1. Firebase Config
        const firebaseConfig = {
            apiKey: "AIzaSyA-xTpcCeCzQpa1sOjgC6EFMPvAvQeX5jg",
            authDomain: "classvibe-2025.firebaseapp.com",
            databaseURL: "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "classvibe-2025",
            storageBucket: "classvibe-2025.firebasestorage.app",
            messagingSenderId: "1002148479668",
            appId: "1:1002148479668:web:58f81221c565df8459cde1"
        };
        firebase.initializeApp(firebaseConfig);
        const db = firebase.database();

        const urlParams = new URLSearchParams(window.location.search);
        const COURSE_ID = urlParams.get('courseId');
        if (!COURSE_ID) { alert("ID Error"); window.location.href = "teacherbackground.php"; }

        // Chart Setup
        const ctx = document.getElementById('reactionChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Positive', data: [], borderColor: '#34D399', backgroundColor: 'rgba(52,211,153,0.1)', fill: true, tension: 0.4 },
                { label: 'Negative', data: [], borderColor: '#FBBF24', backgroundColor: 'rgba(251,191,36,0.1)', fill: true, tension: 0.4 }
            ]},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: { beginAtZero: true, max: 100 }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Data Variables
        const courseRef = db.ref('courses/' + COURSE_ID);
        let curReacts = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
        let studentCount = 0;
        let prevReacts = null;
        let heatPositive = 0;
        let heatNegative = 0;
        let battleState = { active: true, red: 0, blue: 0 };
        let classHpState = { max: 200, current: 200, alive: true };
        let lastInteractionTs = Date.now();
        let hpDecayTimer = null;
        
        let sessionStartTime = null;
        let sessionTopic = '';
        let courseInfo = null;
        let classActivated = false;

        // 🆕 RealReaction Variables
        let realReactionActive = false;
        let realReactionStartTime = null;
        let realReactionTimer = null;
        let realReactionData = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
        let votedStudents = new Set(); // 已投票的学生ID集合

        // 2. Firebase Listener
        courseRef.on('value', (snapshot) => {
            const data = snapshot.val();
            if (data) {
                courseInfo = data;
                if (!classActivated) {
                    classActivated = true;
                    db.ref(`courses/${COURSE_ID}/is_active`).set(true);
                }
                
                document.getElementById('course-title').innerText = data.title;
                const code = data.simple_code || "----";
                document.getElementById('join-code').innerText = code;
                document.getElementById('modal-code').innerText = code;
                generateQR(code);
                
                if (!sessionStartTime) {
                    sessionStartTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                }
                
                const active = data.active_students || {};
                const members = data.members || {};
                const activeCount = Object.keys(active).length;
                const membersCount = Object.keys(members).length;
                studentCount = Math.max(activeCount, membersCount);
                document.getElementById('active-student-count').innerText = studentCount;

                // 🆕 更新RealReaction参加人数
                if (realReactionActive) {
                    document.getElementById('rr-total-count').innerText = studentCount;
                }

                const r = data.reactions || {};
                curReacts = { 
                    happy: r.happy||0, amazing: r.amazing||0, 
                    confused: r.confused||0, question: r.question||0,
                    sleepy: r.sleepy||0, bored: r.bored||0 
                };
                updateDashboard();
                hydratePersistentState(data);
                applyReactionEffects(curReacts, data.student_metrics || {});

                // 🆕 监听 RealReaction 状态（支持刷新后自动恢复）
                if (data.real_reaction && data.real_reaction.active) {
                    updateRealReactionUI(data.real_reaction);
                } else if (realReactionActive) {
                    closeRealReactionUI();
                }
            }
        });
        startHpDecayLoop();

        function updateDashboard() {
            ['happy','amazing','confused','question','sleepy','bored'].forEach(k => {
                const el = document.getElementById('val-'+k);
                if(el) el.innerText = curReacts[k];
            });
            updateMascotState();
        }

        function hydratePersistentState(data) {
            const dbBattle = data.battle_persistent || null;
            const dbHp = data.class_hp || null;

            if (dbBattle) {
                battleState = {
                    active: true,
                    red: dbBattle.red || 0,
                    blue: dbBattle.blue || 0
                };
            } else {
                db.ref(`courses/${COURSE_ID}/battle_persistent`).set(battleState);
            }

            if (dbHp) {
                const max = dbHp.max || 200;
                const current = Math.max(0, Math.min(max, dbHp.current ?? max));
                classHpState = {
                    max: max,
                    current: current,
                    alive: current > 0
                };
            } else {
                db.ref(`courses/${COURSE_ID}/class_hp`).set(classHpState);
            }

            updatePersistentPanels();
        }

        function applyReactionEffects(currentReacts, metrics) {
            if (!prevReacts) {
                prevReacts = { ...currentReacts };
                return;
            }

            const delta = {};
            ['happy','amazing','confused','question','sleepy','bored'].forEach(k => {
                delta[k] = Math.max(0, (currentReacts[k] || 0) - (prevReacts[k] || 0));
            });

            const totalNew = Object.values(delta).reduce((a, b) => a + b, 0);
            if (totalNew === 0) return;
            lastInteractionTs = Date.now();

            const positiveGain = delta.happy + delta.amazing + delta.confused;
            const negativeGain = delta.question + delta.sleepy + delta.bored;
            heatPositive = Math.min(100, heatPositive + (positiveGain * 8));
            heatNegative = Math.min(100, heatNegative + (negativeGain * 8));

            const teamTotals = { red: 0, blue: 0 };
            if (metrics && typeof metrics === 'object') {
                Object.values(metrics).forEach(m => {
                    if (!m) return;
                    const team = m.team || '';
                    const contrib = Number(m.team_contribution || 0);
                    if (team === 'red') teamTotals.red += contrib;
                    if (team === 'blue') teamTotals.blue += contrib;
                });
            }
            battleState.red = teamTotals.red;
            battleState.blue = teamTotals.blue;

            // 互动导向：任何按钮都能为集体HP续命
            const hpDelta = totalNew;

            if (classHpState.current > 0) {
                classHpState.current = Math.max(0, Math.min(classHpState.max, classHpState.current + hpDelta));
            }
            classHpState.alive = classHpState.current > 0;

            updatePersistentPanels();

            const updates = {};
            updates[`courses/${COURSE_ID}/battle_persistent`] = {
                active: true,
                red: Math.round(battleState.red),
                blue: Math.round(battleState.blue)
            };
            updates[`courses/${COURSE_ID}/class_hp`] = {
                max: classHpState.max,
                current: classHpState.current,
                alive: classHpState.alive
            };
            db.ref().update(updates);

            prevReacts = { ...currentReacts };
        }

        function startHpDecayLoop() {
            if (hpDecayTimer) clearInterval(hpDecayTimer);
            hpDecayTimer = setInterval(() => {
                if (classHpState.current <= 0) return;
                const idleMs = Date.now() - lastInteractionTs;
                if (idleMs < 15000) return; // 15秒内有互动就不掉血

                classHpState.current = Math.max(0, classHpState.current - 1);
                classHpState.alive = classHpState.current > 0;
                updatePersistentPanels();
                db.ref(`courses/${COURSE_ID}/class_hp`).set({
                    max: classHpState.max,
                    current: classHpState.current,
                    alive: classHpState.alive
                });
            }, 10000);
        }

        function updatePersistentPanels() {
            const red = Math.round(battleState.red || 0);
            const blue = Math.round(battleState.blue || 0);
            document.getElementById('persist-score-red').innerText = red;
            document.getElementById('persist-score-blue').innerText = blue;

            const total = red + blue;
            const redPercent = total === 0 ? 50 : Math.round((red / total) * 100);
            const bluePercent = 100 - redPercent;
            document.getElementById('persist-battle-red-bar').style.width = `${redPercent}%`;
            document.getElementById('persist-battle-blue-bar').style.width = `${bluePercent}%`;
            document.getElementById('persist-battle-spark').style.left = `${redPercent}%`;

            const hpMax = classHpState.max || 200;
            const hpCur = Math.max(0, classHpState.current || 0);
            const hpPercent = Math.round((hpCur / hpMax) * 100);
            document.getElementById('class-hp-text').innerText = `${hpCur} / ${hpMax}`;
            document.getElementById('class-hp-bar').style.width = `${hpPercent}%`;

            const badge = document.getElementById('class-hp-badge');
            if (hpCur <= 0) {
                badge.innerText = '全滅';
                badge.className = 'text-xs font-bold px-2 py-1 rounded-full bg-red-100 text-red-700';
                document.getElementById('class-hp-bar').className = 'h-full bg-gradient-to-r from-red-500 to-red-400 transition-all duration-300';
            } else if (hpPercent <= 30) {
                badge.innerText = '危険';
                badge.className = 'text-xs font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700';
                document.getElementById('class-hp-bar').className = 'h-full bg-gradient-to-r from-amber-500 to-orange-400 transition-all duration-300';
            } else {
                badge.innerText = '生存中';
                badge.className = 'text-xs font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700';
                document.getElementById('class-hp-bar').className = 'h-full bg-gradient-to-r from-emerald-500 to-lime-400 transition-all duration-300';
            }
        }

        // ==========================================
        // 🆕 RealReaction Logic
        // ==========================================
        function startRealReaction() {
            if (realReactionActive) {
                alert("リアルリアクションは既に実施中です。");
                return;
            }

            if (studentCount === 0) {
                alert("参加している学生がいません。");
                return;
            }

            // 初始化
            realReactionActive = true;
            realReactionStartTime = Date.now();
            realReactionData = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
            votedStudents = new Set();

            // Firebase に状態を保存
            db.ref(`courses/${COURSE_ID}/real_reaction`).set({
                active: true,
                start_time: realReactionStartTime,
                reactions: realReactionData,
                voted_students: {}
            });

            // UI表示
            document.getElementById('real-reaction-modal').classList.remove('hidden');
            document.getElementById('rr-total-count').innerText = studentCount;
            updateRealReactionDisplay();

            // 计时器
            startRealReactionTimer();
        }

        function startRealReactionTimer() {
            if (realReactionTimer) clearInterval(realReactionTimer);
            if (!realReactionStartTime) return;
            
            realReactionTimer = setInterval(() => {
                const elapsed = Math.floor((Date.now() - realReactionStartTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('rr-timer').innerText = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }

        function updateRealReactionUI(rrData) {
            if (!rrData || !rrData.active) return;

            realReactionActive = true;
            realReactionStartTime = rrData.start_time || Date.now();

            const reactions = rrData.reactions || {};
            realReactionData = {
                happy: reactions.happy || 0,
                amazing: reactions.amazing || 0,
                confused: reactions.confused || 0,
                question: reactions.question || 0,
                sleepy: reactions.sleepy || 0,
                bored: reactions.bored || 0
            };

            // 更新已投票学生列表
            const votedList = rrData.voted_students || {};
            votedStudents = new Set(Object.keys(votedList));

            document.getElementById('real-reaction-modal').classList.remove('hidden');
            document.getElementById('rr-total-count').innerText = studentCount;
            updateRealReactionDisplay();
            startRealReactionTimer();
        }

        function updateRealReactionDisplay() {
            // 更新各个反应数值
            ['happy', 'amazing', 'confused', 'question', 'sleepy', 'bored'].forEach(key => {
                const el = document.getElementById('rr-val-' + key);
                if (el) el.innerText = realReactionData[key];
            });

            // 更新投票人数和百分比
            const sumFromReactions = Object.values(realReactionData).reduce((a, b) => a + (Number(b) || 0), 0);
            const votedCount = Math.max(votedStudents.size, sumFromReactions);
            document.getElementById('rr-voted-count').innerText = votedCount;
            
            const percentage = studentCount > 0 ? Math.round((votedCount / studentCount) * 100) : 0;
            document.getElementById('rr-percentage').innerText = percentage + '%';
        }

        async function stopRealReaction() {
            if (!realReactionActive) return;

            const endTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
            const duration = Math.floor((Date.now() - realReactionStartTime) / 1000);

            // 保存到历史记录
            const sumFromReactions = Object.values(realReactionData).reduce((a, b) => a + (Number(b) || 0), 0);
            const finalVotedCount = Math.max(votedStudents.size, sumFromReactions);

            const rrSessionData = {
                class_id: COURSE_ID,
                class_name: courseInfo?.title || '未設定',
                topic: '📊 リアルリアクション投票',
                start_time: new Date(realReactionStartTime).toISOString().slice(0, 19).replace('T', ' '),
                end_time: endTime,
                duration: duration,
                student_count: studentCount,
                voted_count: finalVotedCount,
                participation_rate: studentCount > 0 ? Math.round((finalVotedCount / studentCount) * 100) : 0,
                reactions: { ...realReactionData },
                type: 'real_reaction' // 标记为 RealReaction 类型
            };

            try {
                await db.ref('class_sessions').push(rrSessionData);
                console.log("✅ リアルリアクションデータを保存しました:", rrSessionData);

                // 清理Firebase状态
                await db.ref(`courses/${COURSE_ID}/real_reaction`).remove();

                // 重置本地状态
                realReactionActive = false;
                if (realReactionTimer) {
                    clearInterval(realReactionTimer);
                    realReactionTimer = null;
                }

                // 关闭弹窗
                document.getElementById('real-reaction-modal').classList.add('hidden');

                alert(`✅ 投票を終了しました！\n\n参加率: ${rrSessionData.participation_rate}%\n投票数: ${finalVotedCount}/${studentCount}人\n\nデータは履歴に保存されました。`);

            } catch (err) {
                console.error("❌ 保存失败:", err);
                alert("保存に失敗しました: " + err.message);
            }
        }

        async function abortRealReaction() {
            if (!realReactionActive) return;
            if (!confirm("このリアルリアクションを破棄しますか？\n今回の投票データは保存されません。")) return;

            try {
                await db.ref(`courses/${COURSE_ID}/real_reaction`).remove();
                closeRealReactionUI();
                alert("投票を破棄しました。");
            } catch (err) {
                console.error("❌ 破棄失败:", err);
                alert("破棄に失敗しました: " + err.message);
            }
        }

        function closeRealReactionUI() {
            realReactionActive = false;
            realReactionStartTime = null;
            realReactionData = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
            votedStudents = new Set();
            if (realReactionTimer) {
                clearInterval(realReactionTimer);
                realReactionTimer = null;
            }
            document.getElementById('real-reaction-modal').classList.add('hidden');
        }

        function toSafeNumber(value) {
            const n = Number(value || 0);
            return Number.isFinite(n) ? n : 0;
        }

        function hashTeam(uid) {
            const text = String(uid || '');
            let sum = 0;
            for (let i = 0; i < text.length; i++) sum += text.charCodeAt(i);
            return (sum % 2 === 0) ? 'red' : 'blue';
        }

        function calcLevel(expTotal) {
            let level = 1;
            let remaining = Math.max(0, Number(expTotal || 0));
            let need = 120;
            while (remaining >= need) {
                remaining -= need;
                level += 1;
                need = 120 + ((level - 1) * 20);
            }
            return level;
        }

        function computeTitle(dims, level) {
            const entries = Object.entries(dims || {});
            if (entries.length === 0) return 'はじめの一歩';
            entries.sort((a, b) => (b[1] || 0) - (a[1] || 0));
            const primary = entries[0]?.[0] || 'engagement';
            const titles = {
                understand: ['理解の見習い', '解法トラベラー', '知識クラフター', '思考ナビゲーター', '真理トラッカー'],
                question: ['質問の見習い', 'ヒントハンター', '対話イグナイター', '洞察チェイサー', 'ソクラテスの眼'],
                collab: ['協力の見習い', '紅青コーディネーター', 'チームエンジン', '共創キャプテン', 'クラス連結コア'],
                engagement: ['参加の見習い', 'インタラクション加速者', 'ムード点火師', '授業プッシャー', '熱量スター'],
                stability: ['安定の見習い', 'リズムウォッチャー', '秩序リペアラー', 'クラス守護バリア', '不動のガーディアン']
            };
            const tier = Math.min(4, Math.max(0, (level || 1) - 1));
            return (titles[primary] && titles[primary][tier]) ? titles[primary][tier] : 'はじめの一歩';
        }

        const MESSAGE_POOL = {
            high_engagement: [
                { id: "high_engagement_01", text: "今日の一歩が、明日の自信になる。" },
                { id: "high_engagement_02", text: "教室の空気は、君の手で温かくなる。" },
                { id: "high_engagement_03", text: "小さな参加が、大きな流れを作る。" },
                { id: "high_engagement_04", text: "動いた分だけ、学びは前へ進む。" },
                { id: "high_engagement_05", text: "熱量は、誰かの勇気にもなる。" },
                { id: "high_engagement_06", text: "一回の反応が、集中を生む。" },
                { id: "high_engagement_07", text: "今日の活発さは、明日の成長の土台。" },
                { id: "high_engagement_08", text: "参加は小さな挑戦、挑戦は大きな力。" },
                { id: "high_engagement_09", text: "今の勢いが、理解の扉を開く。" },
                { id: "high_engagement_10", text: "行動が先、結果は後。よく動いた。" }
            ],
            low_engagement: [
                { id: "low_engagement_01", text: "静かな時間も、次の一歩の準備。" },
                { id: "low_engagement_02", text: "迷ったら、小さく反応してみよう。" },
                { id: "low_engagement_03", text: "一度の参加で、空気が変わることもある。" },
                { id: "low_engagement_04", text: "止まってもいい、また動けばいい。" },
                { id: "low_engagement_05", text: "小さな合図が、集中のスイッチになる。" },
                { id: "low_engagement_06", text: "焦らなくて大丈夫。まず一回。" },
                { id: "low_engagement_07", text: "沈黙は敵じゃない。次の声を待っている。" },
                { id: "low_engagement_08", text: "迷いは成長の入口。小さく試そう。" },
                { id: "low_engagement_09", text: "一歩目は短くていい。踏み出せば進む。" },
                { id: "low_engagement_10", text: "今日の一回が、明日の習慣になる。" }
            ],
            high_understand: [
                { id: "high_understand_01", text: "理解が深いほど、道はクリアになる。" },
                { id: "high_understand_02", text: "今日の理解は、明日の応用に変わる。" },
                { id: "high_understand_03", text: "腑に落ちる感覚を大事にしよう。" },
                { id: "high_understand_04", text: "分かった瞬間は、次の学びの鍵。" },
                { id: "high_understand_05", text: "理解は静かな勝利だ。" },
                { id: "high_understand_06", text: "仕組みが見えたら、怖くない。" },
                { id: "high_understand_07", text: "理解の積み重ねは、強い土台になる。" },
                { id: "high_understand_08", text: "本質に近づくほど、迷いは減る。" },
                { id: "high_understand_09", text: "理解は「できる」への橋。" },
                { id: "high_understand_10", text: "今日はよく噛み砕けた。いい流れ。" }
            ],
            low_understand: [
                { id: "low_understand_01", text: "わからないは、伸びしろの合図。" },
                { id: "low_understand_02", text: "理解が揺れる日も、学びは進んでいる。" },
                { id: "low_understand_03", text: "一つずつ分解すれば、道は見える。" },
                { id: "low_understand_04", text: "今は霧でも、少しずつ晴れる。" },
                { id: "low_understand_05", text: "迷いは成長の証。立ち止まってOK。" },
                { id: "low_understand_06", text: "難しいと感じたら、困惑の合図が光になる。" },
                { id: "low_understand_07", text: "ゆっくりでいい、確実に前へ。" },
                { id: "low_understand_08", text: "わからないを言える強さがある。" },
                { id: "low_understand_09", text: "今日の混乱は、明日の理解へ繋がる。" },
                { id: "low_understand_10", text: "難所に出会えたのは、成長の前兆。" }
            ],
            high_confusion: [
                { id: "high_confusion_01", text: "困惑は、理解に向かうサイン。" },
                { id: "high_confusion_02", text: "迷いがあるほど、次の成長は深い。" },
                { id: "high_confusion_03", text: "引っかかりは、大事な気づきの入口。" },
                { id: "high_confusion_04", text: "迷いを出せるのは前進の証。" },
                { id: "high_confusion_05", text: "わからないが見えるほど、理解は近い。" },
                { id: "high_confusion_06", text: "違和感を拾える人は強い。" },
                { id: "high_confusion_07", text: "困惑は、学びの方向修正だ。" },
                { id: "high_confusion_08", text: "迷った分だけ、次は確かになる。" },
                { id: "high_confusion_09", text: "引っかかりを放置しない姿勢が素敵。" },
                { id: "high_confusion_10", text: "困惑を言葉にできた時、理解が進む。" }
            ],
            stable: [
                { id: "stable_01", text: "安定した歩みが、一番遠くへ行く。" },
                { id: "stable_02", text: "落ち着きは、理解の味方。" },
                { id: "stable_03", text: "継続は静かな才能だ。" },
                { id: "stable_04", text: "一定のペースが学びを育てる。" },
                { id: "stable_05", text: "焦らず進む人が、最後に強い。" },
                { id: "stable_06", text: "毎回少しずつ。それが最短ルート。" },
                { id: "stable_07", text: "安定は、信頼を積み上げる。" },
                { id: "stable_08", text: "揺れない姿勢が、周りを安心させる。" },
                { id: "stable_09", text: "地道さは、未来を裏切らない。" },
                { id: "stable_10", text: "今日のペースが、明日の力になる。" }
            ]
        };

        function decideMessageCategory(metric) {
            const effective = toSafeNumber(metric.effective_interactions);
            const understood = toSafeNumber(metric.understood_count);
            const question = toSafeNumber(metric.question_count);
            const confused = toSafeNumber(metric.confused_count);
            if (understood >= 3 && effective >= 6) return "high_understand";
            if (question >= 2) return "high_confusion";
            if (effective >= 8) return "high_engagement";
            if (confused >= 2) return "low_understand";
            if (effective <= 1) return "low_engagement";
            return "stable";
        }

        function pickMessage(category, history) {
            const pool = MESSAGE_POOL[category] || [];
            const used = new Set(Object.keys(history || {}));
            let available = pool.filter(item => !used.has(item.id));
            let nextHistory = { ...(history || {}) };
            if (available.length === 0 && pool.length > 0) {
                // reset used in this category
                pool.forEach(item => { delete nextHistory[item.id]; });
                available = pool.slice();
            }
            if (available.length === 0) {
                return { id: "fallback", text: "今日の学びが、明日の自信になる。", history: nextHistory };
            }
            const picked = available[0];
            nextHistory[picked.id] = true;
            return { id: picked.id, text: picked.text, history: nextHistory };
        }

        function parseLocalDateTime(value) {
            if (!value) return null;
            const parts = String(value).replace('T', ' ').split(' ');
            const datePart = parts[0] || '';
            const timePart = parts[1] || '00:00:00';
            const [y, m, d] = datePart.split('-').map(v => parseInt(v, 10));
            const [hh, mm, ss] = timePart.split(':').map(v => parseInt(v, 10));
            if (!y || !m || !d) return null;
            return new Date(y, (m - 1), d, hh || 0, mm || 0, ss || 0);
        }

        function formatDateWithWeekday(dateObj) {
            if (!dateObj) return '';
            const days = ['日', '月', '火', '水', '木', '金', '土'];
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');
            const w = days[dateObj.getDay()];
            return `${y}/${m}/${d}(${w})`;
        }

        function toMinutes(dateObj) {
            if (!dateObj) return 0;
            return dateObj.getHours() * 60 + dateObj.getMinutes();
        }

        function getPeriods(startMin, endMin) {
            const slots = [
                { label: '1限', start: 9 * 60 + 20, end: 10 * 60 + 50 },
                { label: '2限', start: 11 * 60 + 0, end: 12 * 60 + 30 },
                { label: '3限', start: 13 * 60 + 20, end: 14 * 60 + 50 },
                { label: '4限', start: 15 * 60 + 0, end: 16 * 60 + 30 },
                { label: '5限', start: 16 * 60 + 40, end: 18 * 60 + 10 },
                { label: '6限', start: 18 * 60 + 20, end: 19 * 60 + 50 }
            ];
            const s = Math.max(0, startMin);
            const e = Math.max(s, endMin);
            const result = [];
            for (const slot of slots) {
                const overlap = Math.min(e, slot.end) - Math.max(s, slot.start);
                if (overlap > 0) result.push(slot.label);
            }
            return result.length ? result.join('・') : '時間外';
        }

        function buildLogSummary(startTime, endTime, metric, subjectText) {
            const start = parseLocalDateTime(startTime);
            const dateStr = formatDateWithWeekday(start);
            const effective = toSafeNumber(metric.effective_interactions).toFixed(1);
            const question = toSafeNumber(metric.question_count);
            const subject = subjectText ? ` ${subjectText}` : '';
            return `${dateStr}${subject} / 有効反応 ${effective} / 困惑 ${question}回`;
        }

        function calculateGrowthAward(metric, battleWinner, hpResult) {
            const effective = toSafeNumber(metric.effective_interactions);
            const understood = toSafeNumber(metric.understood_count);
            const question = toSafeNumber(metric.question_count);
            const teamContribution = toSafeNumber(metric.team_contribution);
            const team = metric.team || hashTeam(metric.uid || '');

            let exp = 0;
            if (effective >= 8) exp += 5;
            if (battleWinner !== 'draw' && team === battleWinner) exp += 8;
            if (hpResult === 'survived') exp += 10;
            if (question >= 2) exp += 3;
            exp = Math.min(30, exp);

            const gains = {
                understand: Math.floor(understood / 3),
                question: Math.floor(question / 2),
                collab: Math.floor(teamContribution / 5),
                engagement: Math.floor(effective / 4),
                stability: effective >= 8 ? 1 : 0
            };

            return { exp, gains };
        }

        async function applyGrowthSettlement(sessionId, battleWinner, hpResult, sessionStart, sessionEnd) {
            const metricsSnap = await db.ref(`courses/${COURSE_ID}/student_metrics`).once('value');
            const metrics = metricsSnap.val() || {};
            const entries = Object.entries(metrics);
            if (entries.length === 0) return { count: 0, totalExp: 0 };

            let totalExp = 0;
            await Promise.all(entries.map(async ([uid, rawMetric]) => {
                const metric = { ...(rawMetric || {}), uid };
                const award = calculateGrowthAward(metric, battleWinner, hpResult);
                totalExp += award.exp;

                const growthRef = db.ref(`users/${uid}/growth`);
                const logRef = db.ref(`users/${uid}/growth_logs/${sessionId}`);

                const category = decideMessageCategory(metric);
                let messageText = "";
                let messageId = "";
                await growthRef.transaction(current => {
                    const base = current || {};
                    const dims = base.dims || {
                        understand: 0,
                        question: 0,
                        collab: 0,
                        engagement: 0,
                        stability: 0
                    };
                    const nextDims = {
                        understand: toSafeNumber(dims.understand) + award.gains.understand,
                        question: toSafeNumber(dims.question) + award.gains.question,
                        collab: toSafeNumber(dims.collab) + award.gains.collab,
                        engagement: toSafeNumber(dims.engagement) + award.gains.engagement,
                        stability: toSafeNumber(dims.stability) + award.gains.stability
                    };
                    const nextExpTotal = toSafeNumber(base.exp_total) + award.exp;
                    const nextLevel = calcLevel(nextExpTotal);
                    const nextTitle = computeTitle(nextDims, nextLevel);
                    const history = Array.isArray(base.title_history) ? [...base.title_history] : [];
                    if (nextTitle && history[history.length - 1] !== nextTitle) history.push(nextTitle);
                    if (history.length > 20) history.splice(0, history.length - 20);
                    const messagePick = pickMessage(category, base.message_history || {});
                    messageText = messagePick.text;
                    messageId = messagePick.id;

                    return {
                        exp_total: nextExpTotal,
                        dims: nextDims,
                        title_current: nextTitle,
                        title_history: history,
                        message_history: messagePick.history,
                        last_message: messageText,
                        last_message_id: messageId,
                        last_message_category: category,
                        updated_at: Date.now()
                    };
                }).then(result => {
                    if (result && result.snapshot) {
                        const latest = result.snapshot.val() || {};
                        messageText = latest.last_message || messageText;
                        messageId = latest.last_message_id || messageId;
                    }
                });

                const subjectText = courseInfo?.title || sessionTopic || '通常授業';
                const logSummary = buildLogSummary(sessionStart, sessionEnd, metric, subjectText);
                await logRef.set({
                    created_at: Date.now(),
                    exp_gain: award.exp,
                    gains: award.gains,
                    summary: logSummary,
                    next_hint: "次回は困惑の合図を1回増やして称号を強化しよう",
                    message: messageText,
                    message_id: messageId,
                    message_category: category
                });
            }));

            return { count: entries.length, totalExp };
        }

        // ==========================================
        // 🚫 End Class Logic
        // ==========================================
        async function stopClass() {
            if(!confirm("授業を終了しますか？\n現在のデータは履歴に保存され、画面はリセットされます。")) return;

            // RealReaction 実施中なら先に独立履歴として保存
            if (realReactionActive) {
                const shouldSaveRR = confirm("リアルリアクションが進行中です。\n先に投票データを独立履歴として保存しますか？");
                if (!shouldSaveRR) return;
                await stopRealReaction();
            }

            const endTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
            const battleWinner = battleState.red === battleState.blue
                ? 'draw'
                : (battleState.red > battleState.blue ? 'red' : 'blue');
            const hpResult = classHpState.current <= 0 ? 'failed' : 'survived';
            const rewardPoints = hpResult === 'survived' ? 10 : 0;
            const penaltyPoints = hpResult === 'failed' ? 5 : 0;
            
            const sessionData = {
                class_id: COURSE_ID,
                class_name: courseInfo?.title || '未設定',
                topic: sessionTopic || courseInfo?.title || '通常授業',
                start_time: sessionStartTime,
                end_time: endTime,
                student_count: studentCount,
                reactions: {
                    happy: curReacts.happy,
                    amazing: curReacts.amazing,
                    confused: curReacts.confused,
                    question: curReacts.question,
                    sleepy: curReacts.sleepy,
                    bored: curReacts.bored
                },
                battle_result: {
                    red: battleState.red || 0,
                    blue: battleState.blue || 0,
                    winner: battleWinner
                },
                class_hp_result: {
                    max: classHpState.max || 200,
                    remaining: classHpState.current || 0,
                    result: hpResult
                },
                settlement: {
                    reward_points: rewardPoints,
                    penalty_points: penaltyPoints
                },
                type: 'normal' // 标记为普通课程
            };

            try {
                const sessionRef = db.ref('class_sessions').push();
                await sessionRef.set(sessionData);
                const settlementResult = await applyGrowthSettlement(sessionRef.key, battleWinner, hpResult, sessionStartTime, endTime);
                
                db.ref(`courses/${COURSE_ID}/reactions`).set({ 
                    happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 
                });
                db.ref(`courses/${COURSE_ID}/battle_persistent`).set({ active:true, red:0, blue:0 });
                db.ref(`courses/${COURSE_ID}/class_hp`).set({ max:200, current:200, alive:true });
                db.ref(`courses/${COURSE_ID}/real_reaction`).remove();
                db.ref(`courses/${COURSE_ID}/student_metrics`).remove();
                db.ref(`courses/${COURSE_ID}/active_students`).remove(); 
                db.ref(`courses/${COURSE_ID}/is_active`).set(false);
                db.ref(`courses/${COURSE_ID}/game`).remove();
                if (hpDecayTimer) {
                    clearInterval(hpDecayTimer);
                    hpDecayTimer = null;
                }

                chart.data.datasets.forEach(d => d.data = []);
                chart.update();
                
                sessionStartTime = null;
                
                const winnerLabel = battleWinner === 'draw' ? '引き分け' : (battleWinner === 'red' ? 'RED TEAM' : 'BLUE TEAM');
                const hpLabel = hpResult === 'survived' ? `生存成功 +${rewardPoints}pt` : `HP0で失敗 -${penaltyPoints}pt`;
                alert(`授業を終了しました。\n勝利チーム: ${winnerLabel}\nクラスHP結果: ${hpLabel}\n成長計算: ${settlementResult.count}人 / EXP合計 ${settlementResult.totalExp}\nデータは履歴に保存されました。`);
                window.location.href = "teacherbackground.php";
            } catch (err) {
                console.error("❌ 履歴保存失败:", err);
                alert("保存に失敗しました: " + err.message);
            }
        }

        // QR Code
        let lastCode = "";
        function generateQR(code) {
            if(code === lastCode || code === "----") return; lastCode = code;
            document.getElementById("qrcode-mini").innerHTML = ""; document.getElementById("qrcode-large").innerHTML = "";
            new QRCode(document.getElementById("qrcode-mini"), { text: code, width: 50, height: 50 });
            new QRCode(document.getElementById("qrcode-large"), { text: code, width: 250, height: 250 });
        }
        function toggleFullScreenQR() { document.getElementById('qr-modal').classList.toggle('hidden'); }

        // Update Mascot
        function updateMascotState() {
            const p = curReacts.happy + curReacts.amazing;
            const n = curReacts.confused + curReacts.question;
            const o = curReacts.sleepy + curReacts.bored;
            const t = p + n + o;
            
            let state = 'neutral';
            if (t === 0) state = 'sleepy';
            else if (o > t * 0.3) state = 'sleepy';
            else if (curReacts.amazing > t * 0.2) state = 'super-happy';
            else if (n > p * 0.5) state = (n > 10 && curReacts.question > curReacts.confused) ? 'panic' : 'confused';
            else state = 'happy';

            const config = {
                'super-happy': { c: 'bg-yellow-100', a: 'animate-bounce-fast', e: '⭐' },
                'happy': { c: 'bg-green-100', a: 'animate-bounce-slow', e: '😊' },
                'neutral': { c: 'bg-white', a: 'animate-breath', e: '😐' },
                'confused': { c: 'bg-orange-100', a: 'animate-shake-gentle', e: '😵' },
                'panic': { c: 'bg-purple-100', a: 'animate-shake-hard', e: '😱' },
                'sleepy': { c: 'bg-indigo-50', a: 'animate-float', e: '😴' }
            }[state];

            document.getElementById('mascot-card').className = `rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center relative flex-1 min-h-[350px] transition-colors duration-500 ${config.c}`;
            document.getElementById('mochi-body').className = `w-40 h-32 bg-white rounded-[40%] border-[5px] border-slate-900 relative flex items-center justify-center shadow-2xl transition-all duration-300 ${config.a}`;
            
            const eyes = document.getElementById('mochi-eyes');
            if (state === 'happy' || state === 'sleepy') {
                eyes.innerHTML = '<div class="absolute top-10 left-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div><div class="absolute top-10 right-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div>';
            } else {
                eyes.innerHTML = `<div class="text-4xl absolute top-8 left-8">${config.e}</div><div class="text-4xl absolute top-8 right-8">${config.e}</div>`;
            }
            document.getElementById('mascot-status-text').innerText = state.toUpperCase();
        }

        // Chart Loop (リアルタイム表示: 上がって、無操作で減衰)
        setInterval(() => {
            heatPositive = Math.max(0, heatPositive * 0.9);
            heatNegative = Math.max(0, heatNegative * 0.88);
            chart.data.labels.push('');
            chart.data.datasets[0].data.push(Math.round(heatPositive));
            chart.data.datasets[1].data.push(Math.round(heatNegative));
            if (chart.data.labels.length > 30) { chart.data.labels.shift(); chart.data.datasets[0].data.shift(); chart.data.datasets[1].data.shift(); }
            chart.update();
        }, 2000);
    </script>
</body>
</html>
