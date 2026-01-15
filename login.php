<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassVibe - 登录</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans JP', sans-serif; }
        .animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gray-100">

    <!-- 背景与Logo -->
    <div class="absolute inset-0 z-0">
        <img src="背景.png" alt="Background" class="w-full h-full object-cover" onerror="this.style.display='none'; document.body.style.backgroundColor='#eef2f6';">
        <div class="absolute inset-0 bg-black/10"></div>
    </div>
    <div class="absolute top-6 left-6 z-20">
        <img src="logo.png" alt="ClassVibe Logo" class="h-12 w-auto drop-shadow-lg" onerror="this.src='https://placehold.co/150x50/4A90E2/white?text=ClassVibe';">
    </div>

    <!-- 登录卡片 -->
    <div class="relative z-10 w-full max-w-4xl p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[500px]">
            
            <!-- 左侧装饰 -->
            <div class="hidden md:block w-1/2 relative bg-blue-50">
                <img src="小背景.png" alt="Login Banner" class="absolute inset-0 w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-8">
                    <h2 class="text-white text-2xl font-bold mb-2">Welcome Back!</h2>
                    <p class="text-gray-200 text-sm">リアクションでつながる、<br>やさしい教室へようこそ。</p>
                </div>
            </div>

            <!-- 右侧表单 -->
            <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                <div class="text-center md:text-left mb-8">
                    <h1 class="text-2xl font-bold text-gray-800">登录 ClassVibe</h1>
                    <p class="text-gray-500 mt-2 text-xs">没有账号? <a href="#" class="text-blue-600 hover:underline">联系管理员</a></p>
                </div>

                <!-- 身份切换 (演示用) -->
                <div class="flex p-1 bg-gray-100 rounded-lg mb-6">
                    <button id="tab-student" onclick="switchTab('student')" class="flex-1 py-2 text-sm font-medium rounded-md bg-white text-blue-600 shadow-sm transition-all">我是学生</button>
                    <button id="tab-teacher" onclick="switchTab('teacher')" class="flex-1 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">我是老师</button>
                </div>

                <!-- Google 登录按钮 -->
                <div class="space-y-4">
                    <button type="button" onclick="handleGoogleLogin()" class="w-full flex items-center justify-center gap-3 bg-white border border-gray-200 text-gray-700 font-medium py-2.5 px-4 rounded-xl hover:bg-gray-50 transition-all shadow-sm group">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5 group-hover:scale-110 transition-transform" alt="Google">
                        <span class="text-sm">使用 Google 账号登录</span>
                    </button>
                    
                    <div id="login-status" class="text-center text-xs text-red-500 h-4"></div>

                    <!-- 分割线 -->
                    <div class="relative flex py-1 items-center">
                        <div class="flex-grow border-t border-gray-100"></div>
                        <span class="flex-shrink-0 mx-4 text-gray-300 text-xs">OR</span>
                        <div class="flex-grow border-t border-gray-100"></div>
                    </div>

                    <!-- 传统登录 (为了演示完整性保留，实际走 Google Auth) -->
                    <div>
                        <input type="email" placeholder="example@jec.ac.jp" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-100 outline-none text-sm mb-3">
                        <input type="password" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-100 outline-none text-sm">
                    </div>
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all opacity-50 cursor-not-allowed" disabled>
                        邮箱登录 (请使用 Google)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Firebase SDK (包含 Auth) -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // 1. Firebase 配置
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
        const provider = new firebase.auth.GoogleAuthProvider();

        let currentRole = 'student';

        // 切换身份Tab
        function switchTab(role) {
            currentRole = role;
            const btnStudent = document.getElementById('tab-student');
            const btnTeacher = document.getElementById('tab-teacher');
            const activeClass = "flex-1 py-2 text-sm font-medium rounded-md bg-white text-blue-600 shadow-sm transition-all";
            const inactiveClass = "flex-1 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all";

            if (role === 'student') {
                btnStudent.className = activeClass;
                btnTeacher.className = inactiveClass;
            } else {
                btnTeacher.className = activeClass;
                btnStudent.className = inactiveClass;
            }
        }

        // 2. Google 登录逻辑 (核心)
        function handleGoogleLogin() {
            if (currentRole === 'student') {
                alert("学生端请使用手机 App 登录。Web 端仅供老师管理。");
                return;
            }

            document.getElementById('login-status').innerText = "正在连接 Google...";

            auth.signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    console.log("登录成功:", user.displayName);
                    document.getElementById('login-status').innerText = "登录成功！正在跳转...";
                    
                    // 3. 将老师信息写入/更新到数据库
                    saveTeacherInfo(user);
                }).catch((error) => {
                    console.error("登录失败:", error);
                    // 优化错误提示：处理操作未允许的错误
                    let msg = "登录失败: " + error.message;
                    if (error.code === 'auth/operation-not-allowed') {
                        msg = "登录失败: 请在 Firebase 控制台 Authentication 中启用 Google 登录。";
                    } else if (error.code === 'auth/popup-closed-by-user') {
                        msg = "登录已取消";
                    }
                    document.getElementById('login-status').innerText = msg;
                });
        }

        function saveTeacherInfo(user) {
            // 路径: teachers/{uid}
            db.ref('teachers/' + user.uid).update({
                name: user.displayName,
                email: user.email,
                last_login: Date.now()
            }).then(() => {
                // 4. 跳转到课程管理页
                window.location.href = "teacherbackground.php";
            });
        }
    </script>
</body>
</html>