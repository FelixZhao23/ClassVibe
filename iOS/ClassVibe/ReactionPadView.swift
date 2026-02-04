//
//  ReactionPadView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//
import SwiftUI

struct ReactionPadView: View {
    @ObservedObject var viewModel: StudentViewModel
    
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
                    Button(action: { viewModel.currentCourseId = nil }) {
                        Image(systemName: "xmark.circle.fill").font(.title2).foregroundColor(.gray)
                    }
                    Spacer()
                    if viewModel.gameMode == .fever {
                        Text("🔥 FEVER TIME 🔥").font(.headline).foregroundColor(.red).bold()
                            .scaleEffect(1.2).animation(.easeInOut(duration: 0.5).repeatForever(), value: true)
                    } else if viewModel.gameMode == .battle {
                        Text("⚔️ \(viewModel.myTeam == .red ? "红队" : "蓝队")").font(.headline).bold()
                            .foregroundColor(viewModel.myTeam == .red ? .red : .blue)
                    } else {
                        Text("课堂互动中").foregroundColor(.gray)
                    }
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
                                    // --- 正面反馈 ---
                                    ("understood", "⭕️", "よくわかった", Color.green),
                                    ("interesting", "🤣", "面白い", Color.pink),
                                    ("trying", "🔥", "頑張っています", Color.orange),
                                    
                                    // --- 疑问/困难 ---
                                    ("unclear", "🤔", "ちょっと\nわからない", Color.yellow), // 我修正了"かからない"为"わからない"
                                    ("difficult", "🤯", "難しい", Color(red: 0.8, green: 0.2, blue: 0.2)), // 深红
                                    ("lost", "🌀", "ぜんぜん\nわからない", Color.red),
                                    ("what", "👀", "何をしている", Color.blue),
                                    
                                    // --- 吐槽/状态 ---
                                    ("boring", "😩", "面倒", Color.gray),
                                    ("slacking", "🎮", "サボリ中", Color.purple),
                                    ("sleep", "💤", "寝ます", Color(red: 0.4, green: 0.5, blue: 0.6))
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
    }
}
