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
        // 1. 尝试从 Bundle 中找到文件路径
        if let filePath = Bundle.main.path(forResource: "GoogleService-Info", ofType: "plist") {
            print("✅ 成功找到配置文件: \(filePath)")
            
            // 2. 尝试初始化
            FirebaseApp.configure()
            print("✅ Firebase 初始化成功！")
        } else {
            // 3. 如果找不到，打印严重错误
            print("❌ 严重错误: 找不到 GoogleService-Info.plist！")
            print("请检查：")
            print("1. 文件名是否完全正确（没有空格，没有(2)）")
            print("2. 是否勾选了 Target Membership")
        }
    }
    
    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}
