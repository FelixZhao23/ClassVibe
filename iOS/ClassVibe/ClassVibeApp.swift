//
//  ClassVibeApp.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI
import FirebaseCore

@main
struct ClassVibeApp: App {
    
    init() {
        // 🛡️ 安全启动逻辑
        // 检查文件是否存在
        if let filePath = Bundle.main.path(forResource: "GoogleService-Info", ofType: "plist") {
            print("✅ 找到配置文件: \(filePath)")
            FirebaseApp.configure()
        } else {
            print("❌ 严重错误: 找不到 GoogleService-Info.plist！请去 Firebase 下载并拖入 Xcode。")
            // 这里不调用 configure，防止崩溃，但在控制台你会看到错误信息
        }
    }
    
    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}
