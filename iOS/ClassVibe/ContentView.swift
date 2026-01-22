//
//  ContentView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

// ⚠️ 注意：
// 此文件现在仅作为“界面调度器” (Coordinator)。
// 它不再包含具体的页面 UI 代码 (那些已拆分到 LoginView.swift, ReactionPadView.swift 等)。
// 数据模型 (Course, GameMode 等) 定义在 Models.swift 中。

struct ContentView: View {
    // 1. 初始化核心逻辑控制器 (ViewModel)
    // 这里是整个 App 数据状态的源头
    @StateObject var viewModel: StudentViewModel
    
    // 2. 记录登录状态
    // true: 显示主界面 (TabView)
    // false: 显示登录页 (LoginView)
    @State private var isLoggedIn = false
    
    // 初始化方法
    // 允许从外部传入 viewModel，这是为了方便 Xcode Preview 使用模拟数据 (isMock: true)
    init(viewModel: StudentViewModel = StudentViewModel()) {
        _viewModel = StateObject(wrappedValue: viewModel)
    }
    
    var body: some View {
        // --- 核心逻辑：根据登录状态切换界面 ---
        
        if !isLoggedIn {
            // A. 如果没登录 -> 显示登录页
            // (LoginView 的代码在 LoginView.swift 文件里)
            LoginView(studentName: $viewModel.studentName, roomCode: $viewModel.roomCode, onJoin: {
                // 当用户点击“进入教室”按钮后的回调逻辑
                
                // 尝试通过 4 位码加入房间
                viewModel.joinRoomByCode(code: viewModel.roomCode) { success in
                    if success {
                        // 如果成功找到房间，切换状态，进入主页
                        isLoggedIn = true
                    } else {
                        // 如果失败 (例如码输错了)，可以在这里加个弹窗提示
                        //目前由 ViewModel 打印日志
                        print("加入失败：无效的验证码")
                    }
                }
            })
        } else {
            // B. 如果已登录 -> 显示主界面 (包含底部导航栏)
            TabView {
                // === Tab 1: 课堂 (核心功能) ===
                // 包含课程列表和互动面板
                ReactionPadView(viewModel: viewModel)
                    .tabItem {
                        Label("课堂", systemImage: "person.3.fill")
                    }
                
                // === Tab 2: 个人中心 (附加功能) ===
                // 包含积分、扭蛋机、背包
                GachaProfileView(viewModel: viewModel)
                    .tabItem {
                        Label("我的", systemImage: "person.crop.circle")
                    }
            }
            .accentColor(.blue) // 设置 Tab 选中颜色
        }
    }
}

// 预览设置
// 这里的 isMock: true 是防止预览崩溃的关键
struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView(viewModel: StudentViewModel(isMock: true))
    }
}
