
import SwiftUI

struct MochiPetView: View {
    var mood: PetMood
    
    // 内部动画状态
    @State private var isBouncing = false
    @State private var isShaking = false
    @State private var eyeBlink = false
    
    var config: (color: Color, msg: String) {
        switch mood {
        case .sleepy: return (Color.gray.opacity(0.2), "サボり中")
        case .bored: return (Color.gray.opacity(0.2), "面倒")
        case .happy: return (Color.white, "よくわかった")
        case .superHappy: return (Color.yellow.opacity(0.2), "よくわかった")
        case .confused: return (Color.orange.opacity(0.2), "ちょっとわからない")
        case .panic: return (Color.purple.opacity(0.2), "難しい")
        case .dizzy: return (Color.purple.opacity(0.15), "ぜんぜんわからない")
        }
    }
    
    var body: some View {
        VStack {
            // 1. 角色主体 (GIF 表情)
            if mood == .superHappy {
                GifImage("great")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
            } else if mood == .panic {
                GifImage("difficult")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
            } else if mood == .dizzy {
                GifImage("not_understand")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
            } else if mood == .confused {
                GifImage("why")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
            } else if mood == .sleepy {
                GifImage("lazy")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
            } else if mood == .bored {
                GifImage("troublesome")
                    .frame(width: 160, height: 160)
                    .shadow(radius: 5)
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
            case .superHappy, .confused, .panic, .dizzy, .sleepy, .bored:
                EmptyView() // GIF 模式不需要画眼睛
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
            case .superHappy, .confused, .panic, .dizzy, .sleepy, .bored:
                EmptyView() // GIF 模式下不需要画嘴巴
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
        case .confused: // GIF 模式不需要额外动画
            withAnimation(.linear(duration: 0.1).repeatForever(autoreverses: true)) {
                isShaking = true
            }
        case .sleepy, .bored:
            break
        case .panic, .dizzy, .superHappy:
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
