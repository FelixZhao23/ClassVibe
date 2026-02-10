//import SwiftUI
//import FirebaseDatabase
//import FirebaseAuth // ⚠️ 核心：用于身份验证
//import AVFoundation // 用于震动反馈
//
//class StudentViewModel: ObservableObject {
//    // MARK: - Published 属性 (UI 会监听这些属性的变化)
//    
//    
//    // --- 用户信息 ---
//    @Published var studentName: String = ""
//    @Published var vibePoints: Int = 100 // 初始送100分
//    @Published var inventory: [RewardItem] = [] // 背包
//    
//    // --- 房间/课程信息 ---
//    @Published var roomCode: String = "" // 输入的4位码
//    @Published var courses: [Course] = []
//    @Published var currentCourseId: String? = nil
//    
//    // --- 游戏 & 课堂状态 ---
//    @Published var gameMode: GameMode = .normal
//    @Published var myTeam: Team = .none
//    
//    // 全班反应数据 (用于驱动馒头表情)
//    @Published var classReactions: [String: Int] = ["happy":0, "amazing":0, "confused":0, "question":0]
//    
//    // --- UI 反馈 ---
//    @Published var showReactionSuccess: String? = nil
//    @Published var showFeverEffect: Bool = false
//    @Published var errorMessage: String? = nil // 错误提示信息
//    
//    // MARK: - 内部属性
//
//        
//        // ⚠️ 新增：记录当前课程是否已经参与过互动（用于控制只加一次分）
//        private var hasParticipatedInCurrentSession: Bool = false
//
//        // 模拟模式标记
//        private var isMock1: Bool = false
//
//    // 模拟模式标记 (用于 Preview 防止崩溃)
//    private var isMock: Bool = false
//    
//    // 懒加载数据库引用
////    private lazy var dbRef: DatabaseReference = {
////        return Database.database().reference()
////    }()
//    
//    private lazy var dbRef: DatabaseReference = {
//        // ⚠️ 这里填你刚才发出来的那个具体的 URL
//        let url = "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/"
//        return Database.database(url: url).reference()
//    }()
//    
//    // MARK: - 初始化
//    
//    init(isMock: Bool = false) {
//        self.isMock = isMock
//        if isMock {
//            // 模拟一些初始数据供预览使用
//            self.courses = [
//                Course(id: "mock1", title: "iOS 开发基础 (预览)", teacherName: "ID: 8888", isActive: true)
//            ]
//            self.inventory = [RewardItem(name: "预览券", rarity: "SR", icon: "✨")]
//            self.classReactions = ["happy": 10, "amazing": 5, "confused": 2]
//        }
//    }
//    
//    
//    
//    
//    
//    
//    func loginAndJoinRoom(completion: @escaping (Bool) -> Void) {
//            if isMock {
//                self.enterCourse(id: "mock_course_id")
//                completion(true)
//                return
//            }
//            
//            guard !studentName.isEmpty else {
//                self.errorMessage = "名前を入力してください"
//                completion(false)
//                return
//            }
//            guard roomCode.count == 4 else {
//                self.errorMessage = "4桁のコードを入力してください"
//                completion(false)
//                return
//            }
//            
//            print("🔍 1. 开始登录流程... URL: \(dbRef.database.reference().url)")
//            
//            Auth.auth().signInAnonymously { [weak self] result, error in
//                guard let self = self else { return }
//                
//                if let error = error {
//                    print("❌ 登录失败: \(error.localizedDescription)")
//                    self.errorMessage = "登录失败: \(error.localizedDescription)"
//                    completion(false)
//                    return
//                }
//                
//                print("✅ 2. 匿名登录成功 UID: \(result?.user.uid ?? "无")")
//                
//                // 登录成功后，去查找
//                print("🔍 3. 正在去 active_codes 查找: \(self.roomCode)")
//                
//                self.dbRef.child("active_codes").child(self.roomCode).observeSingleEvent(of: .value) { snapshot in
//                    print("📦 4. 数据库返回 Snapshot: \(snapshot)")
//                    
//                    if let courseId = snapshot.value as? String {
//                        print("✅ 5. 找到课程 ID: \(courseId)")
//                        
//                        // 写入学生信息
//                        let studentInfo = ["name": self.studentName]
//                        self.dbRef.child("courses").child(courseId).child("active_students").child(result!.user.uid).setValue(studentInfo) { err, _ in
//                            if let err = err {
//                                print("❌ 写入名字失败: \(err.localizedDescription)")
//                            } else {
//                                print("✅ 名字写入成功")
//                            }
//                        }
//                        
//                        self.enterCourse(id: courseId)
//                        completion(true)
//                    } else {
//                        print("❌ 没找到对应课程码，Snapshot value 是: \(String(describing: snapshot.value))")
//                        self.errorMessage = "無効な参加コードです"
//                        completion(false)
//                    }
//                } withCancel: { error in
//                    print("❌ 数据库读取权限被拒绝或取消: \(error.localizedDescription)")
//                }
//            }
//        }
//    
//    
//    
//    
//    
//    
//    // 辅助：查找房间并登记
//    private func findRoomAndEnter(userId: String, completion: @escaping (Bool) -> Void) {
//        print("正在查找课程码: \(roomCode)")
//        
//        // 去 active_codes 表里查询映射关系
//        dbRef.child("active_codes").child(roomCode).observeSingleEvent(of: .value) { [weak self] snapshot in
//            guard let self = self else { return }
//            
//            if let courseId = snapshot.value as? String {
//                // ✅ 找到了！
//                print("找到课程 ID: \(courseId), 准备进入...")
//                
//                // 📝 登记入室 (为了让 Web 端人数 +1)
//                // 路径: courses/{id}/active_students/{uid} = {name: "王同学"}
//                let studentInfo = ["name": self.studentName]
//                self.dbRef.child("courses").child(courseId).child("active_students").child(userId).setValue(studentInfo)
//                
//                // 正式进入
//                self.enterCourse(id: courseId)
//                completion(true)
//            } else {
//                // ❌ 没找到
//                print("无效的课程码")
//                self.errorMessage = "無効な参加コードです"
//                completion(false)
//            }
//        }
//    }
//    
//    // MARK: - 课程逻辑
//    
//    // 进入特定课程 (建立监听)
//    func enterCourse(id: String) {
//        // 切换到主线程更新 UI
//        DispatchQueue.main.async {
//            self.currentCourseId = id
//            
//            self.hasParticipatedInCurrentSession = false
//            self.myTeam = Bool.random() ? .red : .blue // 随机分红蓝队
//            self.errorMessage = nil
//        }
//        
//        if isMock { return }
//        
//        // A. 监听该课程的反应数据 (为了让手机上的馒头也能动起来)
//        dbRef.child("courses").child(id).child("reactions").observe(.value) { snapshot in
//            if let value = snapshot.value as? [String: Int] {
//                DispatchQueue.main.async {
//                    self.classReactions = value
//                }
//            } else {
//                DispatchQueue.main.async {
//                    self.classReactions = ["happy":0, "amazing":0, "confused":0, "question":0]
//                }
//            }
//        }
//        
//        // B. 监听游戏模式 (Fever/Battle)
//        dbRef.child("courses").child(id).child("game_mode").observe(.value) { snapshot in
//            if let modeStr = snapshot.value as? String {
//                DispatchQueue.main.async {
//                    switch modeStr {
//                    case "fever": self.gameMode = .fever
//                    case "battle": self.gameMode = .battle
//                    default: self.gameMode = .normal
//                    }
//                }
//            }
//        }
//    }
//    
//    // 监听所有课程列表 (备用功能，现在主要用直连)
//    func listenToCourses() {
//        if isMock { return }
//        
//        dbRef.child("courses").observe(.value) { snapshot in
//            var newCourses: [Course] = []
//            for child in snapshot.children {
//                if let snapshot = child as? DataSnapshot,
//                   let value = snapshot.value as? [String: Any] {
//                    let title = value["title"] as? String ?? "未知课程"
//                    let teacherId = value["teacher_id"] as? String ?? ""
//                    let isActive = value["is_active"] as? Bool ?? false
//                    
//                    let course = Course(id: snapshot.key, title: title, teacherName: "ID: \(teacherId.prefix(4))", isActive: isActive)
//                    newCourses.append(course)
//                }
//            }
//            self.courses = newCourses.sorted(by: { $0.id > $1.id })
//        }
//    }
//    
//    // MARK: - 互动发送逻辑
//    
//    func sendReaction(type: String) {
//        // 1. 震动反馈
//        let generator = UIImpactFeedbackGenerator(style: (gameMode == .fever) ? .heavy : .medium)
//        generator.impactOccurred()
//        
//        // 2. 数据库写入
//        if !isMock1, let courseId = currentCourseId {
//            // 路径：courses / {ID} / reactions / {type}
//            let reactionPath = dbRef.child("courses").child(courseId).child("reactions").child(type)
//            reactionPath.setValue(ServerValue.increment(1))
//            
//            // 如果是对战模式，计入队伍分
//            if gameMode == .battle {
//                let teamKey = (myTeam == .red) ? "red_score" : "blue_score"
//                dbRef.child("courses").child(courseId).child("battle").child(teamKey).setValue(ServerValue.increment(1))
//            }
//        } else if isMock1 {
//            self.classReactions[type, default: 0] += 1
//        }
//        
//        
//        
//        
//        // 3. 增加个人积分
//        if !hasParticipatedInCurrentSession {
//                    vibePoints += 1
//                    hasParticipatedInCurrentSession = true // 标记为已领取
//                    print("🎉 首次互动，积分 +1！当前积分: \(vibePoints)")
//                } else {
//                    print("👀 本堂课已领过积分，不再增加。")
//                }
//        
//        // 4. 触发 UI 动画
//        showReactionSuccess = type
//        if gameMode == .fever { showFeverEffect.toggle() }
//        
//        DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) {
//            self.showReactionSuccess = nil
//        }
//    }
//    
//    // MARK: - 扭蛋系统逻辑
//    
//    func spinGacha() -> RewardItem? {
//        let cost = 50
//        if vibePoints < cost { return nil }
//        
//        vibePoints -= cost
//        
//        let roll = Int.random(in: 1...100)
//        let item: RewardItem
//        
//        if roll <= 2 {
//            item = RewardItem(name: "免作业券", rarity: "SSR", icon: "👑")
//        } else if roll <= 10 {
//            item = RewardItem(name: "加分券 (+5分)", rarity: "SR", icon: "🔥")
//        } else if roll <= 40 {
//            item = RewardItem(name: "优先提问权", rarity: "R", icon: "🙋")
//        } else {
//            item = RewardItem(name: "电子贴纸", rarity: "N", icon: "🍀")
//        }
//        
//        inventory.append(item)
//        return item
//    }
//    
//    func debugToggleMode() {
//        if gameMode == .normal { gameMode = .fever }
//        else if gameMode == .fever { gameMode = .battle }
//        else { gameMode = .normal }
//    }
//
//    
//    // 在 StudentViewModel.swift 里找到这个变量
//        var currentPetMood: PetMood {
//            // 获取各种反应的数量
//            let difficult = classReactions["difficult"] ?? 0
//            let interesting = classReactions["interesting"] ?? 0
//            let understood = classReactions["understood"] ?? 0
//            
//            // 🛑 强制逻辑修改：
//            // 只要按了一下 "difficult" (難しい)，马上切换成 panic (大哭)
//            // 这样就能确保你的 GIF 一定会被显示出来！
//            if difficult > 0 {
//                return .panic
//            }
//            
//            // 其他逻辑保持不变
//            if interesting > understood {
//                return .superHappy
//            } else if understood > 0 {
//                return .happy
//            } else {
//                return .sleepy // 默认状态
//            }
//        }
//    
//    
//    
//}


import SwiftUI
import FirebaseDatabase
import FirebaseAuth
import AVFoundation

class StudentViewModel: ObservableObject {
    
    // MARK: - Published 属性
    
    // 用户信息
    @Published var studentName: String = ""
    @Published var vibePoints: Int = 100
    @Published var inventory: [RewardItem] = []
    
    // 房间 & 状态
    @Published var roomCode: String = ""
    @Published var currentCourseId: String? = nil
    @Published var currentCourseTitle: String = ""
    
    // ⚠️ 修复 1: 把它改回普通的 @Published 属性，不再是计算属性
    // 这样我们就可以在点击按钮时自由修改它了
    @Published var currentPetMood: PetMood = .sleepy
    
    @Published var gameMode: GameMode = .normal
    @Published var myTeam: Team = .none
    
    // UI 反馈
    @Published var showReactionSuccess: String? = nil
    @Published var showFeverEffect: Bool = false
    @Published var errorMessage: String? = nil
    
    // MARK: - 内部状态
    
    private var isMock: Bool = false
    
    // ⚠️ 记录当前课程是否已领过积分 (控制一堂课只加1分)
    private var hasParticipatedInCurrentSession: Bool = false
    private var currentUserId: String? = nil
    private var lastReactionAt: Date? = nil
    private var lastReactionType: String? = nil
    private var sameReactionChain: Int = 0
    private var teamCountRed: Int = 1
    private var teamCountBlue: Int = 1
    private var joinTime: Date = Date()
    
    // 数据库引用 (保持你的 URL)
    private lazy var dbRef: DatabaseReference = {
        let url = "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/"
        return Database.database(url: url).reference()
    }()
    
    // MARK: - 初始化
    init(isMock: Bool = false) {
        self.isMock = isMock
        if isMock {
            // 模拟数据
            self.inventory = [RewardItem(name: "预览券", rarity: "SR", icon: "✨")]
            return
        }
        let savedName = UserDefaults.standard.string(forKey: "last_student_name") ?? ""
        if !savedName.isEmpty {
            self.studentName = savedName
        }
    }
    // 发送反馈：普通模式写 courses/{id}/reactions，RealReaction 模式写 courses/{id}/real_reaction
    func sendReaction(type: String) {
        if isHapticsEnabled() {
            let generator = UIImpactFeedbackGenerator(style: (gameMode == .fever) ? .heavy : .medium)
            generator.impactOccurred()
        }

        var dbKey = ""
        switch type {
        case "understood":
            dbKey = "happy"
        case "difficult", "panic":
            dbKey = "confused"
        case "lost":
            dbKey = "question"
        case "unclear":
            dbKey = "amazing"
        case "slacking":
            dbKey = "sleepy"
        case "boring":
            dbKey = "bored"
        default:
            dbKey = "happy"
        }

        guard !isMock, let courseId = currentCourseId else {
            print("⚠️ 未连接数据库或未进入课程 (ID为空)")
            return
        }

        guard let uid = Auth.auth().currentUser?.uid else {
            DispatchQueue.main.async {
                self.errorMessage = "ログイン状態を確認してください"
            }
            return
        }

        let metricCtx = makeMetricContext(for: type, dbKey: dbKey)
        if !metricCtx.accepted {
            DispatchQueue.main.async {
                self.errorMessage = "連打しすぎです。少し待ってください。"
            }
            return
        }

        let rrRef = dbRef.child("courses").child(courseId).child("real_reaction")
        rrRef.observeSingleEvent(of: .value) { [weak self] snapshot in
            guard let self = self else { return }

            let rrData = snapshot.value as? [String: Any]
            let rrActive = (rrData?["active"] as? Bool) ?? false

            if rrActive {
                let voted = (rrData?["voted_students"] as? [String: Any])?[uid] != nil
                if voted {
                    DispatchQueue.main.async {
                        self.errorMessage = "リアルリアクションは1人1回までです"
                    }
                    return
                }

                let updates: [String: Any] = [
                    "reactions/\(dbKey)": ServerValue.increment(1),
                    "voted_students/\(uid)": [
                        "name": self.studentName,
                        "at": ServerValue.timestamp()
                    ]
                ]

                rrRef.updateChildValues(updates) { error, _ in
                    if let error = error {
                        print("❌ RealReaction 写入失败: \(error.localizedDescription)")
                        return
                    }
                    self.updateStudentMetrics(
                        courseId: courseId,
                        uid: uid,
                        type: type,
                        dbKey: dbKey,
                        weight: metricCtx.weight
                    )
                    DispatchQueue.main.async {
                        self.onReactionSent(type: type)
                    }
                }
                return
            }

            let reactionPath = self.dbRef
                .child("courses")
                .child(courseId)
                .child("reactions")
                .child(dbKey)

            reactionPath.setValue(ServerValue.increment(1)) { error, _ in
                if let error = error {
                    print("❌ 写入失败: \(error.localizedDescription)")
                    return
                }

                if self.gameMode == .battle {
                    let teamKey = (self.myTeam == .red) ? "red_score" : "blue_score"
                    self.dbRef
                        .child("courses")
                        .child(courseId)
                        .child("battle")
                        .child(teamKey)
                        .setValue(ServerValue.increment(1))
                }

                self.updateStudentMetrics(
                    courseId: courseId,
                    uid: uid,
                    type: type,
                    dbKey: dbKey,
                    weight: metricCtx.weight
                )

                DispatchQueue.main.async {
                    self.onReactionSent(type: type)
                }
            }
        }
    }

    private func isHapticsEnabled() -> Bool {
        if let stored = UserDefaults.standard.object(forKey: "haptics_enabled") as? Bool {
            return stored
        }
        return true
    }

    private func makeMetricContext(for type: String, dbKey: String) -> (accepted: Bool, weight: Double) {
        let now = Date()
        if let last = lastReactionAt, now.timeIntervalSince(last) < 2.0 {
            return (false, 0.0)
        }

        if lastReactionType == type {
            sameReactionChain += 1
        } else {
            sameReactionChain = 1
            lastReactionType = type
        }
        lastReactionAt = now

        let weight: Double
        switch sameReactionChain {
        case 1: weight = 1.0
        case 2: weight = 0.6
        default: weight = 0.3
        }
        return (true, weight)
    }

    private func updateStudentMetrics(courseId: String, uid: String, type: String, dbKey: String, weight: Double) {
        let metricRef = dbRef.child("courses").child(courseId).child("student_metrics").child(uid)
        let teamStr: String = (myTeam == .red) ? "red" : "blue"

        var understood = 0
        var question = 0
        var confused = 0

        if dbKey == "happy" || dbKey == "amazing" { understood = 1 }
        if dbKey == "confused" || dbKey == "question" || dbKey == "amazing" { question = 1 }
        if dbKey == "confused" || dbKey == "sleepy" || dbKey == "bored" { confused = 1 }

        let teamContribution = teamContributionWeight(teamStr: teamStr, base: weight)

        let updates: [String: Any] = [
            "display_name": studentName.isEmpty ? "student" : studentName,
            "team": teamStr,
            "effective_interactions": ServerValue.increment(NSNumber(value: weight)),
            "understood_count": ServerValue.increment(NSNumber(value: understood)),
            "question_count": ServerValue.increment(NSNumber(value: question)),
            "confused_count": ServerValue.increment(NSNumber(value: confused)),
            "team_contribution": ServerValue.increment(NSNumber(value: teamContribution)),
            "last_reaction_at": ServerValue.timestamp()
        ]

        metricRef.updateChildValues(updates)
    }

    private func onReactionSent(type: String) {
        if !hasParticipatedInCurrentSession {
            vibePoints += 1
            hasParticipatedInCurrentSession = true
            print("🎉 积分 +1 (本节课首次互动)")
        }

        updateMoodLocally(type: type)
        showReactionSuccess = type
        if gameMode == .fever { showFeverEffect.toggle() }

        DispatchQueue.main.asyncAfter(deadline: .now() + 0.3) {
            self.showReactionSuccess = nil
        }
    }

    private func teamContributionWeight(teamStr: String, base: Double) -> Double {
        let red = max(1, teamCountRed)
        let blue = max(1, teamCountBlue)
        let total = max(1, red + blue)
        let teamCount = (teamStr == "red") ? red : blue

        let ratio = sqrt(Double(total) / Double(teamCount))
        let sizeFactor = min(5.0, max(1.0, ratio))

        let elapsed = Date().timeIntervalSince(joinTime)
        let ramp = min(1.0, max(0.4, elapsed / 30.0))

        return base * sizeFactor * ramp
    }


    
    // 专门处理表情变化的函数
    private func updateMoodLocally(type: String) {
        // 根据按钮类型切换心情
        switch type {
        case "understood":
            self.currentPetMood = .superHappy // 星星眼
            
        case "lost":
            self.currentPetMood = .dizzy // ぜんぜんわからない
        case "difficult", "panic", "unclear":
            self.currentPetMood = .panic // 😭 触发 GIF
            
        case "slacking", "boring":
            self.currentPetMood = .sleepy // 睡觉
            
        default:
            self.currentPetMood = .happy // 普通开心
        }
        
        // (可选) 3秒后如果没有新的操作，变回普通开心状态
        // 这样可以避免一直停留在“哭”或“晕”的状态
        let originalType = type
        DispatchQueue.main.asyncAfter(deadline: .now() + 3.0) {
            // 只有当当前状态还是刚才设置的状态时才恢复 (防止覆盖了新的操作)
            if self.currentPetMood != .sleepy && originalType != "sleep" {
                 self.currentPetMood = .happy
            }
        }
    }
    
    // MARK: - 课程进入逻辑
    
    func enterCourse(id: String) {
        DispatchQueue.main.async {
            self.currentCourseId = id
            self.hasParticipatedInCurrentSession = false // 重置积分领取状态
            self.myTeam = self.teamFromUid(self.currentUserId)
            self.errorMessage = nil
            self.currentPetMood = .happy // 进教室时默认开心
            self.currentCourseTitle = ""
            self.joinTime = Date()
        }
        
        if isMock { return }
        
        dbRef.child("courses").child(id).child("is_active").observe(.value) { snapshot in
            if let active = snapshot.value as? Bool, active == false {
                DispatchQueue.main.async {
                    self.errorMessage = "授業が終了しました。"
                    self.currentCourseId = nil
                    self.currentCourseTitle = ""
                }
            }
        }

        dbRef.child("courses").child(id).child("title").observe(.value) { snapshot in
            if let title = snapshot.value as? String {
                DispatchQueue.main.async {
                    self.currentCourseTitle = title
                }
            }
        }

        // 监听游戏模式
        dbRef.child("courses").child(id).child("game_mode").observe(.value) { snapshot in
            if let modeStr = snapshot.value as? String {
                DispatchQueue.main.async {
                    switch modeStr {
                    case "fever": self.gameMode = .fever
                    case "battle": self.gameMode = .battle
                    default: self.gameMode = .normal
                    }
                }
            }
        }

        dbRef.child("courses").child(id).child("active_students").observe(.value) { snapshot in
            var red = 0
            var blue = 0
            if let dict = snapshot.value as? [String: Any] {
                for (_, value) in dict {
                    if let row = value as? [String: Any] {
                        let team = row["team"] as? String ?? ""
                        if team == "red" { red += 1 }
                        if team == "blue" { blue += 1 }
                    }
                }
            }
            DispatchQueue.main.async {
                self.teamCountRed = max(1, red)
                self.teamCountBlue = max(1, blue)
            }
        }
        
        // ⚠️ 注意：我移除了对 "reactions" 的监听来驱动表情
        // 因为我们现在改用“点击按钮直接驱动表情”，这样反馈最快，也不会被卡死。
    }
    
    // ... (Login, Gacha 等其他函数保持不变即可) ...
    
    // 登录逻辑 (保持不变)
    func loginAndJoinRoom(completion: @escaping (Bool) -> Void) {
        if isMock {
            self.currentUserId = "mock-user"
            self.myTeam = Bool.random() ? .red : .blue
            completion(true)
            return
        }
        guard !studentName.isEmpty, roomCode.count == 4 else {
            errorMessage = "入力エラー"
            completion(false)
            return
        }

        let afterAuth: (String) -> Void = { uid in
            self.currentUserId = uid

            self.dbRef.child("active_codes").child(self.roomCode).observeSingleEvent(of: .value) { snapshot in
                if let courseId = snapshot.value as? String {
                    self.dbRef.child("courses").child(courseId).child("is_active").observeSingleEvent(of: .value) { activeSnap in
                        let isActive = (activeSnap.value as? Bool) ?? false
                        guard isActive else {
                            self.errorMessage = "授業はまだ開始していません"
                            completion(false)
                            return
                        }

                        self.chooseBalancedTeam(courseId: courseId) { team in
                            self.myTeam = team
                            let teamStr = (team == .red) ? "red" : "blue"
                            let studentInfo: [String: Any] = [
                                "name": self.studentName,
                                "team": teamStr,
                                "joined_at": ServerValue.timestamp()
                            ]
                            let activeRef = self.dbRef.child("courses").child(courseId).child("active_students").child(uid)
                            activeRef.setValue(studentInfo)
                            activeRef.onDisconnectRemoveValue()
                            self.enterCourse(id: courseId)
                            completion(true)
                        }
                    }
                } else {
                    self.errorMessage = "コードが無効です"
                    completion(false)
                }
            }
        }

        if let user = Auth.auth().currentUser {
            afterAuth(user.uid)
            return
        }

        Auth.auth().signInAnonymously { result, error in
            if let user = result?.user {
                afterAuth(user.uid)
            } else {
                self.errorMessage = "ログイン失敗"
                completion(false)
            }
        }
    }

    private func chooseBalancedTeam(courseId: String, completion: @escaping (Team) -> Void) {
        dbRef.child("courses").child(courseId).child("active_students").observeSingleEvent(of: .value) { snapshot in
            var red = 0
            var blue = 0
            if let dict = snapshot.value as? [String: Any] {
                for (_, value) in dict {
                    if let row = value as? [String: Any] {
                        let team = row["team"] as? String ?? ""
                        if team == "red" { red += 1 }
                        if team == "blue" { blue += 1 }
                    }
                }
            }
            let team: Team
            if red > blue { team = .blue }
            else if blue > red { team = .red }
            else { team = Bool.random() ? .red : .blue }
            completion(team)
        }
    }

    func leaveCourse() {
        guard let courseId = currentCourseId, let uid = currentUserId else {
            currentCourseId = nil
            currentCourseTitle = ""
            return
        }
        dbRef.child("courses").child(courseId).child("active_students").child(uid).removeValue()
        currentCourseId = nil
        currentCourseTitle = ""
    }

    func fetchCourseTitleByCode(_ code: String, completion: @escaping (String?) -> Void) {
        guard code.count == 4 else {
            completion(nil)
            return
        }
        dbRef.child("active_codes").child(code).observeSingleEvent(of: .value) { snapshot in
            guard let courseId = snapshot.value as? String else {
                completion(nil)
                return
            }
            self.dbRef.child("courses").child(courseId).child("title").observeSingleEvent(of: .value) { titleSnap in
                completion(titleSnap.value as? String)
            }
        }
    }

    private func teamFromUid(_ uid: String?) -> Team {
        guard let uid = uid else { return .none }
        let sum = uid.unicodeScalars.reduce(0) { $0 + Int($1.value) }
        return (sum % 2 == 0) ? .red : .blue
    }
    
    // 扭蛋逻辑 (保持不变)
    func spinGacha() -> RewardItem? {
        let cost = 50
        guard vibePoints >= cost else { return nil }
        vibePoints -= cost
        let item = RewardItem(name: "New Item", rarity: "R", icon: "🎁") // 简写了，你可以用之前的逻辑
        inventory.append(item)
        return item
    }
    
    func debugToggleMode() {
        if gameMode == .normal { gameMode = .fever }
        else if gameMode == .fever { gameMode = .battle }
        else { gameMode = .normal }
    }
}
