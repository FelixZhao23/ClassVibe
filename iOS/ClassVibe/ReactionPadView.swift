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
                let buttons = [
                    ("happy", "😄", "明白", Color.green),
                    ("amazing", "😲", "太棒了", Color.pink),
                    ("confused", "🤔", "困惑", Color.orange),
                    ("question", "❓", "提问", Color.blue)
                ]
                
                LazyVGrid(columns: [GridItem(), GridItem()], spacing: 15) {
                    ForEach(buttons, id: \.0) { btn in
                        Button(action: { viewModel.sendReaction(type: btn.0) }) {
                            VStack {
                                Text(btn.1).font(.system(size: 45))
                                    .scaleEffect(viewModel.showReactionSuccess == btn.0 ? 1.5 : 1.0)
                                    .animation(.spring(), value: viewModel.showReactionSuccess)
                                Text(btn.2).bold().foregroundColor(.white)
                            }
                            .frame(maxWidth: .infinity, minHeight: 120)
                            .background(viewModel.gameMode == .fever ? Color.purple : (viewModel.gameMode == .battle ? (viewModel.myTeam == .red ? .red : .blue) : btn.3))
                            .cornerRadius(20)
                            .shadow(radius: 5)
                            .overlay(RoundedRectangle(cornerRadius: 20).stroke(Color.white, lineWidth: viewModel.gameMode == .fever ? 4 : 0))
                        }
                    }
                }
                .padding(.horizontal)
                
                Spacer()
                
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
