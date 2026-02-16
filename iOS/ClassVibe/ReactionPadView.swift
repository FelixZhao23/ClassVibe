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
    @State private var showClassEndedAlert = false
    
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
        .alert("授業が終了しました", isPresented: Binding(
            get: { showClassEndedAlert },
            set: { showClassEndedAlert = $0 }
        )) {
            Button("OK", role: .cancel) {}
        } message: {
            Text("教室は終了しました。ホームに戻ります。")
        }
        .onReceive(NotificationCenter.default.publisher(for: Notification.Name("ClassVibeClassEnded"))) { _ in
            showClassEndedAlert = true
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
        GeometryReader { geo in
            ScrollView {
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

                        MochiPetView(mood: viewModel.currentPetMood)
                            .frame(height: min(180, geo.size.height * 0.28))
                            .padding(.vertical, 12)
                    }
                    .padding(.top, 8)
                    .padding(.bottom, 8)
                    .background(teamGradientBackground)

                    VStack(alignment: .leading, spacing: 12) {
                        Text("リアクション")
                            .font(.headline)
                            .foregroundColor(Color.gray)
                            .padding(.horizontal, 22)
                            .padding(.top, 20)

                        let buttons: [(String, String, String, Color, Color, Color)] = [
                            ("understood", "⭕️", "よくわかった",
                             Color(red: 220/255, green: 252/255, blue: 231/255),
                             Color(red: 22/255, green: 101/255, blue: 52/255),
                             Color(red: 134/255, green: 239/255, blue: 172/255)),
                            ("difficult", "🤯", "難しい",
                             Color(red: 254/255, green: 226/255, blue: 226/255),
                             Color(red: 153/255, green: 27/255, blue: 27/255),
                             Color(red: 252/255, green: 165/255, blue: 165/255)),
                            ("lost", "🌀", "ぜんぜん\nわからない",
                             Color(red: 219/255, green: 234/255, blue: 254/255),
                             Color(red: 30/255, green: 64/255, blue: 175/255),
                             Color(red: 147/255, green: 197/255, blue: 253/255)),
                            ("unclear", "🤔", "ちょっと\nわからない",
                             Color(red: 243/255, green: 244/255, blue: 246/255),
                             Color(red: 75/255, green: 85/255, blue: 99/255),
                             Color(red: 209/255, green: 213/255, blue: 219/255)),
                            ("slacking", "🎮", "サボり中",
                             Color(red: 224/255, green: 231/255, blue: 255/255),
                             Color(red: 30/255, green: 58/255, blue: 138/255),
                             Color(red: 165/255, green: 180/255, blue: 252/255)),
                            ("boring", "😩", "面倒",
                             Color(red: 229/255, green: 231/255, blue: 235/255),
                             Color(red: 55/255, green: 65/255, blue: 81/255),
                             Color(red: 209/255, green: 213/255, blue: 219/255))
                        ]

                        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                            ForEach(buttons, id: \.0) { btn in
                                Button(action: { viewModel.sendReaction(type: btn.0) }) {
                                    VStack(spacing: 6) {
                                        Text(btn.1).font(.system(size: 24, weight: .regular, design: .rounded))
                                            .scaleEffect(viewModel.showReactionSuccess == btn.0 ? 1.5 : 1.0)
                                            .animation(.spring(), value: viewModel.showReactionSuccess)
                                        Text(btn.2)
                                            .font(.headline).bold().foregroundColor(btn.4)
                                            .multilineTextAlignment(.center).minimumScaleFactor(0.8)
                                    }
                                    .frame(maxWidth: .infinity)
                                    .frame(height: 100)
                                    .background(btn.3)
                                    .cornerRadius(16)
                                    .shadow(color: .black.opacity(0.12), radius: 3, x: 0, y: 3)
                                    .overlay(RoundedRectangle(cornerRadius: 16).stroke(btn.5, lineWidth: 1))
                                }
                            }
                        }
                        .padding(.horizontal, 22)
                        .padding(.bottom, 24)
                    }
                    .frame(maxWidth: .infinity)
                    .background(Color.white)
                    .clipShape(RoundedRectangle(cornerRadius: 28, style: .continuous))
                    .shadow(color: .black.opacity(0.12), radius: 10, x: 0, y: -2)
                    .padding(.horizontal, 16)
                    .padding(.bottom, 12)

                    LinearGradient(
                        gradient: Gradient(colors: [Color.white.opacity(0.0), Color.white.opacity(0.6), Color.white]),
                        startPoint: .top,
                        endPoint: .bottom
                    )
                    .frame(height: 18)
                }
                .frame(minHeight: geo.size.height)
            }
            .background(teamGradientBackground.ignoresSafeArea())
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
