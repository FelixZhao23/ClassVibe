<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的课程 - ClassVibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .modal { transition: opacity 0.3s ease-in-out; }
        .modal-content { transition: transform 0.3s ease-in-out; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-gray-800 invisible" id="main-body">

    <!-- 顶栏 -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-blue-600 text-white font-bold rounded px-2 py-1">CV</div>
                <h1 class="text-xl font-bold text-gray-800">课程管理</h1>
                <!-- 历史回顾按钮 -->
                <a href="teacher_history.php" class="ml-6 text-gray-500 hover:text-blue-600 font-medium flex items-center gap-2 transition-colors">
                    <i class="fas fa-history"></i> 历史回顾
                </a>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <img id="user-avatar" src="" alt="" class="w-8 h-8 rounded-full border border-gray-200 hidden">
                    <span class="text-sm text-gray-500">欢迎, <b id="teacher-name">加载中...</b></span>
                </div>
                <button onclick="handleLogout()" class="text-sm text-red-500 hover:text-red-700 border border-red-200 px-3 py-1 rounded hover:bg-red-50 transition">
                    登出
                </button>
            </div>
        </div>
    </nav>

    <!-- 主内容区 -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">我的课程表</h2>
            <div id="loading-indicator" class="loader hidden"></div>
        </div>

        <div id="course-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 新建课程卡片 -->
            <div onclick="openModal()" class="bg-white border-2 border-dashed border-gray-300 rounded-xl h-48 flex flex-col items-center justify-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all group">
                <div class="w-12 h-12 rounded-full bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mb-2 transition-colors">
                    <i class="fas fa-plus text-gray-400 group-hover:text-blue-600 text-xl"></i>
                </div>
                <span class="text-gray-500 group-hover:text-blue-600 font-medium">添加新课程</span>
            </div>
            <!-- 动态卡片插入区 -->
        </div>
    </main>

    <!-- 添加课程模态框 -->
    <div id="add-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden modal-content scale-95 opacity-0 transition-all" id="modal-panel">
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg">新建课程</h3>
                <button onclick="closeModal()" class="text-white/80 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form id="add-course-form" onsubmit="handleAddCourse(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">课程名称</label>
                    <input type="text" id="input-title" placeholder="例如：Java 基础第3回" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">日期</label>
                        <input type="date" id="input-date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">时间</label>
                        <input type="time" id="input-time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">取消</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow transition-colors">创建</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // 1. Firebase 配置 (请确认这些配置是你的)
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

        // 2. 身份验证
        auth.onAuthStateChanged((user) => {
            if (user) {
                CURRENT_TEACHER_ID = user.uid;
                updateUserProfile(user);
                document.getElementById('main-body').classList.remove('invisible');
                loadCourses();
            } else {
                window.location.href = "login.php";
            }
        });

        function updateUserProfile(user) {
            const displayName = user.displayName || user.email.split('@')[0];
            document.getElementById('teacher-name').innerText = displayName;
            if (user.photoURL) {
                const avatar = document.getElementById('user-avatar');
                avatar.src = user.photoURL;
                avatar.classList.remove('hidden');
            }
        }

        function handleLogout() {
            if(confirm("确定要退出登录吗？")) {
                auth.signOut().then(() => { window.location.href = "login.php"; });
            }
        }

        // 3. 加载课程列表
        function loadCourses() {
            const loading = document.getElementById('loading-indicator');
            loading.classList.remove('hidden');
            const coursesRef = db.ref('courses');
            
            coursesRef.on('value', (snapshot) => {
                loading.classList.add('hidden');
                const data = snapshot.val();
                const listContainer = document.getElementById('course-list');
                
                while (listContainer.children.length > 1) {
                    listContainer.removeChild(listContainer.lastChild);
                }

                if (data) {
                    const coursesArray = Object.entries(data).map(([key, value]) => ({ id: key, ...value }));
                    coursesArray.sort((a, b) => {
                        const dateA = new Date(a.date + ' ' + a.time);
                        const dateB = new Date(b.date + ' ' + b.time);
                        return dateB - dateA;
                    });

                    coursesArray.forEach(course => {
                        if (course.teacher_id === CURRENT_TEACHER_ID) {
                            const cardHTML = createCourseCard(course);
                            listContainer.insertAdjacentHTML('beforeend', cardHTML);
                        }
                    });
                }
            });
        }

        function createCourseCard(course) {
            const gradients = [
                'from-blue-500 to-cyan-400',
                'from-purple-500 to-pink-400',
                'from-green-500 to-emerald-400',
                'from-orange-400 to-amber-300'
            ];
            const colorIndex = course.id.charCodeAt(course.id.length - 1) % gradients.length;
            const randomGradient = gradients[colorIndex];
            
            // ✨ 显示验证码
            const simpleCode = course.simple_code ? `码: ${course.simple_code}` : '无验证码';

            return `
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all card-hover overflow-hidden flex flex-col h-48 relative group animate-fade-in">
                <div class="h-2 bg-gradient-to-r ${randomGradient}"></div>
                <div class="p-6 flex-1 flex flex-col relative">
                    <button onclick="deleteCourse('${course.id}')" 
                            class="absolute top-4 right-4 text-gray-300 hover:text-red-500 p-2 transition-colors z-10" 
                            title="删除课程">
                        <i class="fas fa-trash-alt"></i>
                    </button>

                    <div class="flex justify-between items-start mb-2 pr-8">
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded font-mono">${course.date || '无日期'}</span>
                        <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-100">${simpleCode}</span>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-800 mb-1 truncate" title="${course.title}">${course.title}</h3>
                    <p class="text-sm text-gray-500 flex items-center gap-2"><i class="far fa-clock"></i> ${course.time || '未设置时间'}</p>
                    
                    <div class="mt-auto pt-4 flex justify-between items-center">
                        <div class="flex -space-x-2 overflow-hidden">
                            <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-200"></div>
                            <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-300"></div>
                            <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px] text-gray-500">+</div>
                        </div>
                        <a href="index.php?courseId=${course.id}" 
                           class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                           进入课堂 <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>`;
        }

        // ==========================================
        // 4. ✨ 核心修改：生成 4 位数代码并保存
        // ==========================================
        async function handleAddCourse(e) {
            e.preventDefault();
            if (!CURRENT_TEACHER_ID) { alert("错误：未登录"); return; }
            
            const title = document.getElementById('input-title').value;
            const date = document.getElementById('input-date').value;
            const time = document.getElementById('input-time').value;
            
            // 1. 生成唯一的 4 位码
            const simpleCode = await generateUniqueCode();
            
            // 2. 生成新课程 ID
            const newCourseRef = db.ref('courses').push();
            const newCourseId = newCourseRef.key;

            const newCourseData = {
                title: title,
                date: date,
                time: time,
                teacher_id: CURRENT_TEACHER_ID,
                simple_code: simpleCode, // 保存短码到课程
                is_active: true,
                reactions: { happy: 0, amazing: 0, confused: 0, question: 0 }
            };

            // 3. 同时写入 courses 和 active_codes
            const updates = {};
            updates['/courses/' + newCourseId] = newCourseData;
            updates['/active_codes/' + simpleCode] = newCourseId; // 建立映射：8848 -> -OiM...

            db.ref().update(updates).then(() => {
                closeModal(); 
                e.target.reset(); 
                document.getElementById('input-date').valueAsDate = new Date();
            }).catch(err => alert("创建失败: " + err.message));
        }

        // 🎲 生成不重复的随机码
        async function generateUniqueCode() {
            let code = "";
            let isUnique = false;
            while (!isUnique) {
                code = Math.floor(1000 + Math.random() * 9000).toString(); // 1000-9999
                const snapshot = await db.ref('active_codes/' + code).once('value');
                if (!snapshot.exists()) isUnique = true;
            }
            return code;
        }

        // 删除课程 (同时删除 active_codes 里的记录)
        function deleteCourse(courseId) {
            if(confirm("确定要删除这个课程吗？删除后数据无法恢复。")) {
                db.ref('courses/' + courseId).once('value').then(snapshot => {
                    const course = snapshot.val();
                    const updates = {};
                    updates['/courses/' + courseId] = null;
                    if (course && course.simple_code) {
                        updates['/active_codes/' + course.simple_code] = null;
                    }
                    return db.ref().update(updates);
                }).catch(err => alert("删除失败: " + err.message));
            }
        }

        // UI 控制
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            document.getElementById('input-date').valueAsDate = today;
            document.getElementById('input-time').value = `${String(today.getHours()).padStart(2,'0')}:${String(today.getMinutes()).padStart(2,'0')}`;
        });

        function openModal() {
            const modal = document.getElementById('add-modal');
            const panel = document.getElementById('modal-panel');
            modal.classList.remove('hidden');
            setTimeout(() => { panel.classList.remove('scale-95', 'opacity-0'); panel.classList.add('scale-100', 'opacity-100'); }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('add-modal');
            const panel = document.getElementById('modal-panel');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }
    </script>
</body>
</html>