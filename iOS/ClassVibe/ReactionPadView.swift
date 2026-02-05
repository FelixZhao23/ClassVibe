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
    @State private var isShowingScanner = false
    @State private var isJoining = false
    
    var backgroundColor: Color {
        switch viewModel.gameMode {
        case .fever: return Color.purple.opacity(0.3)
        case .battle: return viewModel.myTeam == .red ? Color.red.opacity(0.2) : Color.blue.opacity(0.2)
        default: return Color.white
        }
    }
    
    var body: some View {
        Group {
            if viewModel.currentCourseId == nil {
                joinView
            } else {
                classroomView
            }
        }
        .navigationBarHidden(true)
        .sheet(isPresented: $isShowingScanner) {
            QRScannerView(scannedCode: $viewModel.roomCode, isPresented: $isShowingScanner)
        }
        .alert("教室を退出しますか？", isPresented: $showLeaveAlert) {
            Button("キャンセル", role: .cancel) {}
            Button("退出", role: .destructive) {
                viewModel.leaveCourse()
            }
        } message: {
            Text("退出すると参加状態が解除されます。")
        }
    }

    private var joinView: some View {
        VStack(spacing: 20) {
            Spacer()
            Text("ホーム").font(.largeTitle).bold()
            Text("参加コードを入力するかQRをスキャンしてください").font(.subheadline).foregroundColor(.gray)
            HStack(spacing: 12) {
                TextField("1234", text: $viewModel.roomCode)
                    .font(.system(size: 28, weight: .bold, design: .monospaced))
                    .multilineTextAlignment(.center)
                    .keyboardType(.numberPad)
                    .frame(height: 56)
                    .background(Color.white)
                    .cornerRadius(12)
                    .onChange(of: viewModel.roomCode) { value in
                        if value.count > 4 { viewModel.roomCode = String(value.prefix(4)) }
                    }
                Button(action: { isShowingScanner = true }) {
                    Image(systemName: "qrcode.viewfinder")
                        .font(.title2).foregroundColor(.white)
                        .frame(width: 56, height: 56)
                        .background(Color.black)
                        .cornerRadius(12)
                }
            }
            .padding(.horizontal, 24)

            Button(action: joinClass) {
                Text(isJoining ? "接続中..." : "教室に参加")
                    .bold()
                    .frame(maxWidth: .infinity)
                    .frame(height: 52)
                    .background(viewModel.roomCode.count == 4 ? Color.blue : Color.gray.opacity(0.35))
                    .foregroundColor(.white)
                    .cornerRadius(14)
            }
            .disabled(viewModel.roomCode.count < 4 || isJoining)
            .padding(.horizontal, 24)
            Spacer()
        }
        .background(Color(.systemGroupedBackground).ignoresSafeArea())
    }

    private var classroomView: some View {
        ZStack {
            backgroundColor.ignoresSafeArea()
            if viewModel.gameMode == .fever {
                LinearGradient(gradient: Gradient(colors: [.red, .orange, .yellow, .green, .blue, .purple]), startPoint: .topLeading, endPoint: .bottomTrailing)
                    .opacity(0.3).blendMode(.overlay).ignoresSafeArea()
            }

            VStack {
                HStack {
                    Text(viewModel.myTeam == .red ? "🟥 RED TEAM" : "🟦 BLUE TEAM")
                        .font(.headline).bold()
                        .foregroundColor(viewModel.myTeam == .red ? .red : .blue)
                    Spacer()
                    Button("退室") { showLeaveAlert = true }
                        .font(.subheadline).bold()
                        .padding(.horizontal, 12).padding(.vertical, 6)
                        .background(Color.white.opacity(0.85))
                        .cornerRadius(10)
                }
                .padding(.horizontal)

                Spacer()
                MochiPetView(mood: viewModel.currentPetMood)
                    .frame(height: 180)
                    .padding(.bottom, 20)

                let buttons = [
                    ("understood", "⭕️", "よくわかった", Color.green),
                    ("difficult", "🤯", "難しい", Color(red: 0.8, green: 0.2, blue: 0.2)),
                    ("lost", "🌀", "ぜんぜん\nわからない", Color.red),
                    ("unclear", "🤔", "ちょっと\nわからない", Color.orange),
                    ("slacking", "🎮", "サボり中", Color.indigo),
                    ("boring", "😩", "面倒", Color.gray)
                ]

                ScrollView {
                    LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 15) {
                        ForEach(buttons, id: \.0) { btn in
                            Button(action: { viewModel.sendReaction(type: btn.0) }) {
                                VStack(spacing: 5) {
                                    Text(btn.1).font(.system(size: 40))
                                        .scaleEffect(viewModel.showReactionSuccess == btn.0 ? 1.5 : 1.0)
                                        .animation(.spring(), value: viewModel.showReactionSuccess)
                                    Text(btn.2)
                                        .font(.headline).bold().foregroundColor(.white)
                                        .multilineTextAlignment(.center).minimumScaleFactor(0.8)
                                }
                                .frame(maxWidth: .infinity)
                                .frame(height: 100)
                                .background(btn.3)
                                .cornerRadius(16)
                                .shadow(color: .black.opacity(0.1), radius: 3, x: 0, y: 3)
                                .overlay(RoundedRectangle(cornerRadius: 16).stroke(Color.white.opacity(0.5), lineWidth: 1))
                            }
                        }
                    }
                    .padding(.horizontal)
                    .padding(.bottom, 20)
                }
            }
        }
    }

    private func joinClass() {
        isJoining = true
        viewModel.loginAndJoinRoom { success in
            isJoining = false
            if !success {
                // keep on join screen; error text is handled in viewModel
            }
        }
    }
}
