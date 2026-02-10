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
    @State private var previewCourseTitle: String = ""
    
    var teamBackground: some View {
        switch viewModel.gameMode {
        case .battle:
            return AnyView(teamGradientBackground)
        case .fever:
            return AnyView(Color.purple.opacity(0.2))
        default:
            if viewModel.myTeam == .red || viewModel.myTeam == .blue {
                return AnyView(teamGradientBackground)
            }
            return AnyView(Color(.systemGroupedBackground))
        }
    }

    private var teamGradientBackground: some View {
        ZStack {
            LinearGradient(
                gradient: Gradient(colors: viewModel.myTeam == .red
                    ? [Color(red: 1.00, green: 0.95, blue: 0.95), Color(red: 1.00, green: 0.79, blue: 0.79)]
                    : [Color(red: 0.94, green: 0.97, blue: 1.00), Color(red: 0.75, green: 0.86, blue: 0.99)]
                ),
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            RadialGradient(
                gradient: Gradient(colors: [Color.white.opacity(0.7), Color.clear]),
                center: .topLeading,
                startRadius: 30,
                endRadius: 260
            )
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
                        if value.count == 4 {
                            viewModel.fetchCourseTitleByCode(value) { title in
                                previewCourseTitle = title ?? ""
                            }
                        } else {
                            previewCourseTitle = ""
                        }
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

            if !previewCourseTitle.isEmpty {
                Text("この授業: \(previewCourseTitle)")
                    .font(.subheadline)
                    .foregroundColor(.blue)
                    .padding(.horizontal, 24)
            }

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
        VStack(spacing: 0) {
            VStack {
                HStack {
                    VStack(alignment: .leading, spacing: 2) {
                        Text(viewModel.currentCourseTitle.isEmpty ? "教室" : viewModel.currentCourseTitle)
                            .font(.headline).bold().foregroundColor(.primary)
                        Text(viewModel.myTeam == .red ? "RED TEAM" : "BLUE TEAM")
                            .font(.caption).bold()
                            .padding(.horizontal, 10).padding(.vertical, 4)
                            .background(viewModel.myTeam == .red ? Color.red.opacity(0.18) : Color.blue.opacity(0.18))
                            .cornerRadius(10)
                            .foregroundColor(viewModel.myTeam == .red ? .red : .blue)
                    }
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
                .padding(.bottom, 16)
                Spacer(minLength: 12)
            }
            .padding(.top, 8)
            .padding(.bottom, 12)
            .background(teamGradientBackground)

            VStack(alignment: .leading, spacing: 12) {
                Text("リアクション")
                    .font(.headline)
                    .foregroundColor(Color.gray)
                    .padding(.horizontal, 22)

                let buttons = [
                    ("understood", "⭕️", "よくわかった", Color.green),
                    ("difficult", "🤯", "難しい", Color(red: 0.8, green: 0.2, blue: 0.2)),
                    ("lost", "🌀", "ぜんぜん\nわからない", Color.red),
                    ("unclear", "🤔", "ちょっと\nわからない", Color.orange),
                    ("slacking", "🎮", "サボり中", Color.indigo),
                    ("boring", "😩", "面倒", Color.gray)
                ]

                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                    ForEach(buttons, id: \.0) { btn in
                        Button(action: { viewModel.sendReaction(type: btn.0) }) {
                            VStack(spacing: 6) {
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
                            .shadow(color: .black.opacity(0.12), radius: 3, x: 0, y: 3)
                            .overlay(RoundedRectangle(cornerRadius: 16).stroke(Color.white.opacity(0.5), lineWidth: 1))
                        }
                    }
                }
                .padding(.horizontal, 22)
                .padding(.bottom, 30)
            }
            .frame(maxWidth: .infinity)
            .background(Color.white)
            .clipShape(RoundedRectangle(cornerRadius: 28, style: .continuous))
            .shadow(color: .black.opacity(0.12), radius: 10, x: 0, y: -2)
            .padding(.horizontal, 12)
            .padding(.bottom, 8)

            LinearGradient(
                gradient: Gradient(colors: [Color.white.opacity(0.0), Color.white.opacity(0.6), Color.white]),
                startPoint: .top,
                endPoint: .bottom
            )
            .frame(height: 18)
        }
        .background(teamGradientBackground.ignoresSafeArea())
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
