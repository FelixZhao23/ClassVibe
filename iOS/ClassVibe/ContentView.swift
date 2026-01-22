//
//  ContentView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

struct ContentView: View {
    
    @StateObject private var viewModel = StudentViewModel()
    // ✨ 修复：增加这个状态变量，只有点击按钮后才变成 true
    @State private var isLoggedIn = false
    
    var body: some View {
        Group {
            // ① 未登录
            if !isLoggedIn {
                LoginView(studentName: $viewModel.studentName) {
                    // 这是 LoginView 中按钮点击后的回调
                    if !viewModel.studentName.isEmpty {
                        viewModel.listenToCourses() // 开始加载数据
                        isLoggedIn = true // ✨ 切换界面
                    }
                }
               
            // ② 已登录
            } else {
                TabView {
                    // 上课流程
                    NavigationView {
                        if viewModel.currentCourseId == nil {
                            CourseListView(viewModel: viewModel)
                        } else {
                            ReactionPadView(viewModel: viewModel)
                        }
                    }
                    .tabItem {
                        Label("课堂", systemImage: "person.3.fill")
                    }
                    
                    // 个人中心 / 扭蛋
                    GachaProfileView(viewModel: viewModel)
                        .tabItem {
                            Label("我的", systemImage: "person.crop.circle")
                        }
                }
                .accentColor(.blue)
            }
        }
    }
}
