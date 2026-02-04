<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業履歴 - ClassVibe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>

    <style>
        body { font-family: 'Noto Sans JP', sans-serif; background-color: #F3F4F6; }
        
        /* 日历选中样式 */
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
        /* 有数据的日期标记为浅蓝色 */
        .flatpickr-day.has-data {
            background: #dbeafe;
            border-color: #93c5fd;
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body class="text-gray-800 invisible" id="main-body">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-blue-500"></i> 授業履歴
            </h1>
            <a href="teacherbackground.php" class="text-gray-500 hover:text-blue-600 font-medium transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> コース一覧に戻る
            </a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-8">
        
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">検索条件</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">コースを選択</label>
                    <select id="course-select" onchange="handleCourseChange()" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50 hover:bg-white transition">
                        <option value="">読み込み中...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">実施日を選択</label>
                    <div class="relative">
                        <input type="text" id="date-picker" placeholder="先にコースを選んでください" disabled 
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-100 cursor-not-allowed text-gray-500">
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-400">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="history-result" class="hidden animate-fade-in">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1 font-bold" id="result-date-display">YYYY/MM/DD</p>
                    <h2 class="text-3xl font-black text-gray-800 leading-tight" id="session-topic">Topic Name</h2>
                </div>
                <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg font-mono font-bold text-sm border border-blue-100">
                    <i class="far fa-clock mr-2"></i><span id="session-time">00:00 - 00:00</span>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-700">リアクション集計結果</h3>
                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">参加人数: <span id="student-count" class="font-bold">0</span>名</span>
                </div>

                <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4" id="reactions-container">
                    </div>
            </div>

        </div>

        <div id="empty-state" class="text-center py-20 text-gray-400">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                <i class="fas fa-search"></i>
            </div>
            <p class="text-lg">コースと日付を選択して<br>過去の授業データを表示します。</p>
        </div>

    </main>

    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>

    <script>
        // 1. Firebase 配置 (必须与 index.php 一致)
        const firebaseConfig = {
            apiKey: "AIzaSyA-xTpcCeCzQpa1sOjgC6EFMPvAvQeX5jg",
            authDomain: "classvibe-2025.firebaseapp.com",
            databaseURL: "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "classvibe-2025", 
        };
        if (!firebase.apps.length) firebase.initializeApp(firebaseConfig);
        const db = firebase.database();

        let calendarInstance = null;
        let allSessionsCache = []; // 缓存当前课程的所有Session

        // 初始化
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('main-body').classList.remove('invisible');
            loadCourses();
        });

        // 1. 加载所有课程列表
        function loadCourses() {
            const select = document.getElementById('course-select');
            // 注意：这里读取的是 courses 节点，以获取课程名称
            db.ref('courses').once('value', snapshot => {
                select.innerHTML = '<option value="">コースを選択してください</option>';
                const data = snapshot.val();
                if (data) {
                    Object.entries(data).forEach(([key, val]) => {
                        const opt = document.createElement('option');
                        opt.value = key;
                        opt.text = val.name || val.title || "名称未設定";
                        select.appendChild(opt);
                    });
                } else {
                    select.innerHTML = '<option value="">コースが見つかりません</option>';
                }
            });
        }

        // 2. 选择课程后，加载该课程的历史记录
        function handleCourseChange() {
            const courseId = document.getElementById('course-select').value;
            const dateInput = document.getElementById('date-picker');
            
            // UI重置
            document.getElementById('history-result').classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');

            if (!courseId) {
                disableCalendar();
                return;
            }

            // 读取 class_sessions (新 index.php 保存的位置)
            // 按 class_id 筛选
            db.ref('class_sessions').orderByChild('class_id').equalTo(courseId)
              .once('value', snapshot => {
                  const sessions = snapshot.val();
                  allSessionsCache = []; // 清空缓存
                  const datesWithData = [];

                  if (sessions) {
                      Object.entries(sessions).forEach(([sid, sData]) => {
                          // 保存 ID 以便后续使用
                          sData.id = sid;
                          allSessionsCache.push(sData);
                          
                          // 提取日期部分 (YYYY-MM-DD)
                          if (sData.start_time) {
                              const dateStr = sData.start_time.split(' ')[0];
                              if (!datesWithData.includes(dateStr)) {
                                  datesWithData.push(dateStr);
                              }
                          }
                      });
                  }
                  
                  initCalendar(datesWithData);
              });
        }

        function disableCalendar() {
            const dateInput = document.getElementById('date-picker');
            dateInput.disabled = true;
            dateInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            dateInput.classList.remove('bg-white');
            dateInput.value = "";
            if (calendarInstance) calendarInstance.clear();
        }

        // 3. 初始化日历
        function initCalendar(availableDates) {
            const inputElement = document.getElementById('date-picker');
            inputElement.disabled = false;
            inputElement.classList.remove('bg-gray-100', 'cursor-not-allowed');
            inputElement.classList.add('bg-white');
            inputElement.placeholder = availableDates.length > 0 ? "日付を選択してください" : "履歴データがありません";

            if (calendarInstance) calendarInstance.destroy();

            calendarInstance = flatpickr(inputElement, {
                locale: "ja",
                dateFormat: "Y-m-d",
                enable: availableDates, // 只允许选择有数据的日期
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    // 给有数据的日期加特殊样式
                    const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                    if (availableDates.includes(dateStr)) {
                        dayElem.classList.add("has-data");
                    }
                },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        showSessionData(dateStr);
                    }
                }
            });
            
            // 如果只有一天有数据，自动选中它（可选体验优化）
            // if(availableDates.length === 1) calendarInstance.setDate(availableDates[0], true);
        }

        // 4. 显示具体某天的上课数据
        function showSessionData(dateStr) {
            // 找到匹配该日期的 Session (如果有多个，这里默认取最后一个/最新的)
            // 实际应用中如果一天多次上课，可能需要列表选择，这里简化为匹配日期
            const session = allSessionsCache.filter(s => s.start_time.startsWith(dateStr)).pop();

            if (!session) return;

            // 填充文本信息
            document.getElementById('result-date-display').innerText = dateStr;
            document.getElementById('session-topic').innerText = session.topic || "テーマなし";
            document.getElementById('student-count').innerText = session.student_count || 0;
            
            const startT = session.start_time ? session.start_time.split(' ')[1] : '--:--';
            const endT = session.end_time ? session.end_time.split(' ')[1] : '--:--';
            document.getElementById('session-time').innerText = `${startT} - ${endT}`;

            // 生成反应卡片 (匹配 index.php 的6种指标)
            const container = document.getElementById('reactions-container');
            container.innerHTML = '';

            const emojiConfig = {
                happy:    { icon: 'fa-smile',     color: 'text-green-500',  bg: 'bg-green-50',  label: 'わかった' },
                amazing:  { icon: 'fa-star',      color: 'text-pink-500',   bg: 'bg-pink-50',   label: 'すごい！' },
                confused: { icon: 'fa-dizzy',     color: 'text-yellow-500', bg: 'bg-yellow-50', label: '難しい' },
                question: { icon: 'fa-hand-paper',color: 'text-blue-500',   bg: 'bg-blue-50',   label: '質問あり' },
                sleepy:   { icon: 'fa-bed',       color: 'text-indigo-400', bg: 'bg-indigo-50', label: '眠い...' },
                bored:    { icon: 'fa-meh-blank', color: 'text-gray-400',   bg: 'bg-gray-100',  label: '暇' }
            };

            const reactions = session.reactions || {};

            Object.entries(emojiConfig).forEach(([key, conf]) => {
                const count = reactions[key] || 0;
                
                // 只有当 count > 0 时才高亮，否则灰色 (可选，这里全部显示)
                const cardHtml = `
                    <div class="${conf.bg} p-4 rounded-xl flex items-center justify-between border border-white shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm">
                                <i class="fas ${conf.icon} ${conf.color} text-lg"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-600">${conf.label}</span>
                        </div>
                        <span class="text-2xl font-black ${conf.color}">${count}</span>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });

            // 切换视图
            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('history-result').classList.remove('hidden');
        }
    </script>
</body>
</html>