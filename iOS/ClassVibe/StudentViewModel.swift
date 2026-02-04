import SwiftUI
import FirebaseDatabase
import FirebaseAuth // ⚠️ 核心：用于身份验证
import AVFoundation // 用于震动反馈

class StudentViewModel: ObservableObject {
    // MARK: - Published 属性 (UI 会监听这些属性的变化)
    
    
    // --- 用户信息 ---
    @Published var studentName: String = ""
    @Published var vibePoints: Int = 100 // 初始送100分
    @Published var inventory: [RewardItem] = [] // 背包
    
    // --- 房间/课程信息 ---
    @Published var roomCode: String = "" // 输入的4位码
    @Published var courses: [Course] = []
    @Published var currentCourseId: String? = nil
    
    // --- 游戏 & 课堂状态 ---
    @Published var gameMode: GameMode = .normal
    @Published var myTeam: Team = .none
    
    // 全班反应数据 (用于驱动馒头表情)
    @Published var classReactions: [String: Int] = ["happy":0, "amazing":0, "confused":0, "question":0]
    
    // --- UI 反馈 ---
    @Published var showReactionSuccess: String? = nil
    @Published var showFeverEffect: Bool = false
    @Published var errorMessage: String? = nil // 错误提示信息
    
    // MARK: - 内部属性

        
        // ⚠️ 新增：记录当前课程是否已经参与过互动（用于控制只加一次分）
        private var hasParticipatedInCurrentSession: Bool = false

        // 模拟模式标记
        private var isMock1: Bool = false

    // 模拟模式标记 (用于 Preview 防止崩溃)
    private var isMock: Bool = false
    
    // 懒加载数据库引用
//    private lazy var dbRef: DatabaseReference = {
//        return Database.database().reference()
//    }()
    
    private lazy var dbRef: DatabaseReference = {
        // ⚠️ 这里填你刚才发出来的那个具体的 URL
        let url = "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/"
        return Database.database(url: url).reference()
    }()
    
    // MARK: - 初始化
    
    init(isMock: Bool = false) {
        self.isMock = isMock
        if isMock {
            // 模拟一些初始数据供预览使用
            self.courses = [
                Course(id: "mock1", title: "iOS 开发基础 (预览)", teacherName: "ID: 8888", isActive: true)
            ]
            self.inventory = [RewardItem(name: "预览券", rarity: "SR", icon: "✨")]
            self.classReactions = ["happy": 10, "amazing": 5, "confused": 2]
        }
    }
    
    
    
    
    
    
    func loginAndJoinRoom(completion: @escaping (Bool) -> Void) {
            if isMock {
                self.enterCourse(id: "mock_course_id")
                completion(true)
                return
            }
            
            guard !studentName.isEmpty else {
                self.errorMessage = "名前を入力してください"
                completion(false)
                return
            }
            guard roomCode.count == 4 else {
                self.errorMessage = "4桁のコードを入力してください"
                completion(false)
                return
            }
            
            print("🔍 1. 开始登录流程... URL: \(dbRef.database.reference().url)")
            
            Auth.auth().signInAnonymously { [weak self] result, error in
                guard let self = self else { return }
                
                if let error = error {
                    print("❌ 登录失败: \(error.localizedDescription)")
                    self.errorMessage = "登录失败: \(error.localizedDescription)"
                    completion(false)
                    return
                }
                
                print("✅ 2. 匿名登录成功 UID: \(result?.user.uid ?? "无")")
                
                // 登录成功后，去查找
                print("🔍 3. 正在去 active_codes 查找: \(self.roomCode)")
                
                self.dbRef.child("active_codes").child(self.roomCode).observeSingleEvent(of: .value) { snapshot in
                    print("📦 4. 数据库返回 Snapshot: \(snapshot)")
                    
                    if let courseId = snapshot.value as? String {
                        print("✅ 5. 找到课程 ID: \(courseId)")
                        
                        // 写入学生信息
                        let studentInfo = ["name": self.studentName]
                        self.dbRef.child("courses").child(courseId).child("active_students").child(result!.user.uid).setValue(studentInfo) { err, _ in
                            if let err = err {
                                print("❌ 写入名字失败: \(err.localizedDescription)")
                            } else {
                                print("✅ 名字写入成功")
                            }
                        }
                        
                        self.enterCourse(id: courseId)
                        completion(true)
                    } else {
                        print("❌ 没找到对应课程码，Snapshot value 是: \(String(describing: snapshot.value))")
                        self.errorMessage = "無効な参加コードです"
                        completion(false)
                    }
                } withCancel: { error in
                    print("❌ 数据库读取权限被拒绝或取消: \(error.localizedDescription)")
                }
            }
        }
    
    
    
    
    
    
    // 辅助：查找房间并登记
    private func findRoomAndEnter(userId: String, completion: @escaping (Bool) -> Void) {
        print("正在查找课程码: \(roomCode)")
        
        // 去 active_codes 表里查询映射关系
        dbRef.child("active_codes").child(roomCode).observeSingleEvent(of: .value) { [weak self] snapshot in
            guard let self = self else { return }
            
            if let courseId = snapshot.value as? String {
                // ✅ 找到了！
                print("找到课程 ID: \(courseId), 准备进入...")
                
                // 📝 登记入室 (为了让 Web 端人数 +1)
                // 路径: courses/{id}/active_students/{uid} = {name: "王同学"}
                let studentInfo = ["name": self.studentName]
                self.dbRef.child("courses").child(courseId).child("active_students").child(userId).setValue(studentInfo)
                
                // 正式进入
                self.enterCourse(id: courseId)
                completion(true)
            } else {
                // ❌ 没找到
                print("无效的课程码")
                self.errorMessage = "無効な参加コードです"
                completion(false)
            }
        }
    }
    
    // MARK: - 课程逻辑
    
    // 进入特定课程 (建立监听)
    func enterCourse(id: String) {
        // 切换到主线程更新 UI
        DispatchQueue.main.async {
            self.currentCourseId = id
            
            self.hasParticipatedInCurrentSession = false
            self.myTeam = Bool.random() ? .red : .blue // 随机分红蓝队
            self.errorMessage = nil
        }
        
        if isMock { return }
        
        // A. 监听该课程的反应数据 (为了让手机上的馒头也能动起来)
        dbRef.child("courses").child(id).child("reactions").observe(.value) { snapshot in
            if let value = snapshot.value as? [String: Int] {
                DispatchQueue.main.async {
                    self.classReactions = value
                }
            } else {
                DispatchQueue.main.async {
                    self.classReactions = ["happy":0, "amazing":0, "confused":0, "question":0]
                }
            }
        }
        
        // B. 监听游戏模式 (Fever/Battle)
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
    }
    
    // 监听所有课程列表 (备用功能，现在主要用直连)
    func listenToCourses() {
        if isMock { return }
        
        dbRef.child("courses").observe(.value) { snapshot in
            var newCourses: [Course] = []
            for child in snapshot.children {
                if let snapshot = child as? DataSnapshot,
                   let value = snapshot.value as? [String: Any] {
                    let title = value["title"] as? String ?? "未知课程"
                    let teacherId = value["teacher_id"] as? String ?? ""
                    let isActive = value["is_active"] as? Bool ?? false
                    
                    let course = Course(id: snapshot.key, title: title, teacherName: "ID: \(teacherId.prefix(4))", isActive: isActive)
                    newCourses.append(course)
                }
            }
            self.courses = newCourses.sorted(by: { $0.id > $1.id })
        }
    }
    
    // MARK: - 互动发送逻辑
    
    func sendReaction(type: String) {
        // 1. 震动反馈
        let generator = UIImpactFeedbackGenerator(style: (gameMode == .fever) ? .heavy : .medium)
        generator.impactOccurred()
        
        // 2. 数据库写入
        if !isMock1, let courseId = currentCourseId {
            // 路径：courses / {ID} / reactions / {type}
            let reactionPath = dbRef.child("courses").child(courseId).child("reactions").child(type)
            reactionPath.setValue(ServerValue.increment(1))
            
            // 如果是对战模式，计入队伍分
            if gameMode == .battle {
                let teamKey = (myTeam == .red) ? "red_score" : "blue_score"
                dbRef.child("courses").child(courseId).child("battle").child(teamKey).setValue(ServerValue.increment(1))
            }
        } else if isMock1 {
            self.classReactions[type, default: 0] += 1
        }
        
        
        
        
        // 3. 增加个人积分
        if !hasParticipatedInCurrentSession {
                    vibePoints += 1
                    hasParticipatedInCurrentSession = true // 标记为已领取
                    print("🎉 首次互动，积分 +1！当前积分: \(vibePoints)")
                } else {
                    print("👀 本堂课已领过积分，不再增加。")
                }
        
        // 4. 触发 UI 动画
        showReactionSuccess = type
        if gameMode == .fever { showFeverEffect.toggle() }
        
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) {
            self.showReactionSuccess = nil
        }
    }
    
    // MARK: - 扭蛋系统逻辑
    
    func spinGacha() -> RewardItem? {
        let cost = 50
        if vibePoints < cost { return nil }
        
        vibePoints -= cost
        
        let roll = Int.random(in: 1...100)
        let item: RewardItem
        
        if roll <= 2 {
            item = RewardItem(name: "免作业券", rarity: "SSR", icon: "👑")
        } else if roll <= 10 {
            item = RewardItem(name: "加分券 (+5分)", rarity: "SR", icon: "🔥")
        } else if roll <= 40 {
            item = RewardItem(name: "优先提问权", rarity: "R", icon: "🙋")
        } else {
            item = RewardItem(name: "电子贴纸", rarity: "N", icon: "🍀")
        }
        
        inventory.append(item)
        return item
    }
    
    func debugToggleMode() {
        if gameMode == .normal { gameMode = .fever }
        else if gameMode == .fever { gameMode = .battle }
        else { gameMode = .normal }
    }

    
    // 在 StudentViewModel.swift 里找到这个变量
        var currentPetMood: PetMood {
            // 获取各种反应的数量
            let difficult = classReactions["difficult"] ?? 0
            let interesting = classReactions["interesting"] ?? 0
            let understood = classReactions["understood"] ?? 0
            
            // 🛑 强制逻辑修改：
            // 只要按了一下 "difficult" (難しい)，马上切换成 panic (大哭)
            // 这样就能确保你的 GIF 一定会被显示出来！
            if difficult > 0 {
                return .panic
            }
            
            // 其他逻辑保持不变
            if interesting > understood {
                return .superHappy
            } else if understood > 0 {
                return .happy
            } else {
                return .sleepy // 默认状态
            }
        }
    
    
    
}
