<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - ClassVibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; background-color: #F8FAFC; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .gradient-text { background-clip: text; -webkit-background-clip: text; color: transparent; background-image: linear-gradient(135deg, #2563EB 0%, #4F46E5 100%); }
        .sidebar-link.active { background-color: #Eff6FF; color: #2563EB; border-right: 3px solid #2563EB; }
        
        /* Animations */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="text-slate-800 antialiased invisible" id="main-body">

    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 shadow-[4px_0_24px_rgba(0,0,0,0.02)] transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col">
        <!-- Brand -->
        <div class="h-20 flex items-center px-8 border-b border-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-blue-500/30">CV</div>
                <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-600">ClassVibe</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-6 space-y-2 overflow-y-auto">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 px-3">Main Menu</div>
            
            <a href="#" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all group">
                <i class="fas fa-th-large w-5 text-center group-hover:scale-110 transition-transform"></i>
                ダッシュボード
            </a>
            <a href="teacher_history.php" class="sidebar-link text-slate-500 hover:text-slate-900 hover:bg-slate-50 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all group">
                <i class="fas fa-history w-5 text-center group-hover:scale-110 transition-transform"></i>
                履歴・アーカイブ
            </a>
            <!-- Placeholder links for realism -->

            <a href="javascript:void(0)" onclick="openSettingsModal()" class="sidebar-link text-slate-500 hover:text-slate-900 hover:bg-slate-50 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all group">
                <i class="fas fa-cog w-5 text-center"></i>
                設定
            </a>
        </nav>

        <!-- User Profile (Bottom) -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-white hover:shadow-sm transition-all cursor-pointer group relative">
                <img id="sidebar-avatar" src="" class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover bg-slate-200">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate" id="sidebar-name">Loading...</p>
                    <p class="text-xs text-slate-500">Teacher Account</p>
                </div>
                <button onclick="handleLogout()" class="p-2 text-slate-400 hover:text-red-500 transition-colors" title="ログアウト">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="lg:ml-72 min-h-screen flex flex-col transition-all duration-300">
        
        <!-- Header -->
        <header class="h-20 px-8 flex items-center justify-between bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-200/50">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-bold text-slate-800 hidden sm:block">コース管理</h1>
            </div>

            <div class="flex items-center gap-6">
                <!-- Date Display -->
                <div class="hidden md:flex flex-col items-end mr-4">
                    <span class="text-xs text-slate-400 font-medium uppercase tracking-wider" id="current-day">Loading...</span>
                    <span class="text-sm font-bold text-slate-700" id="current-date">Loading...</span>
                </div>
                

            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-6 lg:p-10 max-w-7xl mx-auto w-full">
            
            <!-- Welcome/Stats Section -->
            <div class="mb-10 animate-fade-in">
                <h2 class="text-2xl font-bold text-slate-800 mb-6">Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Stat Card 1 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10">
                            <p class="text-sm text-slate-500 font-medium mb-1">担当コース数</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-slate-800" id="stat-course-count">-</span>
                                <span class="text-xs text-green-500 font-medium bg-green-50 px-2 py-0.5 rounded-full"><i class="fas fa-arrow-up text-[10px] mr-1"></i>Active</span>
                            </div>
                        </div>
                        <div class="absolute bottom-6 right-6 text-blue-500 text-2xl opacity-20"><i class="fas fa-layer-group"></i></div>
                    </div>



                    <!-- CTA Create Card -->
                    <div onclick="openModal()" class="bg-gradient-to-br from-blue-600 to-indigo-600 p-6 rounded-2xl shadow-lg shadow-blue-500/20 text-white cursor-pointer hover:shadow-blue-500/30 hover:scale-[1.02] transition-all flex flex-col justify-center items-center text-center group">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mb-3 group-hover:rotate-90 transition-transform duration-500">
                            <i class="fas fa-plus text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg">新規コースを作成</h3>
                        <p class="text-blue-100 text-sm mt-1">新しいクラスルームを立ち上げる</p>
                    </div>
                </div>
            </div>

            <!-- Course List Section -->
            <div class="animate-fade-in delay-100">
                <div class="flex flex-col sm:flex-row justify-between items-end sm:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">My Courses</h2>
                        <p class="text-sm text-slate-500 mt-1">管理中のすべてのコース</p>
                    </div>
                    <!-- Filter/Search Placeholder -->
                    <div class="flex gap-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" id="course-search" placeholder="コースを検索..." oninput="filterCourses()" class="pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-48 sm:w-64 transition-all hover:border-slate-300">
                        </div>
                    </div>
                </div>

                <div id="loading-indicator" class="py-12 flex justify-center hidden">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                </div>

                <div id="course-list" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-12">
                    <!-- Cards will be injected here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form (Modernized) -->
    <div id="add-modal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modal-backdrop" onclick="closeModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div id="modal-panel" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 scale-95">
                    
                    <div class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-100">
                        <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">新規コース作成</h3>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="add-course-form" onsubmit="handleAddCourse(event)">
                        <div class="px-6 py-6 space-y-5">
                            <!-- Title Input -->
                            <div>
                                <label for="input-title" class="block text-sm font-medium leading-6 text-slate-900 mb-1">クラス名称 <span class="text-red-500">*</span></label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <i class="fas fa-chalkboard text-slate-400"></i>
                                    </div>
                                    <input type="text" id="input-title" required class="block w-full rounded-lg border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all" placeholder="例：Webプログラミング演習">
                                </div>
                            </div>

                            <!-- Year/Term Input -->
                            <div>
                                <label class="block text-sm font-medium leading-6 text-slate-900 mb-1">実施時期</label>
                                <div class="flex gap-3">
                                    <div class="relative w-2/3">
                                        <select id="input-year-val" class="appearance-none block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 bg-slate-50 hover:bg-white transition-colors">
                                            <!-- Dynamically populated -->
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                            <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                                        </div>
                                    </div>
                                    <div class="relative w-1/3">
                                        <select id="input-term-val" class="appearance-none block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 bg-slate-50 hover:bg-white transition-colors">
                                            <option value="前期">前期</option>
                                            <option value="後期">後期</option>
                                            <option value="通年">通年</option>
                                            <option value="その他">その他</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                            <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto transition-all transform active:scale-95">作成する</button>
                            <button type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition-all" onclick="closeModal()">キャンセル</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="fixed inset-0 z-[100] hidden" aria-labelledby="settings-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0" id="settings-backdrop" onclick="closeSettingsModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div id="settings-panel" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md opacity-0 translate-y-4 scale-95">
                    
                    <div class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-100">
                        <h3 class="text-lg font-bold leading-6 text-slate-900" id="settings-title">アカウント設定</h3>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="closeSettingsModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="settings-form" onsubmit="handleUpdateProfile(event)">
                        <div class="px-6 py-6 space-y-5">
                            <div>
                                <label for="input-display-name" class="block text-sm font-medium leading-6 text-slate-900 mb-1">表示名</label>
                                <input type="text" id="input-display-name" class="block w-full rounded-lg border-0 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all">
                            </div>
                            <div>
                                <label for="input-photo-url" class="block text-sm font-medium leading-6 text-slate-900 mb-1">アイコンURL (任意)</label>
                                <input type="url" id="input-photo-url" class="block w-full rounded-lg border-0 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition-all" placeholder="https://...">
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto transition-all">保存する</button>
                            <button type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition-all" onclick="closeSettingsModal()">キャンセル</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // --- 1. System Config & Helper ---
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

        // Date Display
        function updateDate() {
            const now = new Date();
            const days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
            const dateStr = now.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('current-day').innerText = days[now.getDay()];
            document.getElementById('current-date').innerText = dateStr;
        }
        updateDate();

        // --- 2. Auth Flow ---
        auth.onAuthStateChanged((user) => {
            if (user) {
                CURRENT_TEACHER_ID = user.uid;
                updateUserProfile(user);
                document.getElementById('main-body').classList.remove('invisible');
                loadCourses();
                initYearDropdown();
            } else {
                window.location.href = "login.php";
            }
        });

        function updateUserProfile(user) {
            const displayName = user.displayName || user.email.split('@')[0];
            document.getElementById('sidebar-name').innerText = displayName;
            const avatars = [document.getElementById('sidebar-avatar')];
            
            if (user.photoURL) {
                avatars.forEach(img => {
                    img.src = user.photoURL;
                    img.classList.remove('hidden');
                });
            } else {
                // Default Avatar if none
                avatars.forEach(img => img.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=random`);
            }
        }

        function handleLogout() {
            if(confirm("ログアウトしてもよろしいですか？")) {
                auth.signOut().then(() => window.location.href = "login.php");
            }
        }

        function initYearDropdown() {
            const yearSelect = document.getElementById('input-year-val');
            const currentYear = new Date().getFullYear();
            yearSelect.innerHTML = '';
            for (let i = -1; i <= 3; i++) {
                const y = currentYear + i;
                const opt = document.createElement('option');
                opt.value = y;
                opt.text = y + "年度";
                if (y === currentYear) opt.selected = true;
                yearSelect.appendChild(opt);
            }
        }

        // --- 3. Core Logic: Courses ---
        function loadCourses() {
            const loading = document.getElementById('loading-indicator');
            const listContainer = document.getElementById('course-list');
            loading.classList.remove('hidden');
            
            db.ref('courses').on('value', (snapshot) => {
                loading.classList.add('hidden');
                listContainer.innerHTML = ''; // Clear all
                const data = snapshot.val();
                let myCourseCount = 0;

                if (data) {
                    const coursesArray = Object.entries(data).map(([key, value]) => ({ id: key, ...value }));
                    coursesArray.reverse(); // Newest first

                    coursesArray.forEach(course => {
                        const title = course.title || "";
                        if (course.deleted === true || !title || title === "名称未設定") return;
                        if (course.teacher_id === CURRENT_TEACHER_ID) {
                            myCourseCount++;
                            const card = createCourseCard(course);
                            listContainer.insertAdjacentHTML('beforeend', card);
                        }
                    });
                }
                
                // Update stats
                document.getElementById('stat-course-count').innerText = myCourseCount;
                // document.getElementById('course-count').innerText = myCourseCount; // If we had it in summary
            });
        }



        // --- Search Functionality ---
        function filterCourses() {
            const searchText = document.getElementById('course-search').value.toLowerCase();
            const cards = document.querySelectorAll('.course-card');
            
            cards.forEach(card => {
                const title = card.getAttribute('data-title').toLowerCase();
                if (title.includes(searchText)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        function createCourseCard(course) {
            // Generates a consistent gradient based on char code of ID
            const gradients = [
                'from-blue-500 to-cyan-400', 
                'from-violet-500 to-fuchsia-400',
                'from-emerald-500 to-teal-400',
                'from-orange-400 to-amber-400',
                'from-pink-500 to-rose-400'
            ];
            const colorIndex = course.id.charCodeAt(course.id.length - 1) % gradients.length;
            const gradientClass = gradients[colorIndex];
            
            const title = course.title || "名称未設定";
            const dateInfo = course.date || "年度未設定";
            const isActive = !!course.is_active;
            const statusBadge = isActive 
                ? `<span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20"><span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>授業中</span>`
                : `<span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">待機中</span>`;

            const simpleCode = course.simple_code || "---";

            return `
            <div class="course-card bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col group h-full" data-title="${title}">
                <div class="h-32 bg-gradient-to-r ${gradientClass} relative">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute top-4 right-4 flex gap-2">
                        <button onclick="deleteCourse('${course.id}')" class="bg-white/20 hover:bg-white/40 text-white p-2 rounded-lg backdrop-blur-sm transition-colors text-sm" title="削除">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <div class="text-xs font-medium bg-white/20 backdrop-blur-md px-2 py-1 rounded-md inline-block mb-1 border border-white/10">${dateInfo}</div>
                    </div>
                </div>
                
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        ${statusBadge}
                        <div class="text-xs font-mono text-slate-400 bg-slate-50 px-2 py-1 rounded border border-slate-200">ID: ${simpleCode}</div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-slate-800 mb-2 line-clamp-2 leading-tight" title="${title}">${title}</h3>
                    
                    <div class="mt-auto pt-6 flex items-center justify-between">
                        <div class="flex -space-x-2 overflow-hidden">
                             <!-- Fake student avatars for visual flair -->
                             <div class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-slate-200 flex items-center justify-center text-xs text-slate-500"><i class="fas fa-user"></i></div>
                             <div class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-slate-100 flex items-center justify-center text-xs text-slate-400 text-[9px]">+</div>
                        </div>
                        
                        <a href="index.php?courseId=${course.id}" class="bg-slate-900 text-white hover:bg-blue-600 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-lg shadow-slate-200 flex items-center gap-2 group-hover:pl-5 transition-all">
                            入室 <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>`;
        }

        async function handleAddCourse(e) {
            e.preventDefault();
            if (!CURRENT_TEACHER_ID) return;

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = "作成中...";

            try {
                const title = document.getElementById('input-title').value;
                const yearVal = document.getElementById('input-year-val').value;
                const termVal = document.getElementById('input-term-val').value;
                const fullYearString = `${yearVal}年度 ${termVal}`;
                const simpleCode = await generateUniqueCode();
                
                const newCourseRef = db.ref('courses').push();
                const newCourseId = newCourseRef.key;

                const newCourseData = {
                    title,
                    date: fullYearString,
                    teacher_id: CURRENT_TEACHER_ID,
                    simple_code: simpleCode,
                    is_active: false,
                    created_at: firebase.database.ServerValue.TIMESTAMP,
                    reactions: { happy: 0, amazing: 0, confused: 0, question: 0 }
                };

                const updates = {};
                updates['/courses/' + newCourseId] = newCourseData;
                updates['/active_codes/' + simpleCode] = newCourseId;

                await db.ref().update(updates);
                
                closeModal();
                e.target.reset();
            } catch (err) {
                alert("エラー: " + err.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        }

        async function generateUniqueCode() {
            let code = "";
            let isUnique = false;
            while (!isUnique) {
                code = Math.floor(1000 + Math.random() * 9000).toString();
                const snapshot = await db.ref('active_codes/' + code).once('value');
                if (!snapshot.exists()) isUnique = true;
            }
            return code;
        }

        function deleteCourse(courseId) {
            if(confirm("このコースを削除しますか？")) {
                db.ref('courses/' + courseId).once('value').then(snapshot => {
                    const course = snapshot.val();
                    const updates = {};
                    updates['/courses/' + courseId] = null;
                    if (course && course.simple_code) {
                        updates['/active_codes/' + course.simple_code] = null;
                    }
                    db.ref().update(updates);
                });
            }
        }

        // --- 4. UI Interaction ---
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            const isClosed = sidebar.classList.contains('-translate-x-full');
            
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        function openModal() {
            const modal = document.getElementById('add-modal');
            const backdrop = document.getElementById('modal-backdrop');
            const panel = document.getElementById('modal-panel');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
                panel.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            }, 10);
            document.getElementById('input-title').focus();
        }

        function closeModal() {
            const modal = document.getElementById('add-modal');
            const backdrop = document.getElementById('modal-backdrop');
            const panel = document.getElementById('modal-panel');
            
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            panel.classList.add('opacity-0', 'translate-y-4', 'scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        // Add escape key listener for all modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                closeSettingsModal();
            }
        });

        // --- Settings Modal ---
        function openSettingsModal() {
            const user = auth.currentUser;
            if (user) {
                document.getElementById('input-display-name').value = user.displayName || "";
                document.getElementById('input-photo-url').value = user.photoURL || "";
            }

            const modal = document.getElementById('settings-modal');
            const backdrop = document.getElementById('settings-backdrop');
            const panel = document.getElementById('settings-panel');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
                panel.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            }, 10);
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settings-modal');
            const backdrop = document.getElementById('settings-backdrop');
            const panel = document.getElementById('settings-panel');
            
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            panel.classList.add('opacity-0', 'translate-y-4', 'scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        async function handleUpdateProfile(e) {
            e.preventDefault();
            const user = auth.currentUser;
            if (!user) return;

            const name = document.getElementById('input-display-name').value;
            const photo = document.getElementById('input-photo-url').value;
            const btn = e.target.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.innerText = "保存中...";

            try {
                await user.updateProfile({
                    displayName: name,
                    photoURL: photo
                });
                updateUserProfile(user); // Update UI immediately
                closeSettingsModal();
                alert("プロフィールを更新しました");
            } catch (error) {
                alert("更新に失敗しました: " + error.message);
            } finally {
                btn.disabled = false;
                btn.innerText = "保存する";
            }
        }

    </script>
</body>
</html>
