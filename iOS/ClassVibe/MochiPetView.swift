
import SwiftUI

struct MochiPetView: View {
    var mood: PetMood
    
    // 内部动画状态
    @State private var isBouncing = false
    @State private var isShaking = false
    @State private var eyeBlink = false
    
    var config: (color: Color, msg: String) {
        switch mood {
        case .sleepy: return (Color.gray.opacity(0.2), "zzZ...")
        case .happy: return (Color.white, "听懂啦!")
        case .superHappy: return (Color.yellow.opacity(0.2), "太棒了!")
        case .confused: return (Color.orange.opacity(0.2), "嗯...?")
        case .panic: return (Color.purple.opacity(0.2), "救命!") // 这里的文字可以根据 GIF 配合
        }
    }
    
    var body: some View {
        VStack {
            // 1. 顶部气泡 (所有状态都保留气泡，看起来更统一)
            Text(config.msg)
                .font(.caption).bold()
                .padding(8)
                .background(Color.white)
                .cornerRadius(10)
                .shadow(radius: 2)
                .offset(y: isBouncing ? -5 : 0)
                // 如果是 GIF 状态，我们暂时不需要气泡跳动，或者你可以保留
                .animation(mood == .panic ? nil : .easeInOut(duration: 1).repeatForever(autoreverses: true), value: isBouncing)
            
            // 2. 角色主体 (核心修改在这里！！！)
            if mood == .panic {
                // ============== GIF 模式 ==============
                GifImage("cry") // ⚠️ 确保你的文件叫 cry.gif 且在项目目录里
                    .frame(width: 160, height: 160) // 调整大小以匹配原来的尺寸
                    .shadow(radius: 5) // 给 GIF 也加点阴影
            } else {
                // ============== 原来的代码绘图模式 ==============
                originalMochiView
            }
        }
        .onAppear { startAnimations() }
        .onChange(of: mood) { _ in startAnimations() }
    }
    
    // 我把原来的 ZStack 抽离出来放到了这里，让 body 代码更整洁
    var originalMochiView: some View {
        ZStack {
            // 身体形状
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
                        .blur(radius: 10)
                )
                .shadow(color: .black.opacity(0.1), radius: 10, x: 0, y: 10)
                .scaleEffect(isBouncing ? 1.05 : 1.0)
                .rotationEffect(.degrees(isShaking ? 5 : -5))
            
            // 脸部
            VStack(spacing: 0) {
                HStack(spacing: 30) {
                    eyeView
                    eyeView
                }
                .offset(y: -5)
                
                mouthView
                    .offset(y: 15)
            }
            
            // 腮红
            if mood != .sleepy {
                HStack(spacing: 60) {
                    Circle().fill(Color.pink.opacity(0.4)).frame(width: 20, height: 15)
                    Circle().fill(Color.pink.opacity(0.4)).frame(width: 20, height: 15)
                }
                .offset(y: 5)
            }
        }
    }
    
    // --- 眼睛视图 (不用改) ---
    var eyeView: some View {
        Group {
            switch mood {
            case .sleepy, .happy:
                Capsule()
                    .fill(Color.clear)
                    .frame(width: 20, height: 10)
                    .overlay(
                        Path { path in
                            path.addArc(center: CGPoint(x: 10, y: 10), radius: 10, startAngle: .degrees(180), endAngle: .degrees(0), clockwise: false)
                        }
                        .stroke(Color.black, lineWidth: 3)
                    )
            case .superHappy: Text("⭐").font(.title2)
            case .confused: Text("😵").font(.title2)
            case .panic: EmptyView() // ⚠️ 因为 panic 用 GIF 了，这里的代码其实不会被用到，留空即可
            default:
                Circle()
                    .fill(Color.black)
                    .frame(width: 10, height: 12)
                    .scaleEffect(y: eyeBlink ? 0.1 : 1.0)
            }
        }
    }
    
    // --- 嘴巴视图 (不用改) ---
    var mouthView: some View {
        Group {
            switch mood {
            case .happy, .superHappy:
                Circle()
                    .trim(from: 0, to: 0.5)
                    .stroke(Color.black, lineWidth: 3)
                    .frame(width: 20, height: 20)
            case .panic: EmptyView() // 同上，GIF 模式下不需要画嘴巴
            default:
                Circle()
                    .stroke(Color.black, lineWidth: 3)
                    .frame(width: 10, height: 10)
            }
        }
    }
    
    // --- 动画逻辑 (保持不变) ---
    func startAnimations() {
        isBouncing = false
        isShaking = false
        
        switch mood {
        case .happy, .superHappy:
            withAnimation(.easeInOut(duration: 0.6).repeatForever(autoreverses: true)) {
                isBouncing = true
            }
            startBlinking()
        case .confused: // 移除了 panic，因为 panic 现在是 GIF 自动播放
            withAnimation(.linear(duration: 0.1).repeatForever(autoreverses: true)) {
                isShaking = true
            }
        case .sleepy:
            withAnimation(.easeInOut(duration: 2.0).repeatForever(autoreverses: true)) {
                isBouncing = true
            }
        case .panic:
            // GIF 不需要额外的 SwiftUI 动画代码
            break
        }
    }
    
    func startBlinking() {
        guard mood == .happy || mood == .superHappy else { return }
        Timer.scheduledTimer(withTimeInterval: Double.random(in: 2.0...4.0), repeats: false) { _ in
            withAnimation(.linear(duration: 0.1)) { eyeBlink = true }
            DispatchQueue.main.asyncAfter(deadline: .now() + 0.1) {
                withAnimation { eyeBlink = false }
                if mood == .happy || mood == .superHappy { startBlinking() }
            }
        }
    }
}
