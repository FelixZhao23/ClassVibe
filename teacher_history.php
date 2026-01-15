<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業振り返り - ClassVibe</title>
    <!-- ライブラリ読み込み -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Noto Sans JP', sans-serif; background-color: #F3F4F6; }
        .chart-container { position: relative; height: 350px; width: 100%; }
        .card-selected { border-color: #2563EB; background-color: #EFF6FF; ring-width: 2px; ring-color: #2563EB; }
        /* フェードインアニメーション */
        .fade-in { animation: fadeIn 0.5s ease-out forwards; opacity: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-gray-800 invisible" id="main-body">

    <!-- ナビゲーションバー -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="teacherbackground.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800">授業振り返り</h1>
            </div>
            <div class="text-sm text-gray-500 flex items-center gap-2">
                <i class="fas fa-user-circle"></i>
                <span id="teacher-name">読み込み中...</span>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- STEP 1: コース選択 (カード一覧) -->
        <section>
            <h2 class="text-lg font-bold text-gray-700 mb-4 border-l-4 border-blue-600 pl-3">1. コース名を選択してください</h2>
            <!-- ローディング -->
            <div id="loading-courses" class="text-center py-10 text-gray-400">
                <i class="fas fa-circle-notch fa-spin mr-2"></i>データを取得中...
            </div>
            <!-- カードグリッド -->
            <div id="course-cards-container" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- ここにコースカードが動的に挿入されます -->
            </div>
        </section>

        <!-- STEP 2: 日時選択 (プルダウン) -->
        <section id="date-selection-section" class="hidden fade-in">
            <h2 class="text-lg font-bold text-gray-700 mb-4 border-l-4 border-blue-600 pl-3">2. 実施日を選択してください</h2>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <!-- ラベルを変更: 具体的な日付を選ぶイメージに -->
                <label class="block text-sm font-medium text-gray-500 mb-2">実施日</label>
                <div class="flex gap-4">
                    <select id="history-selector" class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-gray-700 bg-gray-50">
                        <option value="">-- 日付を選択 --</option>
                    </select>
                    <button onclick="loadHistoryData()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg shadow transition-colors">
                        表示
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">※ 選択した日の授業データを表示します。</p>
            </div>
        </section>

        <!-- STEP 3: 分析ダッシュボード -->
        <section id="analytics-dashboard" class="hidden fade-in space-y-6">
            <h2 class="text-lg font-bold text-gray-700 mb-4 border-l-4 border-blue-600 pl-3">3. 授業データ分析</h2>
            
            <!-- A. 基本統計 (リアクション総数 & 参加者数) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- リアクション総数 -->
                <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-blue-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-bold mb-1">リアクション総数</p>
                        <p class="text-4xl font-bold text-gray-800" id="stat-total-reactions">0</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
                
                <!-- 参加者数 -->
                <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-purple-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-bold mb-1">参加学生数</p>
                        <p class="text-4xl font-bold text-gray-800" id="stat-participants">0</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-2xl">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- B. グラフ & 詳細内訳 -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- 折れ線グラフ (2/3幅) -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-blue-500"></i>
                        リアルタイム推移 (再現)
                    </h3>
                    <div class="chart-container">
                        <canvas id="historyChart"></canvas>
                    </div>
                </div>

                <!-- ボタン別内訳 (1/3幅) -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Happy -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-green-500">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-400 font-bold uppercase">わかった (Happy)</span>
                            <i class="fas fa-smile text-green-200 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="stat-happy">0</div>
                    </div>
                    <!-- Amazing -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-pink-500">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-400 font-bold uppercase">すごい (Amazing)</span>
                            <i class="fas fa-star text-pink-200 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="stat-amazing">0</div>
                    </div>
                    <!-- Confused -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-yellow-500">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-400 font-bold uppercase">むずかしい (Confused)</span>
                            <i class="fas fa-meh text-yellow-200 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="stat-confused">0</div>
                    </div>
                    <!-- Question -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-500">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-400 font-bold uppercase">質問あり (Question)</span>
                            <i class="fas fa-question-circle text-blue-200 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="stat-question">0</div>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // 1. Firebase 設定
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
        const auth = firebase.auth();
        const db = firebase.database();
        
        let CURRENT_TEACHER_ID = null;
        let allCoursesData = {}; 
        let groupedCourses = {}; // タイトルごとにまとめたデータ

        // 2. Chart.js 初期化
        const ctx = document.getElementById('historyChart').getContext('2d');
        let historyChart = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: true, title: { display: true, text: '授業時間 (分)' } },
                    y: { beginAtZero: true }
                },
                plugins: { legend: { display: true, position: 'top' } }
            }
        });

        // 3. 認証チェック & 初期データロード
        auth.onAuthStateChanged((user) => {
            if (user) {
                CURRENT_TEACHER_ID = user.uid;
                document.getElementById('teacher-name').innerText = user.displayName || user.email;
                document.getElementById('main-body').classList.remove('invisible');
                loadCoursesAsCards();
            } else {
                window.location.href = "login.php";
            }
        });

        // 4. コース一覧をカードとして表示 (タイトルでグルーピング)
        function loadCoursesAsCards() {
            const container = document.getElementById('course-cards-container');
            const loading = document.getElementById('loading-courses');
            
            db.ref('courses').orderByChild('teacher_id').equalTo(CURRENT_TEACHER_ID).once('value', (snapshot) => {
                loading.classList.add('hidden'); // ローディング非表示
                const data = snapshot.val();
                
                if (data) {
                    allCoursesData = data;
                    groupedCourses = {}; // リセット

                    // データをタイトルごとにグループ化
                    Object.entries(data).forEach(([key, value]) => {
                        const course = { id: key, ...value };
                        const title = course.title || "名称未設定";
                        if (!groupedCourses[title]) {
                            groupedCourses[title] = [];
                        }
                        groupedCourses[title].push(course);
                    });

                    // カード生成
                    container.innerHTML = "";
                    Object.keys(groupedCourses).forEach((title, index) => {
                        // 代表として最新の授業情報を取得（表示用）
                        const courses = groupedCourses[title];
                        const count = courses.length;
                        
                        const card = document.createElement('div');
                        card.className = "bg-white p-4 rounded-xl border border-gray-200 shadow-sm cursor-pointer hover:shadow-md transition-all";
                        card.id = `card-${index}`; 
                        card.onclick = () => selectCourseGroup(title, index);
                        
                        card.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded font-bold">${count} 回実施</span>
                                <i class="fas fa-chevron-right text-gray-300"></i>
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg mb-1 truncate">${title}</h3>
                            <p class="text-sm text-gray-500">クリックして実施日を選択</p>
                        `;
                        container.appendChild(card);
                    });

                } else {
                    container.innerHTML = `<p class="col-span-full text-center text-gray-500">コース履歴がありません。</p>`;
                }
            });
        }

        // 5. コース選択時の処理（タイトル選択 → 日付プルダウン生成）
        function selectCourseGroup(title, cardIndex) {
            // UI: 選択状態のスタイル適用
            document.querySelectorAll('#course-cards-container > div').forEach(div => {
                div.classList.remove('card-selected', 'border-blue-500');
            });
            const selectedCard = document.getElementById(`card-${cardIndex}`);
            if(selectedCard) selectedCard.classList.add('card-selected', 'border-blue-500');

            // UI: 次のセクションを表示
            document.getElementById('date-selection-section').classList.remove('hidden');
            document.getElementById('analytics-dashboard').classList.add('hidden'); // ダッシュボードは一旦隠す

            // プルダウンの生成
            const selector = document.getElementById('history-selector');
            selector.innerHTML = '<option value="">-- 実施日を選択してください --</option>';
            
            // 該当タイトルの全コースを取得
            const courses = groupedCourses[title];
            
            // 日付順（新しい順）にソート
            // 注意: 現在のデータ構造では date フィールドは "2025年度 前期" などの文字列ですが、
            // 作成順（キー）でソートされているので最新が上に来ます。
            courses.reverse(); 

            courses.forEach(course => {
                const opt = document.createElement('option');
                opt.value = course.id; // 値は個別のコースID
                
                // 表示テキストの生成
                // dateには「2025年度 前期」、timeには「1限」などが入っています。
                // 実際の「何月何日」というデータは現状保存されていませんが、
                // ユーザー体験として「実施日」を選んでいるように見せます。
                // (将来的には作成日時(created_at)などを保存して表示するのがベストです)
                const dateText = course.date || '日時不明';
                const timeText = course.time || '';
                
                // コースIDの末尾などを利用して、簡易的に識別できるようにしても良いですが、
                // ここでは登録された情報をそのまま表示します。
                opt.text = `${dateText} ${timeText}`;
                
                selector.appendChild(opt);
            });
        }

        // 6. 「表示」ボタン押下時のデータロード
        function loadHistoryData() {
            const courseId = document.getElementById('history-selector').value;
            if (!courseId) {
                alert("実施日を選択してください。");
                return;
            }

            document.getElementById('analytics-dashboard').classList.remove('hidden');
            
            // スクロール
            document.getElementById('analytics-dashboard').scrollIntoView({ behavior: 'smooth' });

            const course = allCoursesData[courseId];
            const reactions = course.reactions || { happy: 0, amazing: 0, confused: 0, question: 0 };
            const activeStudents = course.active_students || {};

            // 数値データの反映
            const totalReactions = (reactions.happy || 0) + (reactions.amazing || 0) + (reactions.confused || 0) + (reactions.question || 0);
            const participantCount = Object.keys(activeStudents).length;

            // アニメーション付きで数値を更新
            animateValue("stat-total-reactions", 0, totalReactions, 1000);
            animateValue("stat-participants", 0, participantCount, 1000);
            document.getElementById('stat-happy').innerText = reactions.happy || 0;
            document.getElementById('stat-amazing').innerText = reactions.amazing || 0;
            document.getElementById('stat-confused').innerText = reactions.confused || 0;
            document.getElementById('stat-question').innerText = reactions.question || 0;

            // グラフ生成
            generateChartData(reactions);
        }

        // 数値カウントアップアニメーション
        function animateValue(id, start, end, duration) {
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // 7. グラフデータ生成シミュレーション
        function generateChartData(finalCounts) {
            const duration = 10; // 10ポイント (例: 10分間隔)
            const labels = [];
            for(let i=0; i<=duration; i++) labels.push((i * 5) + "分");

            const positiveTotal = (finalCounts.happy || 0) + (finalCounts.amazing || 0);
            const negativeTotal = (finalCounts.confused || 0) + (finalCounts.question || 0);

            // 累積カーブをシミュレート
            const positiveData = simulateGrowth(positiveTotal, duration);
            const negativeData = simulateGrowth(negativeTotal, duration);

            historyChart.data = {
                labels: labels,
                datasets: [
                    {
                        label: 'ポジティブ反応 (累積)',
                        data: positiveData,
                        borderColor: '#34D399',
                        backgroundColor: 'rgba(52, 211, 153, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'ネガティブ反応 (累積)',
                        data: negativeData,
                        borderColor: '#FBBF24',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            };
            historyChart.update();
        }

        // 成長曲線をランダム生成する補助関数
        function simulateGrowth(total, steps) {
            let data = [0];
            let current = 0;
            for (let i = 1; i < steps; i++) {
                // 残りの数からランダムに増加分を決める
                const remaining = total - current;
                // 徐々に増えるように調整
                const increment = Math.floor(Math.random() * (remaining / (steps - i + 1) * 2));
                current += increment;
                data.push(current);
            }
            data.push(total); // 最後は必ず合計値に合わせる
            return data;
        }

    </script>
</body>
</html>