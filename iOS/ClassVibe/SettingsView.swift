import SwiftUI
import FirebaseAuth
import GoogleSignIn

struct SettingsView: View {
    @AppStorage("haptics_enabled") private var hapticsEnabled: Bool = true
    @AppStorage("is_logged_in") private var isLoggedIn: Bool = false
    @AppStorage("auth_provider") private var authProvider: String = "none"
    @AppStorage("last_student_name") private var lastStudentName: String = ""
    @AppStorage("login_method_label") private var loginMethodLabel: String = ""
    @State private var showSignOutAlert = false

    var body: some View {
        Form {
            Section(header: Text("フィードバック")) {
                Toggle("ボタン振動", isOn: $hapticsEnabled)
                Text("反応ボタンを押した時の振動を切り替えます。")
                    .font(.caption)
                    .foregroundColor(.gray)
            }

            Section {
                Button(role: .destructive) {
                    showSignOutAlert = true
                } label: {
                    Text("アカウントからログアウト")
                }
            }
        }
        .navigationTitle("設定")
        .alert("ログアウトしますか？", isPresented: $showSignOutAlert) {
            Button("キャンセル", role: .cancel) {}
            Button("ログアウト", role: .destructive) {
                signOut()
            }
        } message: {
            Text("ログアウトすると再度ログインが必要になります。")
        }
    }

    private func signOut() {
        do {
            try Auth.auth().signOut()
        } catch {
            print("SignOut error: \(error.localizedDescription)")
        }
        GIDSignIn.sharedInstance.signOut()
        authProvider = "none"
        isLoggedIn = false
        lastStudentName = ""
        loginMethodLabel = ""
    }
}
