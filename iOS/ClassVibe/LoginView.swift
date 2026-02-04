import SwiftUI
import AuthenticationServices // 🍎 Apple 登录
import AVFoundation // 📷 相机

struct LoginView: View {
    @Binding var studentName: String
    @Binding var roomCode: String
    var onJoin: () -> Void
    
    // --- 内部状态 ---
    @State private var step = 1 // 1: 选择登录方式, 2: 输入课程码
    @State private var isShowingScanner = false
    @State private var showAccountPicker = false // 控制 Google 弹窗
    @State private var loginMethodText = "" // 显示当前是用什么登录的
    
    // 模拟 Google 账号数据 (你可以修改这里的名字)
    let googleAccounts = [
        (name: "趙 普湘", email: "24cm0123@jec.ac.jp", icon: "p.circle.fill", color: Color.blue),
        (name: "王 瑛琦", email: "24cm0105@jec.ac.jp", icon: "w.circle.fill", color: Color.orange),
        (name: "测试学生B", email: "testB@example.com", icon: "b.circle.fill", color: Color.green),
        (name: "テスト学生A", email: "studentA@gmail.com", icon: "a.circle.fill", color: Color.yellow),
    ]
    
    var body: some View {
        ZStack {
            Color(red: 0.95, green: 0.96, blue: 0.98).ignoresSafeArea()
            
            VStack(spacing: 0) {
                
                // 1. Logo (始终显示)
                VStack(spacing: 15) {
                    Image(systemName: "graduationcap.fill")
                        .resizable()
                        .scaledToFit()
                        .frame(width: 70)
                        .foregroundColor(.blue)
                        .shadow(color: .blue.opacity(0.3), radius: 10, x: 0, y: 5)
                    
                    Text("ClassVibe")
                        .font(.system(size: 32, weight: .bold, design: .rounded))
                        .foregroundColor(.black)
                }
                .padding(.top, 80)
                .padding(.bottom, 40)
                
                // 2. 主内容区 (动画切换)
                VStack {
                    if step == 1 {
                        authSelectionView
                            .transition(.asymmetric(insertion: .move(edge: .leading), removal: .move(edge: .leading)))
                    } else {
                        joinClassView
                            .transition(.asymmetric(insertion: .move(edge: .trailing), removal: .move(edge: .trailing)))
                    }
                }
                .animation(.spring(response: 0.5, dampingFraction: 0.8), value: step)
                
                Spacer()
                
                // Footer
                if step == 1 {
                    Text("© 2026 ClassVibe Project")
                        .font(.caption2).foregroundColor(.gray.opacity(0.5)).padding(.bottom, 20)
                }
            }
        }
        // Google 账号选择器
        .sheet(isPresented: $showAccountPicker) {
            AccountPickerSheet(accounts: googleAccounts) { selectedAccount in
                self.loginWithGoogle(account: selectedAccount)
            }
            .presentationDetents([.height(350)])
            .presentationDragIndicator(.visible)
        }
        // 扫码弹窗
        .sheet(isPresented: $isShowingScanner) {
            QRScannerView(scannedCode: $roomCode, isPresented: $isShowingScanner)
        }
    }
    
    // ==========================================
    // 视图 1: 登录方式选择 (Apple + Google)
    // ==========================================
    var authSelectionView: some View {
        VStack(spacing: 25) {
            VStack(spacing: 8) {
                Text("ようこそ")
                    .font(.title2).bold().foregroundColor(.gray)
                Text("クラスに参加するには\nログインしてください")
                    .font(.subheadline).foregroundColor(.gray.opacity(0.8)).multilineTextAlignment(.center)
            }
            .padding(.bottom, 10)
            
            // 🍎 1. Apple 登录按钮
            SignInWithAppleButton(
                onRequest: { request in
                    request.requestedScopes = [.fullName, .email]
                },
                onCompletion: { result in
                    switch result {
                    case .success(let authResults):
                        handleAppleLogin(result: authResults)
                    case .failure(let error):
                        print("Apple Login failed: \(error.localizedDescription)")
                    }
                }
            )
            .signInWithAppleButtonStyle(.black)
            .frame(height: 50)
            .cornerRadius(25)
            .shadow(color: .black.opacity(0.1), radius: 5, x: 0, y: 3)
            .padding(.horizontal, 40)
            
            HStack {
                Rectangle().frame(height: 1).foregroundColor(.gray.opacity(0.3))
                Text("または").font(.caption).foregroundColor(.gray)
                Rectangle().frame(height: 1).foregroundColor(.gray.opacity(0.3))
            }
            .padding(.horizontal, 60)
            
            // 🔵 2. Google 登录按钮 (模拟)
            Button(action: {
                showAccountPicker = true
            }) {
                HStack(spacing: 15) {
                    ZStack {
                        Color.white
                        Image(systemName: "g.circle.fill") // 模拟 G 图标
                            .resizable()
                            .frame(width: 20, height: 20)
                            .foregroundColor(.red)
                    }
                    .frame(width: 24, height: 24)
                    
                    Text("Google でログイン")
                        .font(.headline)
                        .foregroundColor(.gray)
                }
                .frame(maxWidth: .infinity)
                .frame(height: 50)
                .background(Color.white)
                .cornerRadius(25)
                .shadow(color: .black.opacity(0.1), radius: 5, x: 0, y: 3)
            }
            .padding(.horizontal, 40)
        }
        .padding(.top, 10)
    }
    
    // ==========================================
    // 视图 2: 加入课程 (输码/扫码)
    // ==========================================
    var joinClassView: some View {
        VStack(spacing: 25) {
            
            // 👤 用户卡片
            HStack(spacing: 15) {
                ZStack {
                    Circle().fill(Color.blue.opacity(0.1))
                    // 根据登录方式显示不同图标
                    Image(systemName: loginMethodText == "Apple ID" ? "applelogo" : "g.circle.fill")
                        .foregroundColor(.black)
                        .font(.title3)
                }
                .frame(width: 50, height: 50)
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(studentName)
                        .font(.headline).foregroundColor(.black)
                    Text(loginMethodText)
                        .font(.caption).foregroundColor(.gray)
                }
                Spacer()
                Button("変更") { withAnimation { step = 1 } }
                    .font(.caption).foregroundColor(.blue)
                    .padding(.horizontal, 12).padding(.vertical, 6)
                    .background(Color.blue.opacity(0.1)).cornerRadius(20)
            }
            .padding().background(Color.white).cornerRadius(16)
            .shadow(color: .black.opacity(0.05), radius: 10)
            .padding(.horizontal, 30)
            
            // 🔢 输入框
            VStack(alignment: .leading, spacing: 10) {
                Text("参加コード (4桁)").font(.caption).bold().foregroundColor(.gray).padding(.leading, 5)
                HStack(spacing: 12) {
                    TextField("1234", text: $roomCode)
                        .font(.system(size: 28, weight: .bold, design: .monospaced))
                        .multilineTextAlignment(.center).keyboardType(.numberPad)
                        .frame(height: 60).background(Color.white).cornerRadius(16)
                        .shadow(color: .black.opacity(0.05), radius: 5)
                        .onChange(of: roomCode) { newValue in if newValue.count > 4 { roomCode = String(newValue.prefix(4)) } }
                    
                    Button(action: { isShowingScanner = true }) {
                        Image(systemName: "qrcode.viewfinder").font(.title).foregroundColor(.white)
                            .frame(width: 60, height: 60).background(Color.black).cornerRadius(16)
                            .shadow(color: .black.opacity(0.2), radius: 5)
                    }
                }
            }
            .padding(.horizontal, 30)
            
            Spacer().frame(height: 20)
            
            Button(action: onJoin) {
                HStack { Text("教室に入る").font(.title3).bold(); Image(systemName: "arrow.right").font(.headline) }
                .frame(maxWidth: .infinity).frame(height: 56)
                .background(roomCode.count == 4 ? Color.blue : Color.gray.opacity(0.3))
                .foregroundColor(.white).cornerRadius(28)
                .shadow(color: .blue.opacity(0.3), radius: 8, y: 4)
            }
            .disabled(roomCode.count < 4).padding(.horizontal, 30)
        }
    }
    
    // ==========================================
    // 逻辑处理函数
    // ==========================================
    
    // Apple 登录处理
    func handleAppleLogin(result: ASAuthorization) {
        switch result.credential {
        case let appleIDCredential as ASAuthorizationAppleIDCredential:
            let fullName = appleIDCredential.fullName
            let email = appleIDCredential.email
            
            var name = "Guest"
            if let givenName = fullName?.givenName, let familyName = fullName?.familyName {
                name = "\(familyName) \(givenName)"
            } else if let email = email {
                name = String(email.split(separator: "@").first ?? "Student")
            } else {
                name = "Apple User"
            }
            
            self.studentName = name
            self.loginMethodText = "Apple ID"
            
            DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) { withAnimation { self.step = 2 } }
        default: break
        }
    }
    
    // Google 登录处理 (模拟)
    func loginWithGoogle(account: (name: String, email: String, icon: String, color: Color)) {
        self.studentName = account.name
        self.loginMethodText = account.email // 显示邮箱
        self.showAccountPicker = false
        
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.3) {
            withAnimation { self.step = 2 }
        }
    }
}

// ==========================================
// 📜 账号选择器 (Google 风格)
// ==========================================
struct AccountPickerSheet: View {
    let accounts: [(name: String, email: String, icon: String, color: Color)]
    let onSelect: ((name: String, email: String, icon: String, color: Color)) -> Void
    
    var body: some View {
        VStack(spacing: 0) {
            HStack { Image(systemName: "g.circle.fill").foregroundColor(.gray); Text("アカウントの選択").font(.headline) }
                .padding(.top, 25).padding(.bottom, 20)
            Divider()
            ScrollView {
                VStack(spacing: 0) {
                    ForEach(accounts, id: \.email) { account in
                        Button(action: { onSelect(account) }) {
                            HStack(spacing: 15) {
                                Circle().fill(account.color.opacity(0.2)).frame(width: 40, height: 40)
                                    .overlay(Image(systemName: account.icon).foregroundColor(account.color))
                                VStack(alignment: .leading, spacing: 2) {
                                    Text(account.name).font(.subheadline).foregroundColor(.black).bold()
                                    Text(account.email).font(.caption).foregroundColor(.gray)
                                }
                                Spacer()
                            }
                            .padding(.horizontal, 20).padding(.vertical, 12).contentShape(Rectangle())
                        }
                        Divider().padding(.leading, 75)
                    }
                }
            }
        }.background(Color.white)
    }
}

// ==========================================
// 📷 二维码组件 (保持不变)
// ==========================================
struct QRScannerView: UIViewControllerRepresentable {
    @Binding var scannedCode: String; @Binding var isPresented: Bool
    func makeUIViewController(context: Context) -> ScannerViewController { let c = ScannerViewController(); c.delegate = context.coordinator; return c }
    func updateUIViewController(_ ui: ScannerViewController, context: Context) {}
    func makeCoordinator() -> Coordinator { Coordinator(parent: self) }
    class Coordinator: NSObject, ScannerDelegate {
        let parent: QRScannerView; init(parent: QRScannerView) { self.parent = parent }
        func didFind(code: String) { parent.scannedCode = code; parent.isPresented = false }
        func didFail(error: String) { parent.isPresented = false }
    }
}
protocol ScannerDelegate: AnyObject { func didFind(code: String); func didFail(error: String) }
class ScannerViewController: UIViewController, AVCaptureMetadataOutputObjectsDelegate {
    weak var delegate: ScannerDelegate?; var captureSession: AVCaptureSession!
    override func viewDidLoad() {
        super.viewDidLoad(); view.backgroundColor = .black; captureSession = AVCaptureSession()
        guard let device = AVCaptureDevice.default(for: .video), let input = try? AVCaptureDeviceInput(device: device) else { return }
        if captureSession.canAddInput(input) { captureSession.addInput(input) }
        let output = AVCaptureMetadataOutput(); if captureSession.canAddOutput(output) { captureSession.addOutput(output); output.setMetadataObjectsDelegate(self, queue: .main); output.metadataObjectTypes = [.qr] }
        let layer = AVCaptureVideoPreviewLayer(session: captureSession); layer.frame = view.layer.bounds; layer.videoGravity = .resizeAspectFill; view.layer.addSublayer(layer)
        let scanBox = UIView(); scanBox.layer.borderColor = UIColor.green.cgColor; scanBox.layer.borderWidth = 3; scanBox.frame = CGRect(x: 0, y: 0, width: 250, height: 250); scanBox.center = view.center; view.addSubview(scanBox)
        DispatchQueue.global(qos: .background).async { self.captureSession.startRunning() }
    }
    func metadataOutput(_ o: AVCaptureMetadataOutput, didOutput m: [AVMetadataObject], from c: AVCaptureConnection) {
        if let obj = m.first as? AVMetadataMachineReadableCodeObject, let str = obj.stringValue { AudioServicesPlaySystemSound(SystemSoundID(kSystemSoundID_Vibrate)); delegate?.didFind(code: str) }
    }
    override func viewWillDisappear(_ animated: Bool) { super.viewWillDisappear(animated); if captureSession?.isRunning == true { captureSession.stopRunning() } }
}
