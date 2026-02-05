import SwiftUI
import AuthenticationServices // 🍎 Apple 登录
import AVFoundation // 📷 相机
import UIKit
import FirebaseAuth
import GoogleSignIn

struct LoginView: View {
    @Binding var studentName: String
    @Binding var roomCode: String
    var onAuthSuccess: () -> Void
    
    // --- 内部状态 ---
    @State private var loginMethodText = "" // 显示当前是用什么登录的
    @State private var authErrorText = ""
    
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
                
                VStack {
                    authSelectionView
                }
                
                Spacer()
                
                // Footer
                Text("© 2026 ClassVibe Project")
                    .font(.caption2).foregroundColor(.gray.opacity(0.5)).padding(.bottom, 20)
            }
        }
        .onAppear {
            if Auth.auth().currentUser != nil {
                onAuthSuccess()
            }
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
            
            // 🔵 2. Google 登录按钮（实际 Google 账号）
            Button(action: {
                signInWithGoogle()
            }) {
                HStack(spacing: 15) {
                    ZStack {
                        Color.white
                        Image(systemName: "g.circle.fill")
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

            if !authErrorText.isEmpty {
                Text(authErrorText)
                    .font(.caption)
                    .foregroundColor(.red)
                    .padding(.horizontal, 40)
            }
        }
        .padding(.top, 10)
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
            DispatchQueue.main.asyncAfter(deadline: .now() + 0.2) { onAuthSuccess() }
        default: break
        }
    }
    
    func signInWithGoogle() {
        authErrorText = ""
        guard let rootVC = UIApplication.shared.connectedScenes
            .compactMap({ $0 as? UIWindowScene })
            .flatMap({ $0.windows })
            .first(where: { $0.isKeyWindow })?.rootViewController else {
            authErrorText = "画面の初期化に失敗しました"
            return
        }

        GIDSignIn.sharedInstance.signIn(withPresenting: rootVC) { result, error in
            if let error = error {
                authErrorText = "Googleログイン失敗: \(error.localizedDescription)"
                return
            }

            guard let user = result?.user else {
                authErrorText = "Googleユーザー情報の取得に失敗しました"
                return
            }

            guard let idToken = user.idToken?.tokenString else {
                authErrorText = "Googleトークン取得に失敗しました"
                return
            }
            let accessToken = user.accessToken.tokenString
            let credential = GoogleAuthProvider.credential(withIDToken: idToken, accessToken: accessToken)

            Auth.auth().signIn(with: credential) { _, firebaseError in
                if let firebaseError = firebaseError {
                    authErrorText = "Firebase認証失敗: \(firebaseError.localizedDescription)"
                    return
                }

                let profileName = user.profile?.name ?? user.profile?.givenName ?? "Google User"
                let email = user.profile?.email ?? "Google"

                self.studentName = profileName
                self.loginMethodText = email

                DispatchQueue.main.asyncAfter(deadline: .now() + 0.2) {
                    onAuthSuccess()
                }
            }
        }
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
