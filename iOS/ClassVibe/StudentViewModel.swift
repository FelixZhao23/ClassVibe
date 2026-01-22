import SwiftUI
import FirebaseDatabase
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
    
    // MARK: - 内部属性
    
    // 模拟模式标记 (用于 Preview 防止崩溃)
    private var isMock: Bool = false
    
    // 懒加载数据库引用
    private lazy var dbRef: DatabaseReference = {
        return Database.database().reference()
    }()
    
    // MARK: - 初始化
    
    init(isMock: Bool = false) {
        self.isMock = isMock
        if isMock {
            // 模拟一些初始数据供预览使用
            self.courses = [
                Course(id: "mock1", title: "iOS 开发基础 (预览)", teacherName: "ID: 8888", isActive: true),
                Course(id: "mock2", title: "Firebase 实战 (预览)", teacherName: "ID: 9999", isActive: false)
            ]
            self.inventory = [RewardItem(name: "预览券", rarity: "SR", icon: "✨")]
            self.classReactions = ["happy": 10, "amazing": 5, "confused": 2]
        }
    }
    
    // MARK: - 核心功能：加入房间
    
    // 1. 通过 4 位数字码查找真实课程 ID
    func joinRoomByCode(code: String, completion: @escaping (Bool) -> Void) {
        if isMock {
            // 模拟成功
            self.enterCourse(id: "mock_course_id")
            completion(true)
            return
        }
        
        print("正在查找课程码: \(code)")
        
        // 去 active_codes 表里查询映射关系
        dbRef.child("active_codes").child(code).observeSingleEvent(of: .value) { snapshot in
            if let courseId = snapshot.value as? String {
                // ✅ 找到了！获取真实的 Course ID
                print("找到课程 ID: \(courseId)")
                self.enterCourse(id: courseId)
                completion(true)
            } else {
                // ❌ 没找到
                print("无效的课程码")
                completion(false)
            }
        }
    }
    
    // 2. 进入特定课程 (建立监听)
    func enterCourse(id: String) {
        self.currentCourseId = id
        self.myTeam = Bool.random() ? .red : .blue // 随机分红蓝队
        
        if isMock { return }
        
        // A. 监听该课程的反应数据 (为了让手机上的馒头也能动起来)
        dbRef.child("courses").child(id).child("reactions").observe(.value) { snapshot in
            if let value = snapshot.value as? [String: Int] {
                self.classReactions = value
            } else {
                self.classReactions = ["happy":0, "amazing":0, "confused":0, "question":0]
            }
        }
        
        // B. 写入入室记录 (Web端统计人数用)
        // 路径: courses/{id}/active_students/{userId} = true
        let userId = getUserId()
        dbRef.child("courses").child(id).child("active_students").child(userId).setValue(true)
    }
    
    // 3. 监听所有课程列表 (备用功能，用于列表页)
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
        if !isMock, let courseId = currentCourseId {
            // 路径：courses / {ID} / reactions / {type}
            let reactionPath = dbRef.child("courses").child(courseId).child("reactions").child(type)
            reactionPath.setValue(ServerValue.increment(1))
            
            // 如果是对战模式，计入队伍分
            if gameMode == .battle {
                let teamKey = (myTeam == .red) ? "red_score" : "blue_score"
                dbRef.child("courses").child(courseId).child("battle").child(teamKey).setValue(ServerValue.increment(1))
            }
        } else if isMock {
            self.classReactions[type, default: 0] += 1
        }
        
        // 3. 增加个人积分
        let pointsEarned = (gameMode == .fever) ? 5 : 1
        vibePoints += pointsEarned
        
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
    
    // MARK: - 计算馒头心情 (Computed Property)
    var currentPetMood: PetMood {
        let happy = classReactions["happy"] ?? 0
        let amazing = classReactions["amazing"] ?? 0
        let confused = classReactions["confused"] ?? 0
        let question = classReactions["question"] ?? 0
        
        let total = happy + amazing + confused + question
        let positive = happy + amazing
        let negative = confused + question
        
        if total == 0 { return .sleepy }
        if gameMode == .fever { return .superHappy }
        
        if amazing > 0 && Double(amazing) >= Double(total) * 0.3 { return .superHappy }
        
        if Double(negative) > Double(positive) * 0.5 {
            if negative > 10 && question > confused { return .panic }
            return .confused
        }
        
        return .happy
    }
    
    // 辅助：获取设备唯一ID (用于统计人数)
    private func getUserId() -> String {
        let key = "classvibe_user_id"
        if let uuid = UserDefaults.standard.string(forKey: key) {
            return uuid
        } else {
            let uuid = UUID().uuidString
            UserDefaults.standard.set(uuid, forKey: key)
            return uuid
        }
    }
}
