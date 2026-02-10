import SwiftUI
import PhotosUI
import FirebaseAuth
import FirebaseDatabase

struct GachaProfileView: View {
    @ObservedObject var viewModel: StudentViewModel
    @AppStorage("profile_avatar_path") private var avatarPath: String = ""
    @State private var titleText: String = "はじめの一歩"
    @State private var roleText: String = "student"
    @State private var expTotal: Int = 0
    @State private var dims: [String: Int] = [:]
    @State private var logs: [(id: String, summary: String, hint: String, exp: Int, message: String)] = []
    @State private var loading = true
    @State private var showTitleLevelUp = false
    @State private var upgradedTitle = ""
    @State private var selectedBadge: BadgeInfo? = nil
    @State private var showBadgeDetail = false
    @State private var selectedAvatarItem: PhotosPickerItem? = nil
    @State private var avatarImage: UIImage? = nil
    @State private var showBadgeBack = false
    @State private var showAllLogs = false

    private let dbRef = Database.database(url: "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/").reference()

    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 6) {
                    profileHeader
                    growthFlipCard
                }
                .padding()
            }
            .navigationTitle("成長プロフィール")
            .toolbar {
                NavigationLink(destination: SettingsView()) {
                    Image(systemName: "gearshape.fill")
                        .foregroundColor(.gray)
                }
            }
            .onAppear(perform: loadGrowth)
            .onAppear(perform: loadAvatarFromDisk)
            .overlay(titleLevelUpOverlay)
            .overlay(badgeDetailOverlay)
        }
    }

    private var profileHeader: some View {
        VStack(spacing: 10) {
            PhotosPicker(selection: $selectedAvatarItem, matching: .images, photoLibrary: .shared()) {
                ZStack(alignment: .bottomTrailing) {
                    Group {
                        if let avatarImage = avatarImage {
                            Image(uiImage: avatarImage)
                                .resizable()
                                .scaledToFill()
                        } else {
                            Image(systemName: "person.crop.circle.fill")
                                .resizable()
                                .scaledToFit()
                                .foregroundColor(Color.gray.opacity(0.4))
                                .padding(10)
                        }
                    }
                    .frame(width: 96, height: 96)
                    .background(Color.white)
                    .clipShape(Circle())
                    .shadow(color: .black.opacity(0.12), radius: 6, x: 0, y: 3)

                    Image(systemName: "camera.fill")
                        .font(.system(size: 12, weight: .bold))
                        .foregroundColor(.white)
                        .padding(6)
                        .background(Color.black.opacity(0.6))
                        .clipShape(Circle())
                        .offset(x: -2, y: -2)
                }
            }
            .onChange(of: selectedAvatarItem) { newItem in
                guard let newItem = newItem else { return }
                Task {
                    if let data = try? await newItem.loadTransferable(type: Data.self),
                       let uiImage = UIImage(data: data) {
                        await MainActor.run {
                            self.avatarImage = uiImage
                        }
                        saveAvatarToDisk(data: data)
                    }
                }
            }

            Text(displayNameText()).font(.title3).bold()
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 2)
    }

    private var growthFlipCard: some View {
        let rotation = showBadgeBack ? 180.0 : 0.0
        return ZStack {
            growthFrontCard
                .opacity(showBadgeBack ? 0 : 1)
            growthBackCard
                .opacity(showBadgeBack ? 1 : 0)
                .rotation3DEffect(.degrees(180), axis: (x: 0, y: 1, z: 0))
        }
        .rotation3DEffect(.degrees(rotation), axis: (x: 0, y: 1, z: 0))
        .animation(.spring(response: 0.45, dampingFraction: 0.86), value: showBadgeBack)
        .onTapGesture { showBadgeBack.toggle() }
    }

    private var growthFrontCard: some View {
        let levelData = levelInfo(from: expTotal)
        return VStack(alignment: .leading, spacing: 12) {
            Text("Lv.\(levelData.level)").font(.title2).bold()
            ProgressView(value: levelData.progress)
                .progressViewStyle(.linear)
                .tint(.orange)
            Text("EXP \(levelData.currentInLevel) / \(levelData.needForNext)")
                .font(.caption).foregroundColor(.gray)

            VStack(spacing: 10) {
                HStack {
                    statCard("理解", "\(dims["understand", default: 0])", .green)
                    statCard("困惑", "\(dims["question", default: 0])", .blue)
                    statCard("協力", "\(dims["collab", default: 0])", .purple)
                }
                HStack {
                    statCard("参加", "\(dims["engagement", default: 0])", .pink)
                    statCard("安定", "\(dims["stability", default: 0])", .teal)
                    statCard("総EXP", "\(expTotal)", .orange)
                }
            }

            logsPreview
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 1)
    }

    private var logsPreview: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("成長ログ").font(.headline)
                Spacer()
                Button("すべて見る") { showAllLogs = true }
                    .font(.caption).foregroundColor(.blue)
            }
            if logs.isEmpty {
                Text(loading ? "読み込み中..." : "まだログがありません")
                    .foregroundColor(.gray)
            } else {
                ForEach(logs.prefix(4), id: \.id) { log in
                    logRow(log)
                }
            }
        }
        .sheet(isPresented: $showAllLogs) {
            NavigationView {
                ScrollView {
                    VStack(alignment: .leading, spacing: 12) {
                        if logs.isEmpty {
                            Text(loading ? "読み込み中..." : "まだログがありません")
                                .foregroundColor(.gray)
                        } else {
                            ForEach(logs, id: \.id) { log in
                                logRow(log)
                            }
                        }
                    }
                    .padding()
                }
                .navigationTitle("成長ログ")
                .toolbar {
                    ToolbarItem(placement: .navigationBarTrailing) {
                        Button("閉じる") { showAllLogs = false }
                    }
                }
            }
        }
    }

    private var growthBackCard: some View {
        let badges = badgeCatalog()
        let columns = Array(repeating: GridItem(.flexible(), spacing: 10), count: 4)
        return VStack(alignment: .leading, spacing: 12) {
            ScrollView {
                LazyVGrid(columns: columns, spacing: 10) {
                    ForEach(badges) { badge in
                        badgeIconView(badge)
                            .onTapGesture { showBadgeInfo(for: badge) }
                    }
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 1)
    }

    private var levelCard: some View {
        let levelData = levelInfo(from: expTotal)
        return VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Lv.\(levelData.level)").font(.title2).bold()
                Spacer()
                Text("EXP \(levelData.currentInLevel) / \(levelData.needForNext)")
                    .font(.caption).foregroundColor(.gray)
            }
            ProgressView(value: levelData.progress)
                .progressViewStyle(.linear)
                .tint(.orange)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 1)
    }

    private var dimensionCards: some View {
        VStack(spacing: 10) {
            HStack {
                statCard("理解", "\(dims["understand", default: 0])", .green)
                statCard("困惑", "\(dims["question", default: 0])", .blue)
                statCard("協力", "\(dims["collab", default: 0])", .purple)
            }
            HStack {
                statCard("参加", "\(dims["engagement", default: 0])", .pink)
                statCard("安定", "\(dims["stability", default: 0])", .teal)
                statCard("総EXP", "\(expTotal)", .orange)
            }
        }
    }

    private func logRow(_ log: (id: String, summary: String, hint: String, exp: Int, message: String)) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(log.summary).font(.subheadline).lineLimit(2)
                Spacer()
                Text("+\(log.exp) EXP").font(.caption).bold().foregroundColor(.green)
            }
            if !log.message.isEmpty {
                Text("💬 \(log.message)").font(.caption).foregroundColor(.gray)
            }
            if !log.hint.isEmpty {
                Text("次の目標: \(log.hint)").font(.caption).foregroundColor(.blue)
            }
        }
        .padding(10)
        .background(Color.white)
        .cornerRadius(10)
    }

    private func statCard(_ label: String, _ value: String, _ color: Color) -> some View {
        VStack {
            Text(label).font(.caption).foregroundColor(.gray)
            Text(value).font(.headline).bold().foregroundColor(color)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 12)
        .background(Color.white)
        .cornerRadius(12)
        .shadow(radius: 1)
    }

    private func loadGrowth() {
        guard let uid = Auth.auth().currentUser?.uid else {
            loading = false
            return
        }
        loading = true

        dbRef.child("users").child(uid).observeSingleEvent(of: .value) { snap in
            let userData = snap.value as? [String: Any] ?? [:]
            roleText = userData["role"] as? String ?? "student"

            let growth = userData["growth"] as? [String: Any] ?? [:]
            let nextTitle = growth["title_current"] as? String ?? "はじめの一歩"
            titleText = nextTitle
            expTotal = growth["exp_total"] as? Int ?? Int((growth["exp_total"] as? Double) ?? 0)

            var parsedDims: [String: Int] = [:]
            let rawDims = growth["dims"] as? [String: Any] ?? [:]
            rawDims.forEach { key, val in
                parsedDims[key] = val as? Int ?? Int((val as? Double) ?? 0)
            }
            dims = parsedDims

            let prefKey = "last_title_\(uid)"
            let oldTitle = UserDefaults.standard.string(forKey: prefKey)
            if let oldTitle = oldTitle, oldTitle != nextTitle, !nextTitle.isEmpty {
                upgradedTitle = nextTitle
                withAnimation(.spring(response: 0.5, dampingFraction: 0.78)) {
                    showTitleLevelUp = true
                }
                DispatchQueue.main.asyncAfter(deadline: .now() + 2.2) {
                    withAnimation(.easeOut(duration: 0.25)) {
                        showTitleLevelUp = false
                    }
                }
            }
            UserDefaults.standard.set(nextTitle, forKey: prefKey)
        }

        dbRef.child("users").child(uid).child("growth_logs").observeSingleEvent(of: .value) { snap in
            var temp: [(id: String, summary: String, hint: String, exp: Int, message: String)] = []
            let logsData = snap.value as? [String: Any] ?? [:]
            for (key, value) in logsData {
                let row = value as? [String: Any] ?? [:]
                let summary = row["summary"] as? String ?? "成長記録"
                let hint = row["next_hint"] as? String ?? ""
                let exp = row["exp_gain"] as? Int ?? Int((row["exp_gain"] as? Double) ?? 0)
                let message = row["message"] as? String ?? ""
                temp.append((id: key, summary: summary, hint: hint, exp: exp, message: message))
            }
            logs = Array(temp.sorted(by: { $0.id > $1.id }).prefix(20))
            loading = false
        }
    }

    private var titleLevelUpOverlay: some View {
        Group {
            if showTitleLevelUp {
                ZStack {
                    Color.black.opacity(0.35).ignoresSafeArea()
                    VStack(spacing: 10) {
                        Text("🎉 称号アップ！").font(.title2).bold().foregroundColor(.white)
                        Text(upgradedTitle)
                            .font(.title3).bold()
                            .padding(.horizontal, 16).padding(.vertical, 10)
                            .background(Color.yellow)
                            .cornerRadius(12)
                    }
                    .padding(24)
                    .background(Color.indigo)
                    .cornerRadius(18)
                    .shadow(radius: 20)
                    .transition(.scale.combined(with: .opacity))
                }
            }
        }
    }

    private func levelInfo(from exp: Int) -> (level: Int, currentInLevel: Int, needForNext: Int, progress: Double) {
        var level = 1
        var remaining = max(0, exp)
        var need = 120
        while remaining >= need {
            remaining -= need
            level += 1
            need = 120 + ((level - 1) * 20)
        }
        let progress = need > 0 ? Double(remaining) / Double(need) : 0
        return (level, remaining, need, progress)
    }

    private func achievementBadges() -> [String] {
        var result: [String] = []
        if dims["understand", default: 0] >= 1 { result.append("理解の見習い") }
        if dims["question", default: 0] >= 10 { result.append("対話の火種") }
        if dims["collab", default: 0] >= 10 { result.append("チームブースター") }
        if dims["stability", default: 0] >= 8 { result.append("静かな支柱") }
        if dims["engagement", default: 0] >= 12 { result.append("行動派") }
        return result
    }

    private func badgeIconView(_ badge: BadgeInfo) -> some View {
        Group {
            if badge.unlocked {
                Image(badge.imageName)
                    .resizable()
                    .scaledToFill()
            } else {
                ZStack {
                    Circle().fill(Color.gray.opacity(0.25))
                    Image(systemName: "lock.fill")
                        .font(.system(size: 18, weight: .semibold))
                        .foregroundColor(.gray.opacity(0.6))
                }
            }
        }
        .frame(width: 64, height: 64)
        .clipShape(Circle())
        .shadow(color: .black.opacity(0.12), radius: 4, x: 0, y: 2)
    }

    private func allBadgeAssets() -> [String] {
        (1...28).map { String(format: "badge_%02d", $0) }
    }

    private func badgeCatalog() -> [BadgeInfo] {
        allBadgeAssets().map { id in
            let meta = badgeMetaFor(id)
            return BadgeInfo(
                id: id,
                title: meta.title,
                imageName: id,
                condition: meta.condition,
                unlocked: isBadgeUnlocked(id: id)
            )
        }
    }

    private func badgeMetaFor(_ id: String) -> (title: String, condition: String) {
        switch id {
        case "badge_01": return ("協力の見習い", "協力系の成長ポイントが5以上")
        case "badge_02": return ("インタラクション加速者", "参加系の成長ポイントが15以上（Lv6以上）")
        case "badge_03": return ("クラス守護バリア", "安定系の成長ポイントが40以上（Lv10以上）")
        case "badge_04": return ("クラス連結コア", "協力系の成長ポイントが60以上（Lv10以上）")
        case "badge_05": return ("ソクラテスの眼", "困惑系の成長ポイントが50以上（Lv10以上）")
        case "badge_06": return ("チームエンジン", "協力系の成長ポイントが25以上（Lv6以上）")
        case "badge_07": return ("ヒントハンター", "困惑系の成長ポイントが10以上（Lv6以上）")
        case "badge_08": return ("ムード点火師", "参加系の成長ポイントが30以上（Lv6以上）")
        case "badge_09": return ("リズムウォッチャー", "安定系の成長ポイントが12以上（Lv6以上）")
        case "badge_10": return ("不動のガーディアン", "安定系の成長ポイントが60以上（Lv10以上）")
        case "badge_11": return ("五角形レジェンド", "全ての成長ポイントが30以上（Lv14以上）")
        case "badge_12": return ("全体ビートメーカー", "参加系の成長ポイントが90以上（Lv14以上）")
        case "badge_13": return ("共創キャプテン", "協力系の成長ポイントが40以上（Lv10以上）")
        case "badge_14": return ("参加の見習い", "参加系の成長ポイントが5以上")
        case "badge_15": return ("安定の見習い", "安定系の成長ポイントが5以上")
        case "badge_16": return ("対話イグナイター", "困惑系の成長ポイントが20以上（Lv6以上）")
        case "badge_17": return ("思考ダブルコア", "理解系と困惑系の成長ポイントが40/35以上（Lv14以上）")
        case "badge_18": return ("思考ナビゲーター", "理解系の成長ポイントが40以上（Lv10以上）")
        case "badge_19": return ("授業プッシャー", "参加系の成長ポイントが50以上（Lv10以上）")
        case "badge_20": return ("洞察チェイサー", "困惑系の成長ポイントが35以上（Lv10以上）")
        case "badge_21": return ("熱量スター", "参加系の成長ポイントが70以上（Lv10以上）")
        case "badge_22": return ("理解の見習い", "理解系の成長ポイントが5以上")
        case "badge_23": return ("真理トラッカー", "理解系の成長ポイントが60以上（Lv10以上）")
        case "badge_24": return ("知識クラフター", "理解系の成長ポイントが25以上（Lv6以上）")
        case "badge_25": return ("秩序リペアラー", "安定系の成長ポイントが25以上（Lv6以上）")
        case "badge_26": return ("紅青コーディネーター", "協力系の成長ポイントが12以上（Lv6以上）")
        case "badge_27": return ("解法トラベラー", "理解系の成長ポイントが12以上（Lv6以上）")
        case "badge_28": return ("質問の見習い", "困惑系の成長ポイントが4以上")
        default: return ("バッジ", "条件データ準備中")
        }
    }

    private func isBadgeUnlocked(id: String) -> Bool {
        let understand = dims["understand", default: 0]
        let question = dims["question", default: 0]
        let collab = dims["collab", default: 0]
        let engagement = dims["engagement", default: 0]
        let stability = dims["stability", default: 0]
        let level = levelInfo(from: expTotal).level
        switch id {
        case "badge_01": return collab >= 5
        case "badge_02": return engagement >= 15 && level >= 6
        case "badge_03": return stability >= 40 && level >= 10
        case "badge_04": return collab >= 60 && level >= 10
        case "badge_05": return question >= 50 && level >= 10
        case "badge_06": return collab >= 25 && level >= 6
        case "badge_07": return question >= 10 && level >= 6
        case "badge_08": return engagement >= 30 && level >= 6
        case "badge_09": return stability >= 12 && level >= 6
        case "badge_10": return stability >= 60 && level >= 10
        case "badge_11": return understand >= 30 && question >= 30 && collab >= 30 && engagement >= 30 && stability >= 30 && level >= 14
        case "badge_12": return engagement >= 90 && level >= 14
        case "badge_13": return collab >= 40 && level >= 10
        case "badge_14": return engagement >= 5
        case "badge_15": return stability >= 5
        case "badge_16": return question >= 20 && level >= 6
        case "badge_17": return understand >= 40 && question >= 35 && level >= 14
        case "badge_18": return understand >= 40 && level >= 10
        case "badge_19": return engagement >= 50 && level >= 10
        case "badge_20": return question >= 35 && level >= 10
        case "badge_21": return engagement >= 70 && level >= 10
        case "badge_22": return understand >= 5
        case "badge_23": return understand >= 60 && level >= 10
        case "badge_24": return understand >= 25 && level >= 6
        case "badge_25": return stability >= 25 && level >= 6
        case "badge_26": return collab >= 12 && level >= 6
        case "badge_27": return understand >= 12 && level >= 6
        case "badge_28": return question >= 4
        default: return false
        }
    }

    private func showBadgeInfo(for badge: BadgeInfo) {
        selectedBadge = badge
        withAnimation(.easeInOut(duration: 0.2)) {
            showBadgeDetail = true
        }
    }

    private var badgeDetailOverlay: some View {
        Group {
            if showBadgeDetail, let info = selectedBadge {
                ZStack {
                    Color.black.opacity(0.5).ignoresSafeArea()
                        .onTapGesture { hideBadgeDetail() }
                    VStack(spacing: 14) {
                        Group {
                            if info.unlocked {
                                Image(info.imageName)
                                    .resizable()
                                    .scaledToFill()
                            } else {
                                ZStack {
                                    Circle().fill(Color.gray.opacity(0.25))
                                    Image(systemName: "questionmark")
                                        .font(.system(size: 36, weight: .semibold))
                                        .foregroundColor(.gray.opacity(0.6))
                                }
                            }
                        }
                        .frame(width: 180, height: 180)
                        .clipShape(Circle())
                        .overlay(
                            Circle()
                                .stroke(Color.yellow.opacity(0.85), lineWidth: 6)
                                .shadow(color: Color.yellow.opacity(0.6), radius: 12, x: 0, y: 0)
                        )
                        .shadow(color: .black.opacity(0.2), radius: 12, x: 0, y: 6)

                        Text(info.title).font(.title3).bold()
                        Text("獲得条件: \(info.condition)")
                            .font(.callout)
                            .foregroundColor(.gray)
                            .multilineTextAlignment(.center)
                            .padding(.horizontal, 18)

                        Button(action: hideBadgeDetail) {
                            Text("閉じる").font(.headline).padding(.horizontal, 24).padding(.vertical, 8)
                        }
                        .background(Color.black.opacity(0.08))
                        .cornerRadius(14)
                    }
                    .padding(24)
                    .background(Color.white)
                    .cornerRadius(20)
                    .shadow(radius: 24)
                    .transition(.scale.combined(with: .opacity))
                }
            }
        }
    }

    private func hideBadgeDetail() {
        withAnimation(.easeInOut(duration: 0.2)) {
            showBadgeDetail = false
        }
    }

    private func displayNameText() -> String {
        if !viewModel.studentName.isEmpty { return viewModel.studentName }
        if let name = Auth.auth().currentUser?.displayName, !name.isEmpty { return name }
        if let mail = Auth.auth().currentUser?.email, !mail.isEmpty {
            return String(mail.split(separator: "@").first ?? "Student")
        }
        return "Student"
    }

    private func avatarFileURL() -> URL? {
        FileManager.default.urls(for: .documentDirectory, in: .userDomainMask).first?.appendingPathComponent("profile_avatar.jpg")
    }

    private func loadAvatarFromDisk() {
        guard let url = avatarFileURL(),
              let data = try? Data(contentsOf: url),
              !data.isEmpty,
              let image = UIImage(data: data) else { return }
        avatarImage = image
        avatarPath = url.path
    }

    private func saveAvatarToDisk(data: Data) {
        guard let url = avatarFileURL() else { return }
        do {
            try data.write(to: url, options: [.atomic])
            avatarPath = url.path
        } catch {
            print("Save avatar failed: \(error.localizedDescription)")
        }
    }
}

private struct BadgeInfo: Identifiable {
    let id: String
    let title: String
    let imageName: String
    let condition: String
    let unlocked: Bool
}
