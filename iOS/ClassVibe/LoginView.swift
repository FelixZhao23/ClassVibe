//
//  LoginView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI
import AVFoundation // 用于相机扫码

struct LoginView: View {
    @Binding var studentName: String
    @Binding var roomCode: String
    var onJoin: () -> Void
    
    // --- 内部状态控制 ---
    @State private var step = 1 // 1: 登录界面, 2: 加入界面
    @State private var emailInput = ""
    @State private var isShowingScanner = false // 是否显示扫码相机
    
    var body: some View {
        ZStack {
            // 背景颜色
            Color(red: 0.95, green: 0.96, blue: 0.98).ignoresSafeArea()
            
            VStack(spacing: 30) {
                // Logo 区域
                Image(systemName: "graduationcap.fill")
                    .resizable()
                    .scaledToFit()
                    .frame(width: 80)
                    .foregroundColor(.blue)
                
                Text("ClassVibe")
                    .font(.system(size: 32, weight: .bold, design: .rounded))
                    .foregroundColor(.black)
                
                // 根据步骤显示不同内容
                if step == 1 {
                    loginStepView
                        .transition(.asymmetric(insertion: .move(edge: .trailing), removal: .move(edge: .leading)))
                } else {
                    joinStepView
                        .transition(.asymmetric(insertion: .move(edge: .trailing), removal: .move(edge: .leading)))
                }
            }
            .padding()
            // 动画效果
            .animation(.easeInOut, value: step)
        }
        // 扫码弹窗
        .sheet(isPresented: $isShowingScanner) {
            QRScannerView(scannedCode: $roomCode, isPresented: $isShowingScanner)
        }
    }
    
    // ==========================================
    // 步骤 1: 登录界面视图
    // ==========================================
    var loginStepView: some View {
        VStack(spacing: 20) {
            Text("ログイン")
                .font(.headline)
                .foregroundColor(.gray)
            
            // 邮箱输入框
            TextField("Googleメールアドレス", text: $emailInput)
                .padding()
                .background(Color.white)
                .cornerRadius(10)
                .shadow(color: .black.opacity(0.05), radius: 5, x: 0, y: 2)
                .keyboardType(.emailAddress)
                .autocapitalization(.none)
            
            // 模拟 Google 登录按钮 (点击后提取邮箱前缀作为名字)
            Button(action: {
                if !emailInput.isEmpty {
                    // 简单的逻辑：提取 @ 前面的部分作为名字
                    let components = emailInput.split(separator: "@")
                    studentName = String(components.first ?? "")
                    step = 2 // 进入下一步
                }
            }) {
                HStack {
                    Image(systemName: "g.circle.fill") // 模拟 Google 图标
                        .font(.title2)
                    Text("Googleでログイン")
                        .bold()
                }
                .frame(maxWidth: .infinity)
                .padding()
                .background(Color.white)
                .foregroundColor(.black)
                .cornerRadius(10)
                .shadow(color: .black.opacity(0.1), radius: 3, x: 0, y: 2)
            }
            
            HStack {
                Rectangle().frame(height: 1).foregroundColor(.gray.opacity(0.3))
                Text("または").font(.caption).foregroundColor(.gray)
                Rectangle().frame(height: 1).foregroundColor(.gray.opacity(0.3))
            }
            
            // 仅使用名字登录 (备用)
            Button(action: {
                if !emailInput.isEmpty {
                    studentName = emailInput
                    step = 2
                }
            }) {
                Text("次へ")
                    .bold()
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(emailInput.isEmpty ? Color.gray : Color.blue)
                    .foregroundColor(.white)
                    .cornerRadius(10)
                    .shadow(radius: 5)
            }
            .disabled(emailInput.isEmpty)
        }
        .padding(.horizontal)
    }
    
    // ==========================================
    // 步骤 2: 加入课堂视图 (输码 + 扫码)
    // ==========================================
    var joinStepView: some View {
        VStack(spacing: 25) {
            Text("ようこそ、\(studentName) さん")
                .font(.headline)
                .foregroundColor(.blue)
            
            VStack(alignment: .leading, spacing: 8) {
                Text("参加コードを入力")
                    .font(.caption)
                    .foregroundColor(.gray)
                    .padding(.leading, 5)
                
                // 4位数字输入框
                TextField("1234", text: $roomCode)
                    .font(.system(size: 40, weight: .bold, design: .monospaced))
                    .multilineTextAlignment(.center)
                    .keyboardType(.numberPad)
                    .padding()
                    .background(Color.white)
                    .cornerRadius(15)
                    .shadow(color: .black.opacity(0.05), radius: 5)
                    // 限制只能输4位
                    .onChange(of: roomCode) { newValue in
                        if newValue.count > 4 {
                            roomCode = String(newValue.prefix(4))
                        }
                    }
            }
            
            // 确认加入按钮
            Button(action: {
                onJoin()
            }) {
                Text("教室に入る")
                    .font(.title3)
                    .bold()
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(roomCode.count == 4 ? Color.blue : Color.gray.opacity(0.3))
                    .foregroundColor(.white)
                    .cornerRadius(15)
                    .shadow(radius: 5)
            }
            .disabled(roomCode.count != 4)
            
            Text("または").font(.caption).foregroundColor(.gray)
            
            // 📷 扫码按钮
            Button(action: {
                isShowingScanner = true
            }) {
                HStack {
                    Image(systemName: "qrcode.viewfinder")
                        .font(.title2)
                    Text("QRコードをスキャン")
                        .bold()
                }
                .frame(maxWidth: .infinity)
                .padding()
                .background(Color.black)
                .foregroundColor(.white)
                .cornerRadius(15)
                .shadow(radius: 5)
            }
            
            // 返回按钮
            Button("戻る") {
                withAnimation { step = 1 }
            }
            .font(.caption)
            .foregroundColor(.gray)
            .padding(.top, 10)
        }
        .padding(.horizontal)
    }
}

// ==========================================
// 📷 附带功能：二维码扫描器实现
// ==========================================
struct QRScannerView: UIViewControllerRepresentable {
    @Binding var scannedCode: String
    @Binding var isPresented: Bool
    
    func makeUIViewController(context: Context) -> ScannerViewController {
        let controller = ScannerViewController()
        controller.delegate = context.coordinator
        return controller
    }
    
    func updateUIViewController(_ uiViewController: ScannerViewController, context: Context) {}
    
    func makeCoordinator() -> Coordinator {
        Coordinator(parent: self)
    }
    
    class Coordinator: NSObject, ScannerDelegate {
        let parent: QRScannerView
        
        init(parent: QRScannerView) { self.parent = parent }
        
        func didFind(code: String) {
            // 扫描成功，填入 code 并关闭
            parent.scannedCode = code
            parent.isPresented = false
        }
        
        func didFail(error: String) {
            print("Scan failed: \(error)")
            parent.isPresented = false
        }
    }
}

protocol ScannerDelegate: AnyObject {
    func didFind(code: String)
    func didFail(error: String)
}

class ScannerViewController: UIViewController, AVCaptureMetadataOutputObjectsDelegate {
    weak var delegate: ScannerDelegate?
    var captureSession: AVCaptureSession!
    var previewLayer: AVCaptureVideoPreviewLayer!
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        view.backgroundColor = UIColor.black
        captureSession = AVCaptureSession()
        
        guard let videoCaptureDevice = AVCaptureDevice.default(for: .video) else { return }
        let videoInput: AVCaptureDeviceInput
        
        do { videoInput = try AVCaptureDeviceInput(device: videoCaptureDevice) } catch { return }
        
        if (captureSession.canAddInput(videoInput)) { captureSession.addInput(videoInput) }
        else { delegate?.didFail(error: "Cannot add input"); return }
        
        let metadataOutput = AVCaptureMetadataOutput()
        
        if (captureSession.canAddOutput(metadataOutput)) {
            captureSession.addOutput(metadataOutput)
            metadataOutput.setMetadataObjectsDelegate(self, queue: DispatchQueue.main)
            metadataOutput.metadataObjectTypes = [.qr]
        } else { delegate?.didFail(error: "Cannot add output"); return }
        
        previewLayer = AVCaptureVideoPreviewLayer(session: captureSession)
        previewLayer.frame = view.layer.bounds
        previewLayer.videoGravity = .resizeAspectFill
        view.layer.addSublayer(previewLayer)
        
        DispatchQueue.global(qos: .background).async {
            self.captureSession.startRunning()
        }
    }
    
    func metadataOutput(_ output: AVCaptureMetadataOutput, didOutput metadataObjects: [AVMetadataObject], from connection: AVCaptureConnection) {
        captureSession.stopRunning()
        
        if let metadataObject = metadataObjects.first {
            guard let readableObject = metadataObject as? AVMetadataMachineReadableCodeObject else { return }
            guard let stringValue = readableObject.stringValue else { return }
            // 震动提示
            AudioServicesPlaySystemSound(SystemSoundID(kSystemSoundID_Vibrate))
            delegate?.didFind(code: stringValue)
        }
    }
    
    override func viewWillDisappear(_ animated: Bool) {
        super.viewWillDisappear(animated)
        if (captureSession?.isRunning == true) {
            captureSession.stopRunning()
        }
    }
}
