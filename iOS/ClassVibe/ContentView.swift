//
//  ContentView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

// ⚠️ 这是 App 的“总指挥”文件
// 负责根据登录状态 (isLoggedIn) 切换显示的内容

struct ContentView: View {
    // 1. 初始化核心逻辑控制器
    @StateObject var viewModel: StudentViewModel
    
    // 2. 状态变量
    @State private var isLoggedIn = false
    @State private var showAlert = false // 用于显示错误弹窗
    
    // 初始化方法 (允许传入 mock viewModel 用于预览)
    init(viewModel: StudentViewModel = StudentViewModel()) {
        _viewModel = StateObject(wrappedValue: viewModel)
    }
    
    var body: some View {
        if !isLoggedIn {
            // --- A. 未登录状态：显示登录页 ---
            LoginView(
                studentName: $viewModel.studentName,
                roomCode: $viewModel.roomCode,
                onJoin: {
                    // 当用户点击“进入教室”时触发
                    viewModel.loginAndJoinRoom { success in
                        if success {
                            // 成功：切换到主界面
                            isLoggedIn = true
                        } else {
                            // 失败：显示错误弹窗
                            showAlert = true
                        }
                    }
                }
            )
            .alert(isPresented: $showAlert) {
                // 错误提示弹窗
                Alert(
                    title: Text("エラー"), // 错误
                    message: Text(viewModel.errorMessage ?? "接続に失敗しました"), // 连接失败
                    dismissButton: .default(Text("OK"))
                )
            }
        } else {
            // --- B. 已登录状态：显示主界面 ---
            TabView {
                // Tab 1: 教室 (互动面板)
                NavigationView {
                    ReactionPadView(viewModel: viewModel)
                }
                .tabItem {
                    Label("教室", systemImage: "person.3.fill")
                }
                
                // Tab 2: 个人中心 (扭蛋/背包)
                GachaProfileView(viewModel: viewModel)
                    .tabItem {
                        Label("マイページ", systemImage: "person.crop.circle")
                    }
            }
            .accentColor(.blue)
            // ✨ 监听退出事件：如果 currentCourseId 变为空 (用户在 ReactionPadView 点了退出)
            // 自动切回登录页面，让用户可以重新输码进入其他房间
            .onChange(of: viewModel.currentCourseId) { newValue in
                if newValue == nil {
                    isLoggedIn = false
                }
            }
        }
    }
}

// 预览设置
struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView(viewModel: StudentViewModel(isMock: true))
    }
}
