//
//  ReactionPadView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//
import SwiftUI

struct ReactionPadView: View {
    @ObservedObject var viewModel: StudentViewModel
    @State private var showLeaveAlert = false
    
    var backgroundColor: Color {
        switch viewModel.gameMode {
        case .fever: return Color.purple.opacity(0.3)
        case .battle: return viewModel.myTeam == .red ? Color.red.opacity(0.2) : Color.blue.opacity(0.2)
        default: return Color.white
        }
    }
    
    var body: some View {
        ZStack {
            backgroundColor.ignoresSafeArea()
            
            if viewModel.gameMode == .fever {
                LinearGradient(gradient: Gradient(colors: [.red, .orange, .yellow, .green, .blue, .purple]), startPoint: .topLeading, endPoint: .bottomTrailing)
                    .opacity(0.3).blendMode(.overlay).ignoresSafeArea()
            }
            
            VStack {
                HStack {
                    Button(action: { showLeaveAlert = true }) {
                        Image(systemName: "xmark.circle.fill").font(.title2).foregroundColor(.gray)
                    }
                    Spacer()
                    Text(viewModel.myTeam == .red ? "🟥 RED TEAM" : "🟦 BLUE TEAM")
                        .font(.headline).bold()
                        .foregroundColor(viewModel.myTeam == .red ? .red : .blue)
                    Spacer()
                    Button(action: { viewModel.debugToggleMode() }) {
                        Image(systemName: "slider.horizontal.3").font(.title2)
                    }
                }
                .padding(.horizontal)
                
                Spacer()
                
                // Mochi-chan
                MochiPetView(mood: viewModel.currentPetMood)
                    .frame(height: 180)
                    .padding(.bottom, 20)
                
                // Buttons
            // 格式: (Key, Emoji, 显示文字, 背景颜色)
                                let buttons = [
                                    ("understood", "⭕️", "よくわかった", Color.green),
                                    ("difficult", "🤯", "難しい", Color(red: 0.8, green: 0.2, blue: 0.2)),
                                    ("lost", "🌀", "ぜんぜん\nわからない", Color.red),
                                    ("unclear", "🤔", "ちょっと\nわからない", Color.orange),
                                    ("slacking", "🎮", "サボり中", Color.indigo),
                                    ("boring", "😩", "面倒", Color.gray)
                                ]
                                
                                // 使用 ScrollView 以防屏幕放不下 10 个按钮
                                ScrollView {
                                    LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 15) {
                                        ForEach(buttons, id: \.0) { btn in
                                            Button(action: { viewModel.sendReaction(type: btn.0) }) {
                                                VStack(spacing: 5) {
                                                    Text(btn.1).font(.system(size: 40)) // Emoji
                                                        .scaleEffect(viewModel.showReactionSuccess == btn.0 ? 1.5 : 1.0)
                                                        .animation(.spring(), value: viewModel.showReactionSuccess)
                                                    
                                                    Text(btn.2) // 文字
                                                        .font(.headline)
                                                        .bold()
                                                        .foregroundColor(.white)
                                                        .multilineTextAlignment(.center)
                                                        .minimumScaleFactor(0.8) // 文字太长自动缩小
                                                }
                                                .frame(maxWidth: .infinity)
                                                .frame(height: 100) //稍微调低高度以便放下更多
                                                .background(viewModel.gameMode == .fever ? Color.purple : (viewModel.gameMode == .battle ? (viewModel.myTeam == .red ? .red : .blue) : btn.3))
                                                .cornerRadius(16)
                                                .shadow(color: .black.opacity(0.1), radius: 3, x: 0, y: 3)
                                                .overlay(RoundedRectangle(cornerRadius: 16).stroke(Color.white.opacity(0.5), lineWidth: 1))
                                            }
                                        }
                                    }
                                    .padding(.horizontal)
                                    .padding(.bottom, 20) // 底部留白
                                }
                
                // Points
                HStack {
                    Image(systemName: "star.fill").foregroundColor(.yellow)
                    Text("Points: \(viewModel.vibePoints)").font(.headline)
                }
                .padding().background(Color.white.opacity(0.8)).cornerRadius(20).padding(.bottom)
            }
        }
        .navigationBarHidden(true)
        .alert("教室を退出しますか？", isPresented: $showLeaveAlert) {
            Button("キャンセル", role: .cancel) {}
            Button("退出", role: .destructive) {
                viewModel.leaveCourse()
            }
        } message: {
            Text("退出すると参加状態が解除されます。")
        }
    }
}
