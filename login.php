<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassVibe - 教員用ログイン</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts (Noto Sans JP) -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Noto Sans JP', sans-serif; }
        .animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gray-100">

    <!-- 背景画像 & Logo -->
    <div class="absolute inset-0 z-0">
        <!-- 确保背景图片存在，或者使用背景色兜底 -->
        <img src="背景.png" alt="Background" class="w-full h-full object-cover" 
             onerror="this.style.display='none'; document.body.style.backgroundColor='#eef2f6';">
        <div class="absolute inset-0 bg-black/10"></div>
    </div>
    
    <div class="absolute top-6 left-6 z-20">
        <img src="logo.png" alt="ClassVibe Logo" class="h-12 w-auto drop-shadow-lg" 
             onerror="this.src='https://placehold.co/150x50/4A90E2/white?text=ClassVibe';">
    </div>

    <!-- ログインカード -->
    <div class="relative z-10 w-full max-w-4xl p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[500px]">
            
            <!-- 左側：装飾エリア -->
            <div class="hidden md:block w-1/2 relative bg-blue-50">
                <img src="小背景.png" alt="Login Banner" class="absolute inset-0 w-full h-full object-cover" 
                     onerror="this.src='https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';">
                
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-8">
                    <h2 class="text-white text-2xl font-bold mb-2">Welcome Back!</h2>
                    <p class="text-gray-200 text-sm leading-relaxed">
                        リアクションでつながる、<br>
                        やさしい教室へようこそ。
                    </p>
                </div>
            </div>

            <!-- 右側：ログインフォーム -->
            <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                <div class="text-center md:text-left mb-10">
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded mb-2 inline-block">教員専用</span>
                    <h1 class="text-2xl font-bold text-gray-800">ClassVibe ログイン</h1>
                    <p class="text-gray-500 mt-2 text-xs">
                        アカウントをお持ちでない場合は、<br>
                        <a href="#" class="text-blue-600 hover:underline">管理者にお問い合わせください</a>
                    </p>
                </div>

                <!-- Google ログインボタン (メイン) -->
                <div class="space-y-6">
                    <button type="button" onclick="handleGoogleLogin()" class="w-full flex items-center justify-center gap-3 bg-white border border-gray-300 text-gray-700 font-medium py-4 px-4 rounded-xl hover:bg-gray-50 hover:border-blue-400 transition-all shadow-sm hover:shadow-md group transform active:scale-95 duration-200">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-6 h-6 group-hover:scale-110 transition-transform" alt="Google">
                        <span class="text-base">Google アカウントでログイン</span>
                    </button>
                    
                    <!-- ステータス表示 -->
                    <div id="login-status" class="text-center text-sm font-medium text-red-500 h-6"></div>

                    <div class="text-center">
                        <p class="text-xs text-gray-400">
                            ※ 学生の方は、スマートフォンアプリをご利用ください。
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- フッター -->
        <p class="text-center text-white/80 text-xs mt-6 drop-shadow-md">
            © 2025 ClassVibe Graduation Project. Designed by 趙普湘 & 王瑛琦
        </p>
    </div>

    <!-- Firebase SDK (互換版) -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // ==========================================
        // 1. Firebase 設定 (あなたの設定を使用)
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

        // 初期化
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const db = firebase.database();
        const provider = new firebase.auth.GoogleAuthProvider();

        // 🔥 关键修改：强制每次都显示账号选择界面
        provider.setCustomParameters({
            prompt: 'select_account'
        });

        // ==========================================
        // 2. Google ログイン処理
        // ==========================================
        function handleGoogleLogin() {
            const statusEl = document.getElementById('login-status');
            statusEl.innerText = "Googleに接続中...";
            statusEl.className = "text-center text-sm font-medium text-blue-600 h-6"; 

            auth.signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    console.log("ログイン成功:", user.displayName);
                    
                    statusEl.innerText = "ログイン成功！ページを移動します...";
                    statusEl.className = "text-center text-sm font-medium text-green-600 h-6"; 
                    
                    // ユーザー情報を保存して遷移
                    saveTeacherInfo(user);
                }).catch((error) => {
                    console.error("ログインエラー:", error);
                    let msg = "エラーが発生しました: " + error.message;
                    
                    if (error.code === 'auth/operation-not-allowed') {
                        msg = "エラー: Firebase ConsoleでGoogleログインを有効にしてください。";
                    } else if (error.code === 'auth/popup-closed-by-user') {
                        msg = "ログインがキャンセルされました。";
                    } else if (error.code === 'auth/popup-blocked') {
                        msg = "ポップアップがブロックされました。設定を確認してください。";
                    }
                    
                    statusEl.innerText = msg;
                    statusEl.className = "text-center text-sm font-medium text-red-500 h-6"; 
                });
        }

        // ==========================================
        // 3. ユーザー情報の保存 & 画面遷移
        // ==========================================
        function saveTeacherInfo(user) {
            // teachers テーブルに保存
            db.ref('teachers/' + user.uid).update({
                name: user.displayName,
                email: user.email,
                last_login: Date.now()
            }).then(() => {
                // コース管理画面へ遷移
                window.location.href = "teacherbackground.php";
            }).catch((err) => {
                // 记录失败但不阻塞登录流程，避免因规则变更卡在登录页
                console.warn("teachers write failed:", err);
                window.location.href = "teacherbackground.php";
            });
        }
        
        // 既にログイン済みなら自動遷移（オプション）
        // auth.onAuthStateChanged((user) => {
        //     if (user) {
        //         window.location.href = "teacherbackground.php";
        //     }
        // });
    </script>
</body>
</html>
