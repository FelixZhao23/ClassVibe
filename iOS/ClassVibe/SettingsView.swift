import SwiftUI

struct SettingsView: View {
    @AppStorage("haptics_enabled") private var hapticsEnabled: Bool = true

    var body: some View {
        Form {
            Section(header: Text("フィードバック")) {
                Toggle("ボタン振動", isOn: $hapticsEnabled)
                Text("反応ボタンを押した時の振動を切り替えます。")
                    .font(.caption)
                    .foregroundColor(.gray)
            }
        }
        .navigationTitle("設定")
    }
}

