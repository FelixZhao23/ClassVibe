<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassVibe - 实时课堂</title>
    
    <!-- 1. 引入样式库 (Tailwind CSS) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- 2. 引入图表库 (Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- 3. 引入图标库 (FontAwesome) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- 4. ✨ 引入二维码生成库 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body { background-color: #F3F4F6; font-family: 'Segoe UI', sans-serif; }
        
        /* 气泡小三角 */
        .bubble::after {
            content: ''; position: absolute; bottom: -10px; left: 50%;
            border-width: 10px 10px 0; border-style: solid;
            border-color: white transparent; transform: translateX(-50%);
        }

        /* === 移植自 iOS 版的 Mochi-chan 动画 === */
        @keyframes bounce-fast { 0%, 100% { transform: translateY(0) scale(1.1); } 50% { transform: translateY(-10px) scale(1.1); } }
        @keyframes bounce-slow { 0%, 100% { transform: translateY(-5%); } 50% { transform: translateY(0); } }
        @keyframes shake-gentle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
        @keyframes shake-hard { 0% { transform: translate(1px, 1px) rotate(0deg); } 10% { transform: translate(-3px, -2px) rotate(-5deg); } 50% { transform: translate(-1px, 2px) rotate(-5deg); } 100% { transform: translate(1px, -2px) rotate(-5deg); } }
        @keyframes breath { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }
        @keyframes blink { 0%, 90%, 100% { transform: scaleY(1); } 95% { transform: scaleY(0.1); } }
        @keyframes cry { 0% { height: 0; opacity: 0; } 50% { height: 40px; opacity: 1; } 100% { height: 60px; opacity: 0; transform: translateY(20px); } }
        @keyframes snot { 0% { transform: scale(0.5); } 50% { transform: scale(1.2); } 100% { transform: scale(0.5); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .animate-bounce-fast { animation: bounce-fast 0.5s infinite; }
        .animate-bounce-slow { animation: bounce-slow 2s infinite; }
        .animate-shake-gentle { animation: shake-gentle 1s infinite; }
        .animate-shake-hard { animation: shake-hard 0.5s infinite; }
        .animate-breath { animation: breath 4s infinite ease-in-out; }
        .animate-blink { animation: blink 3s infinite; }
        .animate-cry { animation: cry 1.5s infinite; }
        .animate-snot { animation: snot 3s infinite ease-in-out; }
        .animate-float { animation: float 3s infinite ease-in-out; }
        .animate-spin-slow { animation: spin-slow 3s linear infinite; }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden">

    <!-- 1. 顶部导航栏 -->
    <header class="bg-white shadow-sm z-20 flex-none h-20">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            
            <!-- 左侧：返回 & 标题 & 加入码 -->
            <div class="flex items-center gap-4">
                <a href="teacherbackground.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 leading-tight" id="course-title">正在连接课堂...</h1>
                    <!-- ✨ 显示 4 位加入码 -->
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">加入码:</span>
                        <span class="text-3xl font-mono font-black text-blue-600 tracking-widest bg-blue-50 px-3 py-0 rounded border border-blue-100" id="join-code">----</span>
                    </div>
                </div>
            </div>
            
            <!-- 右侧：二维码 & 工具 -->
            <div class="flex items-center gap-4">
                <!-- ✨ 小二维码 (点击放大) -->
                <div class="hidden md:flex flex-col items-center cursor-pointer group" onclick="toggleFullScreenQR()">
                    <div id="qrcode-mini" class="bg-white p-1 border rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                    <span class="text-[10px] text-gray-400 mt-1">点击放大</span>
                </div>

                <div class="hidden md:flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium">
                    <i class="fas fa-user-friends mr-2"></i>
                    <span id="online-count">1</span> 人在线
                </div>
                <button onclick="resetData()" class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-colors" title="重置数据">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- 2. 主内容区 -->
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 h-full">
            
            <!-- 左侧 (4/12): 角色与数据 -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                
                <!-- 🤖 Mochi-chan 角色卡片 -->
                <div id="mascot-card" class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center relative flex-1 min-h-[350px] transition-colors duration-500">
                    <!-- 气泡 -->
                    <div id="mascot-bubble" class="bubble bg-white px-6 py-3 rounded-2xl shadow-md text-gray-700 font-bold mb-8 text-center animate-bounce z-10">
                        同学们准备好了吗？
                    </div>
                    
                    <!-- 角色本体 (纯代码绘制) -->
                    <div class="relative z-10 scale-125 md:scale-150 transform transition-transform">
                        <div id="mochi-body" class="w-40 h-32 bg-white rounded-[40%] border-[5px] border-slate-900 relative flex items-center justify-center shadow-2xl transition-all duration-300 animate-breath">
                            <div class="relative w-full h-full">
                                <!-- 眼睛 -->
                                <div id="mochi-eyes"></div>
                                <!-- 嘴巴 -->
                                <div id="mochi-mouth" class="absolute left-1/2 transform -translate-x-1/2 border-slate-900 top-16 w-4 h-1 bg-slate-900 rounded-full"></div>
                                <!-- 腮红 -->
                                <div id="mochi-cheeks">
                                    <div class="absolute top-16 left-4 w-7 h-5 bg-pink-300 rounded-full opacity-60 blur-sm"></div>
                                    <div class="absolute top-16 right-4 w-7 h-5 bg-pink-300 rounded-full opacity-60 blur-sm"></div>
                                </div>
                            </div>
                        </div>
                        <!-- 影子 -->
                        <div class="w-32 h-4 bg-black/20 rounded-full blur-md mt-4 mx-auto"></div>
                    </div>

                    <!-- 状态文字 -->
                    <div class="text-center mt-8">
                        <span id="mascot-status-text" class="text-gray-400 font-bold text-lg">待机中...</span>
                    </div>
                </div>

                <!-- 📊 4个实时数据块 -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Happy -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-green-500 hover:scale-105 transition-transform">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">明白了</div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="val-happy">0</div>
                    </div>
                    <!-- Amazing -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-pink-500 hover:scale-105 transition-transform">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">太棒了</div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="val-amazing">0</div>
                    </div>
                    <!-- Confused -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-yellow-500 hover:scale-105 transition-transform">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">再讲一遍</div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="val-confused">0</div>
                    </div>
                    <!-- Question -->
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-500 hover:scale-105 transition-transform">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">有疑问</div>
                        <div class="text-3xl font-bold text-gray-800 mt-1" id="val-question">0</div>
                    </div>
                </div>
            </div>

            <!-- 右侧 (8/12): 实时图表 -->
            <div class="lg:col-span-8 flex flex-col h-full">
                <div class="bg-white rounded-2xl shadow-lg p-6 flex-1 flex flex-col relative h-full">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-700 text-lg">
                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>课堂理解度趋势
                        </h3>
                        <div class="flex gap-4 text-xs font-bold">
                            <div class="flex items-center gap-1"><span class="w-3 h-3 bg-green-400 rounded-full"></span> 积极反馈</div>
                            <div class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-400 rounded-full"></span> 消极反馈</div>
                        </div>
                    </div>
                    <!-- Chart.js 画布 -->
                    <div class="relative flex-1 w-full min-h-[300px]">
                        <canvas id="reactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ✨ 全屏二维码模态框 -->
    <div id="qr-modal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center backdrop-blur-sm" onclick="toggleFullScreenQR()">
        <div class="bg-white p-10 rounded-3xl text-center shadow-2xl transform scale-110" onclick="event.stopPropagation()">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">扫码加入课堂</h2>
            <p class="text-gray-500 mb-6">或在 App 输入: <span class="text-blue-600 font-mono font-bold text-xl" id="modal-code">----</span></p>
            <div class="flex justify-center bg-white p-2 rounded-xl">
                <!-- 大二维码容器 -->
                <div id="qrcode-large"></div>
            </div>
            <p class="text-sm text-gray-400 mt-8 cursor-pointer hover:text-gray-600" onclick="toggleFullScreenQR()">点击任意处关闭</p>
        </div>
    </div>

    <!-- Firebase SDK (兼容版) -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // ==========================================
        // 1. Firebase 配置 (请确认这些配置是你的)
        // ==========================================
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

        // 2. 获取当前课程ID
        const urlParams = new URLSearchParams(window.location.search);
        const CURRENT_COURSE_ID = urlParams.get('courseId');
        
        // 如果没有ID，跳回课程列表
        if (!CURRENT_COURSE_ID) {
            alert("未检测到课程ID，正在返回课程列表...");
            window.location.href = "teacherbackground.php";
        }

        // 3. Chart.js 初始化
        const ctx = document.getElementById('reactionChart').getContext('2d');
        const reactionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Positive',
                        data: [],
                        borderColor: '#34D399',
                        backgroundColor: 'rgba(52, 211, 153, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 3
                    },
                    {
                        label: 'Negative',
                        data: [],
                        borderColor: '#FBBF24',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: { display: false },
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' } }
                }
            }
        });

        // 4. 监听 Firebase 数据
        const courseRef = db.ref('courses/' + CURRENT_COURSE_ID);
        let currentReactions = { happy: 0, amazing: 0, confused: 0, question: 0 };

        courseRef.on('value', (snapshot) => {
            const data = snapshot.val();
            if (data) {
                // 更新标题
                document.getElementById('course-title').innerText = data.title;
                
                // ✨ 更新 4 位数加入码 (从数据库读取 simple_code)
                const code = data.simple_code || "----";
                document.getElementById('join-code').innerText = code;
                document.getElementById('modal-code').innerText = code;
                
                // ✨ 生成二维码
                generateQR(code);

                // 更新互动数据
                const reactions = data.reactions || {};
                updateDashboard(reactions);
            }
        });

        // ✨ 二维码生成函数
        let lastQR = "";
        function generateQR(code) {
            if (code === lastQR || code === "----") return;
            lastQR = code;
            
            // 清空旧的
            document.getElementById("qrcode-mini").innerHTML = "";
            document.getElementById("qrcode-large").innerHTML = "";
            
            // 生成小图 (60px)
            new QRCode(document.getElementById("qrcode-mini"), {
                text: code,
                width: 60,
                height: 60,
                colorDark : "#000000",
                colorLight : "#ffffff"
            });
            
            // 生成大图 (250px)
            new QRCode(document.getElementById("qrcode-large"), {
                text: code,
                width: 250,
                height: 250,
                colorDark : "#2563EB", // 蓝色
                colorLight : "#ffffff"
            });
        }

        // 切换全屏二维码
        function toggleFullScreenQR() {
            const modal = document.getElementById('qr-modal');
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }

        // ==========================================
        // 5. 界面与角色更新逻辑
        // ==========================================
        function updateDashboard(reactions) {
            const happy = reactions.happy || 0;
            const amazing = reactions.amazing || 0;
            const confused = reactions.confused || 0;
            const question = reactions.question || 0;
            
            currentReactions = { happy, amazing, confused, question };

            document.getElementById('val-happy').innerText = happy;
            document.getElementById('val-amazing').innerText = amazing;
            document.getElementById('val-confused').innerText = confused;
            document.getElementById('val-question').innerText = question;

            updateMascotState(happy, amazing, confused, question);
        }

        // 角色表情配置
        const mascotConfig = {
            'super-happy': { cardBg: 'bg-yellow-100', bodyAnim: 'animate-bounce-fast', eyesType: 'stars', mouthClass: 'top-14 w-8 h-8 bg-red-400 border-2 rounded-full', message: '最高！みんな天才！🤩', subMsg: '(太棒了！全班起飞！)' },
            'happy': { cardBg: 'bg-green-100', bodyAnim: 'animate-bounce-slow', eyesType: 'happy', mouthClass: 'top-14 w-6 h-4 border-b-[4px] rounded-full', message: 'わかった！嬉しいな 😊', subMsg: '(大家都听懂啦)' },
            'neutral': { cardBg: 'bg-white', bodyAnim: 'animate-breath', eyesType: 'neutral', mouthClass: 'top-16 w-4 h-1 bg-slate-900 rounded-full', message: '勉強中... 真剣です 😐', subMsg: '(认真听讲中...)' },
            'confused': { cardBg: 'bg-orange-100', bodyAnim: 'animate-shake-gentle', eyesType: 'swirl', mouthClass: 'top-16 w-3 h-3 border-[3px] rounded-full', message: 'あれ？難しいかも... 😵‍💫', subMsg: '(哎呀，有点懵圈...)' },
            'panic': { cardBg: 'bg-purple-100', bodyAnim: 'animate-shake-hard', eyesType: 'crying', mouthClass: 'top-18 w-10 h-6 bg-slate-800 rounded-xl animate-pulse', message: 'ヘルプ！全然わからない！😱', subMsg: '(救命！完全听不懂了！)' },
            'sleepy': { cardBg: 'bg-indigo-50', bodyAnim: 'animate-float', eyesType: 'closed', mouthClass: 'hidden', message: 'ぐすー... zZZ 😴', subMsg: '(呼... 睡着了)' }
        };

        function updateMascotState(happy, amazing, confused, question) {
            const positive = happy + amazing;
            const negative = confused + question;
            const total = positive + negative;
            
            let state = 'neutral';
            
            if (total === 0) {
                state = 'sleepy';
            } else if (amazing > 0 && amazing >= total * 0.3) { 
                state = 'super-happy';
            } else if (negative > positive * 0.5) {
                if (negative > 10 && question > confused) {
                     state = 'panic';
                } else {
                     state = 'confused';
                }
            } else {
                state = 'happy';
            }

            const cfg = mascotConfig[state];

            document.getElementById('mascot-card').className = `rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center relative flex-1 min-h-[350px] transition-colors duration-500 ${cfg.cardBg}`;
            document.getElementById('mochi-body').className = `w-40 h-32 bg-white rounded-[40%] border-[5px] border-slate-900 relative flex items-center justify-center shadow-2xl transition-all duration-300 ${cfg.bodyAnim}`;
            document.getElementById('mochi-eyes').innerHTML = getEyesHTML(cfg.eyesType);
            document.getElementById('mochi-mouth').className = `absolute left-1/2 transform -translate-x-1/2 border-slate-900 ${cfg.mouthClass}`;
            document.getElementById('mascot-bubble').innerText = cfg.message;
            document.getElementById('mascot-status-text').innerText = cfg.subMsg;
        }

        function getEyesHTML(type) {
            if (type === 'happy') return '<div class="absolute top-10 left-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div><div class="absolute top-10 right-8 w-6 h-4 border-t-[5px] border-slate-900 rounded-full"></div>';
            if (type === 'stars') return '<div class="absolute top-8 left-6 text-yellow-500 text-3xl animate-spin-slow">⭐</div><div class="absolute top-8 right-6 text-yellow-500 text-3xl animate-spin-slow">⭐</div>';
            if (type === 'neutral') return '<div class="absolute top-10 left-10 w-3 h-4 bg-slate-900 rounded-full animate-blink"></div><div class="absolute top-10 right-10 w-3 h-4 bg-slate-900 rounded-full animate-blink"></div>';
            if (type === 'swirl') return '<div class="absolute top-8 left-8 text-slate-900 text-2xl animate-spin">😵</div><div class="absolute top-8 right-8 text-slate-900 text-2xl animate-spin">😵</div><div class="absolute -top-6 -right-6 text-4xl font-black text-slate-900 animate-bounce">?</div>';
            if (type === 'crying') return '<div class="absolute top-10 left-8 w-6 h-2 bg-slate-900 rotate-12"></div><div class="absolute top-10 right-8 w-6 h-2 bg-slate-900 -rotate-12"></div><div class="absolute top-12 left-9 w-4 h-12 bg-blue-300 rounded-full animate-cry"></div><div class="absolute top-12 right-9 w-4 h-12 bg-blue-300 rounded-full animate-cry" style="animation-delay: 0.2s"></div>';
            if (type === 'closed') return '<div class="absolute top-12 left-8 w-6 h-1 bg-slate-900"></div><div class="absolute top-12 right-8 w-6 h-1 bg-slate-900"></div><div class="absolute top-12 right-12 w-8 h-8 bg-blue-200/50 rounded-full border-2 border-white animate-snot origin-bottom-left"></div>';
            return '';
        }

        // ==========================================
        // 6. 图表自动更新
        // ==========================================
        setInterval(() => {
            const positive = currentReactions.happy + currentReactions.amazing;
            const negative = currentReactions.confused + currentReactions.question;

            reactionChart.data.labels.push('');
            reactionChart.data.datasets[0].data.push(positive);
            reactionChart.data.datasets[1].data.push(negative);

            if (reactionChart.data.labels.length > 30) {
                reactionChart.data.labels.shift();
                reactionChart.data.datasets[0].data.shift();
                reactionChart.data.datasets[1].data.shift();
            }
            reactionChart.update();
        }, 2000);

        function resetData() {
            if(confirm("确定要清空当前课堂的所有反馈数据吗？")) {
                courseRef.child('reactions').set({ happy: 0, amazing: 0, confused: 0, question: 0 });
            }
        }
    </script>
</body>
</html>