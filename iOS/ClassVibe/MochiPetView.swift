//
//  MochiPetView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/20.
//

import SwiftUI

// ==========================================
// MochiPetView: 情感化角色组件
// ==========================================
struct MochiPetView: View {
    // 接收外部传入的心情状态
    var mood: PetMood
    
    // 内部动画状态
    @State private var isBouncing = false
    @State private var isShaking = false
    @State private var eyeBlink = false
    
    // 根据心情配置颜色和气泡文字
    var config: (color: Color, msg: String) {
        switch mood {
        case .sleepy:
            return (Color.gray.opacity(0.2), "zzZ...")
        case .happy:
            return (Color.white, "听懂啦!")
        case .superHappy:
            return (Color.yellow.opacity(0.2), "太棒了!")
        case .confused:
            return (Color.orange.opacity(0.2), "嗯...?")
        case .panic:
            return (Color.purple.opacity(0.2), "救命!")
        }
    }
    
    var body: some View {
        VStack {
            // 1. 顶部气泡
            Text(config.msg)
                .font(.caption).bold()
                .padding(8)
                .background(Color.white)
                .cornerRadius(10)
                .shadow(radius: 2)
                // 气泡跟随身体弹跳，稍微延迟一点显得自然
                .offset(y: isBouncing ? -5 : 0)
                .animation(.easeInOut(duration: 1).repeatForever(autoreverses: true), value: isBouncing)
            
            // 2. 角色主体
            ZStack {
                // 身体形状 (圆角矩形模拟馒头形状)
                RoundedRectangle(cornerRadius: 60)
                    .fill(Color.white)
                    .frame(width: 140, height: 110)
                    .overlay(
                        RoundedRectangle(cornerRadius: 60)
                            .stroke(Color.black, lineWidth: 4)
                    )
                    .background(
                        RoundedRectangle(cornerRadius: 60)
                            .fill(config.color)
                            .blur(radius: 10) // 情绪颜色光晕
                    )
                    .shadow(color: .black.opacity(0.1), radius: 10, x: 0, y: 10)
                    // 应用动画：弹跳(呼吸) 或 颤抖
                    .scaleEffect(isBouncing ? 1.05 : 1.0)
                    .rotationEffect(.degrees(isShaking ? 5 : -5))
                
                // 脸部特征容器
                VStack(spacing: 0) {
                    // 眼睛
                    HStack(spacing: 30) {
                        eyeView
                        eyeView
                    }
                    .offset(y: -5)
                    
                    // 嘴巴
                    mouthView
                        .offset(y: 15)
                }
                
                // 腮红 (睡觉时不显示)
                if mood != .sleepy {
                    HStack(spacing: 60) {
                        Circle().fill(Color.pink.opacity(0.4)).frame(width: 20, height: 15)
                        Circle().fill(Color.pink.opacity(0.4)).frame(width: 20, height: 15)
                    }
                    .offset(y: 5)
                }
            }
            .onAppear { startAnimations() }
            // 当心情改变时，重新触发对应的动画
            .onChange(of: mood) { _ in startAnimations() }
        }
    }
    
    // --- 眼睛视图构建器 ---
    var eyeView: some View {
        Group {
            switch mood {
            case .sleepy, .happy:
                // 笑眼/闭眼 (倒弧形)
                Capsule()
                    .fill(Color.clear)
                    .frame(width: 20, height: 10)
                    .overlay(
                        Path { path in
                            path.addArc(center: CGPoint(x: 10, y: 10), radius: 10, startAngle: .degrees(180), endAngle: .degrees(0), clockwise: false)
                        }
                        .stroke(Color.black, lineWidth: 3)
                    )
            case .superHappy:
                // 星星眼
                Text("⭐").font(.title2)
            case .confused:
                // 晕乎乎眼
                Text("😵").font(.title2)
            case .panic:
                // 哭眼
                Text("😭").font(.title2)
            default: // 正常圆眼 (包含眨眼动画)
                Circle()
                    .fill(Color.black)
                    .frame(width: 10, height: 12)
                    .scaleEffect(y: eyeBlink ? 0.1 : 1.0)
            }
        }
    }
    
    // --- 嘴巴视图构建器 ---
    var mouthView: some View {
        Group {
            switch mood {
            case .happy, .superHappy:
                // 笑嘴 (半圆)
                Circle()
                    .trim(from: 0, to: 0.5)
                    .stroke(Color.black, lineWidth: 3)
                    .frame(width: 20, height: 20)
            case .panic:
                // 颤抖的嘴 (胶囊形)
                Capsule()
                    .fill(Color.black)
                    .frame(width: 30, height: 15)
            default:
                // 圆嘴 (惊讶/普通/睡觉)
                Circle()
                    .stroke(Color.black, lineWidth: 3)
                    .frame(width: 10, height: 10)
            }
        }
    }
    
    // --- 动画逻辑 ---
    func startAnimations() {
        // 重置所有动画状态
        isBouncing = false
        isShaking = false
        
        switch mood {
        case .happy, .superHappy:
            // 开心时：轻快地上下弹跳
            withAnimation(.easeInOut(duration: 0.6).repeatForever(autoreverses: true)) {
                isBouncing = true
            }
            // 启动随机眨眼
            startBlinking()
            
        case .confused, .panic:
            // 困惑/恐慌时：左右快速颤抖
            withAnimation(.linear(duration: 0.1).repeatForever(autoreverses: true)) {
                isShaking = true
            }
            
        case .sleepy:
            // 睡觉时：缓慢呼吸 (大幅度慢速缩放)
            withAnimation(.easeInOut(duration: 2.0).repeatForever(autoreverses: true)) {
                isBouncing = true
            }
        }
    }
    
    // 简单的眨眼定时器
    func startBlinking() {
        // 只有开心或正常状态才眨眼
        guard mood == .happy || mood == .superHappy else { return }
        
        // 模拟不规则眨眼
        Timer.scheduledTimer(withTimeInterval: Double.random(in: 2.0...4.0), repeats: false) { _ in
            withAnimation(.linear(duration: 0.1)) {
                eyeBlink = true
            }
            DispatchQueue.main.asyncAfter(deadline: .now() + 0.1) {
                withAnimation {
                    eyeBlink = false
                }
                // 递归调用，持续眨眼
                if mood == .happy || mood == .superHappy {
                    startBlinking()
                }
            }
        }
    }
}

// 预览组件
struct MochiPetView_Previews: PreviewProvider {
    static var previews: some View {
        VStack(spacing: 50) {
            MochiPetView(mood: .happy)
            MochiPetView(mood: .confused)
            MochiPetView(mood: .sleepy)
        }
        .padding()
        .background(Color.blue.opacity(0.1))
    }
}
