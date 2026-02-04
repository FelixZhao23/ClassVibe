<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassVibe - リアルタイム授業</title>
    
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
        @keyframes damage { 0% { transform: scale(1); filter: brightness(1); } 50% { transform: scale(0.9); filter: brightness(0.5) sepia(1) hue-rotate(-50deg) saturate(5); } 100% { transform: scale(1); filter: brightness(1); } }

        .animate-bounce-fast { animation: bounce-fast 0.5s infinite; }
        .animate-bounce-slow { animation: bounce-slow 2s infinite; }
        .animate-shake-gentle { animation: shake-gentle 1s infinite; }
        .animate-shake-hard { animation: shake-hard 0.5s infinite; }
        .animate-breath { animation: breath 4s infinite ease-in-out; }
        .animate-damage { animation: damage 0.2s ease-in-out; }
        
        /* Game UI */
        .boss-hp-bar-container { box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); }
        .shadow-text { text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden relative">

    <header class="bg-white shadow-sm z-20 flex-none h-20">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            
            <div class="flex items-center gap-4">
                <a href="teacherbackground.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h1 class="text-xl font-bold text-gray-900 leading-tight" id="course-title">接続中...</h1>
                        <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded" id="course-time">-- : --</span>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">Code:</span>
                        <span class="text-2xl font-mono font-black text-blue-600 tracking-widest" id="join-code">----</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="hidden md:flex flex-col items-center cursor-pointer group" onclick="toggleFullScreenQR()">
                    <div id="qrcode-mini" class="bg-white p-1 border rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                    <span class="text-[10px] text-gray-400 mt-1">拡大</span>
                </div>

                <div class="flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 shadow-sm">
                    <i class="fas fa-users mr-2 text-lg"></i>
                    <div class="text-center leading-none">
                        <span class="text-xs font-normal text-blue-500 block">参加者数</span>
                        <span class="text-lg font-bold" id="active-student-count">0</span>
                    </div>
                </div>

                <button onclick="stopClass()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg shadow font-bold flex items-center gap-2 transition-colors">
                    <i class="fas fa-stop-circle"></i>
                    授業終了
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-4 flex flex-col gap-6">
                
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

                <div class="bg-white p-5 rounded-2xl shadow border-l-4 border-indigo-500">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center text-lg">
                        <i class="fas fa-gamepad mr-2 text-indigo-500"></i> クラスアクティビティ
                    </h3>
                    <div class="space-y-3">
                        <button onclick="startGame('battle')" class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-red-500 to-blue-500 text-white rounded-xl shadow hover:opacity-90 transition transform hover:-translate-y-1">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">⚔️</span>
                                <div class="text-left">
                                    <div class="font-bold">赤青対抗戦</div>
                                    <div class="text-xs opacity-90">チームで競う！</div>
                                </div>
                            </div>
                            <i class="fas fa-play-circle text-2xl"></i>
                        </button>
                        
                        <button onclick="startGame('boss')" class="w-full flex items-center justify-between p-4 bg-gray-800 text-white rounded-xl shadow hover:bg-gray-700 transition transform hover:-translate-y-1">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">👾</span>
                                <div class="text-left">
                                    <div class="font-bold">BOSS討伐</div>
                                    <div class="text-xs opacity-70">全員で協力！</div>
                                </div>
                            </div>
                            <i class="fas fa-play-circle text-2xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-green-500"><div class="text-xs text-gray-400 font-bold uppercase">わかった</div><div class="text-3xl font-bold text-gray-800" id="val-happy">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-pink-500"><div class="text-xs text-gray-400 font-bold uppercase">すごい！</div><div class="text-3xl font-bold text-gray-800" id="val-amazing">0</div></div>
                    
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-yellow-500"><div class="text-xs text-gray-400 font-bold uppercase">むずかしい</div><div class="text-3xl font-bold text-gray-800" id="val-confused">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-blue-500"><div class="text-xs text-gray-400 font-bold uppercase">質問あり</div><div class="text-3xl font-bold text-gray-800" id="val-question">0</div></div>

                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-gray-400 bg-gray-50"><div class="text-xs text-gray-500 font-bold uppercase">眠い...</div><div class="text-3xl font-bold text-gray-600" id="val-sleepy">0</div></div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-b-4 border-gray-400 bg-gray-50"><div class="text-xs text-gray-500 font-bold uppercase">暇</div><div class="text-3xl font-bold text-gray-600" id="val-bored">0</div></div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 flex-1 min-h-[350px]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-700 text-lg"><i class="fas fa-chart-line text-blue-500 mr-2"></i>クラスの熱量 (累積)</h3>
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

    <div id="game-overlay" class="fixed inset-0 bg-black/95 z-50 hidden flex flex-col items-center justify-center text-white transition-opacity duration-300">
        
        <button onclick="stopGame()" class="absolute top-8 right-8 text-white/50 hover:text-white text-xl border border-white/30 px-4 py-2 rounded-full hover:bg-white/10 transition">
            <i class="fas fa-times mr-2"></i>終了
        </button>

        <div id="game-battle-ui" class="hidden w-full max-w-5xl text-center px-4">
            <h2 class="text-5xl font-black mb-12 tracking-wider text-yellow-400 drop-shadow-[0_0_15px_rgba(250,204,21,0.5)]">🔥 赤青対抗戦 🔥</h2>
            
            <div class="flex justify-between items-end mb-6 px-4 md:px-20">
                <div class="text-center">
                    <div class="text-8xl font-black text-red-500 drop-shadow-md" id="score-red">0</div>
                    <div class="text-2xl text-red-300 font-bold mt-2">RED TEAM</div>
                </div>
                <div class="text-4xl font-black text-white/30 italic mb-4">VS</div>
                <div class="text-center">
                    <div class="text-8xl font-black text-blue-500 drop-shadow-md" id="score-blue">0</div>
                    <div class="text-2xl text-blue-300 font-bold mt-2">BLUE TEAM</div>
                </div>
            </div>
            
            <div class="relative w-full h-24 bg-gray-800 rounded-full overflow-hidden border-8 border-gray-700 shadow-inner">
                <div id="battle-bar-red" class="h-full bg-gradient-to-r from-red-700 via-red-500 to-red-400 transition-all duration-300 ease-out flex items-center justify-end pr-4" style="width: 50%">
                    <div class="h-full w-2 bg-white/50 blur-sm"></div>
                </div>
                <div class="absolute top-0 bottom-0 left-1/2 w-1 bg-white/20 -ml-0.5 z-0"></div>
                <div id="battle-knot" class="absolute top-1/2 -translate-y-1/2 transition-all duration-300 ease-out z-10" style="left: 50%">
                    <div class="text-6xl -ml-8 filter drop-shadow-lg">🪢</div>
                </div>
            </div>
            <p class="mt-12 text-2xl text-white/80 animate-pulse font-bold">スマホを連打して綱を引け！</p>
        </div>

        <div id="game-boss-ui" class="hidden w-full max-w-3xl text-center px-4">
            <h2 class="text-5xl font-black mb-6 text-purple-400 drop-shadow-[0_0_15px_rgba(168,85,247,0.5)]">👾 BOSS BATTLE 👾</h2>
            
            <div class="relative mb-10 h-64 flex items-center justify-center">
                <div id="boss-avatar" class="text-[180px] transition-transform duration-100 select-none filter drop-shadow-2xl">🦖</div>
                <div id="damage-container" class="absolute inset-0 pointer-events-none"></div>
            </div>

            <div class="w-full boss-hp-bar-container h-16 bg-gray-900 rounded-full overflow-hidden border-4 border-gray-700 relative mb-4">
                <div id="boss-hp-bar" class="h-full bg-gradient-to-r from-green-500 via-green-400 to-green-300 transition-all duration-300" style="width: 100%"></div>
                <div class="absolute inset-0 flex items-center justify-center text-xl font-black text-white shadow-text tracking-widest z-10">
                    HP: <span id="boss-hp-text">1000</span> / 1000
                </div>
            </div>
            <p class="text-2xl text-purple-200 font-bold mt-8">全員で攻撃ボタンを連打せよ！</p>
        </div>

    </div>

    <div id="qr-modal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center backdrop-blur-sm" onclick="toggleFullScreenQR()">
        <div class="bg-white p-10 rounded-3xl text-center shadow-2xl transform scale-110" onclick="event.stopPropagation()">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">QRコードで参加</h2>
            <p class="text-gray-500 mb-6">参加コード: <span class="text-blue-600 font-mono font-bold text-xl" id="modal-code">----</span></p>
            <div class="flex justify-center bg-white p-2 rounded-xl border border-gray-200"><div id="qrcode-large"></div></div>
            <p class="text-sm text-gray-400 mt-8 cursor-pointer hover:text-gray-600" onclick="toggleFullScreenQR()">閉じる</p>
        </div>
    </div>

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
        // 二重初期化防止
        if (!firebase.apps.length) firebase.initializeApp(firebaseConfig);
        const db = firebase.database();

        const urlParams = new URLSearchParams(window.location.search);
        const COURSE_ID = urlParams.get('courseId');
        if (!COURSE_ID) { alert("ID Error"); window.location.href = "index.php"; }

        // Chart Setup
        const ctx = document.getElementById('reactionChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'Positive', data: [], borderColor: '#34D399', backgroundColor: 'rgba(52,211,153,0.1)', fill: true, tension: 0.4 },
                { label: 'Negative', data: [], borderColor: '#FBBF24', backgroundColor: 'rgba(251,191,36,0.1)', fill: true, tension: 0.4 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false }, y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });

        // Data Variables
        const courseRef = db.ref('courses/' + COURSE_ID);
        let curReacts = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
        let studentCount = 0;
        let lastBossHp = 1000;
        const BOSS_MAX_HP = 1000;
        
        // ★ 授業開始時間を記録（ページを開いた時間を開始とする）
        const sessionStartTime = new Date();

        // 2. Firebase Listener
        courseRef.on('value', (snapshot) => {
            const data = snapshot.val();
            if (data) {
                // Info
                document.getElementById('course-title').innerText = data.name || data.title || "コース名なし";
                document.getElementById('course-time').innerText = data.time || "-- : --"; 
                const code = data.code || data.simple_code || "----";
                document.getElementById('join-code').innerText = code;
                document.getElementById('modal-code').innerText = code;
                generateQR(code);
                
                // Active Students
                const active = data.active_students || {};
                studentCount = Object.keys(active).length;
                document.getElementById('active-student-count').innerText = studentCount;

                // Reactions
                const r = data.reactions || {};
                curReacts = { 
                    happy: r.happy||0, amazing: r.amazing||0, 
                    confused: r.confused||0, question: r.question||0,
                    sleepy: r.sleepy||0, bored: r.bored||0 
                };
                updateDashboard();
                
                // Game Status
                const game = data.game || {};
                updateGameUI(game);
            }
        });

        function updateDashboard() {
            ['happy','amazing','confused','question','sleepy','bored'].forEach(k => {
                const el = document.getElementById('val-'+k);
                if(el) el.innerText = curReacts[k];
            });
            updateMascotState();
        }

        // ==========================================
        // 🎮 Game Logic
        // ==========================================
        function startGame(mode) {
            const updates = {};
            updates[`courses/${COURSE_ID}/game/status`] = mode;
            if (mode === 'battle') {
                updates[`courses/${COURSE_ID}/game/battle`] = { red: 0, blue: 0 };
            } else if (mode === 'boss') {
                updates[`courses/${COURSE_ID}/game/boss`] = { hp: BOSS_MAX_HP, max_hp: BOSS_MAX_HP };
            }
            db.ref().update(updates);
        }

        function stopGame() {
            db.ref(`courses/${COURSE_ID}/game/status`).set('none');
        }

        function updateGameUI(game) {
            const status = game.status || 'none';
            const overlay = document.getElementById('game-overlay');
            const battleUI = document.getElementById('game-battle-ui');
            const bossUI = document.getElementById('game-boss-ui');

            if (status === 'none') {
                overlay.classList.add('hidden');
                return;
            }
            overlay.classList.remove('hidden');

            // Battle Mode
            if (status === 'battle') {
                battleUI.classList.remove('hidden');
                bossUI.classList.add('hidden');
                
                const scores = game.battle || { red: 0, blue: 0 };
                document.getElementById('score-red').innerText = scores.red;
                document.getElementById('score-blue').innerText = scores.blue;
                
                const total = (scores.red + scores.blue) || 1;
                const redPercent = (scores.red / total) * 100;
                document.getElementById('battle-bar-red').style.width = `${redPercent}%`;
                document.getElementById('battle-knot').style.left = `${redPercent}%`;
            }

            // Boss Mode
            if (status === 'boss') {
                bossUI.classList.remove('hidden');
                battleUI.classList.add('hidden');
                
                const bossData = game.boss || { hp: BOSS_MAX_HP };
                const hpPercent = Math.max(0, (bossData.hp / BOSS_MAX_HP) * 100);
                
                document.getElementById('boss-hp-bar').style.width = `${hpPercent}%`;
                document.getElementById('boss-hp-text').innerText = Math.max(0, bossData.hp);

                if (bossData.hp < lastBossHp) {
                    const bossAvatar = document.getElementById('boss-avatar');
                    bossAvatar.classList.add('animate-damage');
                    setTimeout(() => bossAvatar.classList.remove('animate-damage'), 200);
                    showDamageEffect();
                }
                lastBossHp = bossData.hp;

                if (bossData.hp <= 0) {
                     document.getElementById('boss-avatar').innerText = "💀";
                     document.getElementById('boss-hp-text').innerText = "VICTORY!";
                } else {
                     document.getElementById('boss-avatar').innerText = "🦖";
                }
            }
        }

        function showDamageEffect() {
            const container = document.getElementById('damage-container');
            const el = document.createElement('div');
            el.innerText = "-10";
            el.className = "absolute text-red-500 font-bold text-4xl animate-bounce-fast";
            el.style.left = (50 + (Math.random() * 20 - 10)) + "%";
            el.style.top = (20 + (Math.random() * 20 - 10)) + "%";
            container.appendChild(el);
            setTimeout(() => el.remove(), 500);
        }

        // ==========================================
        // 🚫 End Class Logic (修正済: コース一覧へ戻る)
        // ==========================================
        function stopClass() {
            if(!confirm("授業を終了しますか？\nデータは履歴に保存され、コース一覧に戻ります。")) return;

            const sessionEndTime = new Date();
            const title = document.getElementById('course-title').innerText;
            
            // 履歴用データ作成
            const historyData = {
                class_id: COURSE_ID,  
                topic: title,         
                start_time: formatTime(sessionStartTime),
                end_time: formatTime(sessionEndTime),
                reactions: curReacts,
                student_count: studentCount
            };

            // 1. class_sessions に保存
            db.ref('class_sessions').push(historyData)
                .then(() => {
                    // 2. 現在の授業データをリセット
                    const resetUpdates = {};
                    resetUpdates[`courses/${COURSE_ID}/reactions`] = { happy:0, amazing:0, confused:0, question:0, sleepy:0, bored:0 };
                    resetUpdates[`courses/${COURSE_ID}/active_students`] = null;
                    resetUpdates[`courses/${COURSE_ID}/game/status`] = 'none';
                    
                    return db.ref().update(resetUpdates);
                })
                .then(() => {
                    alert("お疲れ様でした！\nデータを保存しました。コース一覧へ戻ります。");
                    // 3. コース一覧ページへ遷移
                   window.location.href = "teacherbackground.php";
                })
                .catch((error) => {
                    alert("保存エラー: " + error.message);
                });
        }

        // 日時フォーマット関数 (YYYY-MM-DD HH:mm形式)
        function formatTime(date) {
            const y = date.getFullYear();
            const m = ('0' + (date.getMonth() + 1)).slice(-2);
            const d = ('0' + date.getDate()).slice(-2);
            const h = ('0' + date.getHours()).slice(-2);
            const min = ('0' + date.getMinutes()).slice(-2);
            return `${y}-${m}-${d} ${h}:${min}`;
        }

        // QR Code
        let lastCode = "";
        function generateQR(code) {
            if(code === lastCode || code === "----") return; lastCode = code;
            document.getElementById("qrcode-mini").innerHTML = ""; document.getElementById("qrcode-large").innerHTML = "";
            const joinUrl = `${window.location.origin}/student_login.php?code=${code}`; 
            new QRCode(document.getElementById("qrcode-mini"), { text: joinUrl, width: 50, height: 50 });
            new QRCode(document.getElementById("qrcode-large"), { text: joinUrl, width: 250, height: 250 });
        }
        function toggleFullScreenQR() { document.getElementById('qr-modal').classList.toggle('hidden'); }

        // Update Mascot
        function updateMascotState() {
            const p = curReacts.happy + curReacts.amazing;
            const n = curReacts.confused + curReacts.question;
            const o = curReacts.sleepy + curReacts.bored;
            const t = p + n + o;
            
            let state = 'neutral';
            if (t > 0) {
                if (o > t * 0.3) state = 'sleepy';
                else if (curReacts.amazing > t * 0.2) state = 'super-happy';
                else if (n > p * 0.5) state = (n > 10 && curReacts.question > curReacts.confused) ? 'panic' : 'confused';
                else if (p > n) state = 'happy';
            }

            const config = {
                'super-happy': { c: 'bg-yellow-100', a: 'animate-bounce-fast', e: '⭐' },
                'happy': { c: 'bg-green-100', a: 'animate-bounce-slow', e: '😊' },
                'neutral': { c: 'bg-white', a: 'animate-breath', e: '😐' },
                'confused': { c: 'bg-orange-100', a: 'animate-shake-gentle', e: '😵' },
                'panic': { c: 'bg-purple-100', a: 'animate-shake-hard', e: '😱' },
                'sleepy': { c: 'bg-indigo-50', a: 'animate-float', e: '😴' }
            }[state];

            document.getElementById('mascot-card').className = `rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center relative flex-1 min-h-[300px] transition-colors duration-500 ${config.c}`;
            document.getElementById('mochi-body').className = `w-40 h-32 bg-white rounded-[40%] border-[5px] border-slate-900 relative flex items-center justify-center shadow-2xl transition-all duration-300 ${config.a}`;
            
            const eyes = document.getElementById('mochi-eyes');
            if (state === 'happy' || state === 'sleepy') {
                eyes.innerHTML = '<div class="absolute top-10 left-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div><div class="absolute top-10 right-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div>';
            } else {
                eyes.innerHTML = `<div class="text-4xl absolute top-8 left-8">${config.e}</div><div class="text-4xl absolute top-8 right-8">${config.e}</div>`;
            }
            document.getElementById('mascot-status-text').innerText = state.toUpperCase();
        }

        // Chart Loop
        setInterval(() => {
            const p = curReacts.happy + curReacts.amazing;
            const n = curReacts.confused + curReacts.question;
            chart.data.labels.push('');
            chart.data.datasets[0].data.push(p);
            chart.data.datasets[1].data.push(n);
            if (chart.data.labels.length > 30) { chart.data.labels.shift(); chart.data.datasets[0].data.shift(); chart.data.datasets[1].data.shift(); }
            chart.update();
        }, 2000);
    </script>
</body>
</html>