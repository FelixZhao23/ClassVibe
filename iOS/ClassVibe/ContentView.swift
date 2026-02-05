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
                roomCode: $viewModel.roomCode
            ) {
                isLoggedIn = true
            }
            .alert(isPresented: $showAlert) {
                Alert(
                    title: Text("エラー"),
                    message: Text(viewModel.errorMessage ?? "接続に失敗しました"),
                    dismissButton: .default(Text("OK")))
            }
        }
        
        else {
            TabView {
                NavigationView {
                    ReactionPadView(viewModel: viewModel)
                }
                .tabItem {
                    Label("ホーム", systemImage: "house.fill")
                }
                
                GachaProfileView(viewModel: viewModel)
                    .tabItem {
                        Label("マイページ", systemImage: "person.crop.circle")
                    }
            }
            .accentColor(.blue)
        }
    }
    
    
    // 预览设置
    struct ContentView_Previews: PreviewProvider {
        static var previews: some View {
            ContentView(viewModel: StudentViewModel(isMock: true))
        }
    }}
