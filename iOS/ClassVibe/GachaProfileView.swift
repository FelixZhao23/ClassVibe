import SwiftUI
import FirebaseAuth
import FirebaseDatabase

struct GachaProfileView: View {
    @ObservedObject var viewModel: StudentViewModel
    @State private var titleText: String = "はじめの一歩"
    @State private var roleText: String = "student"
    @State private var expTotal: Int = 0
    @State private var dims: [String: Int] = [:]
    @State private var logs: [(id: String, summary: String, hint: String, exp: Int)] = []
    @State private var loading = true
    @State private var showTitleLevelUp = false
    @State private var upgradedTitle = ""

    private let dbRef = Database.database(url: "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/").reference()

    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 16) {
                    profileHeader
                    levelCard
                    dimensionCards
                    achievementCard
                    logsCard
                }
                .padding()
            }
            .navigationTitle("成長プロフィール")
            .onAppear(perform: loadGrowth)
            .overlay(titleLevelUpOverlay)
        }
    }

    private var profileHeader: some View {
        VStack(spacing: 10) {
            Image(systemName: roleText == "teacher" ? "person.crop.square.badge.checkmark.fill" : "person.crop.circle.badge.checkmark")
                .resizable()
                .frame(width: 82, height: 82)
                .foregroundColor(roleText == "teacher" ? .indigo : .blue)
            Text(displayNameText()).font(.title3).bold()
            Text(roleText == "teacher" ? "Teacher" : "Student")
                .font(.caption).foregroundColor(.gray)
            Text(titleText)
                .font(.headline)
                .padding(.horizontal, 12).padding(.vertical, 6)
                .background(Color.indigo.opacity(0.12))
                .cornerRadius(14)
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 2)
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
                statCard("質問", "\(dims["question", default: 0])", .blue)
                statCard("協力", "\(dims["collab", default: 0])", .purple)
            }
            HStack {
                statCard("参加", "\(dims["engagement", default: 0])", .pink)
                statCard("安定", "\(dims["stability", default: 0])", .teal)
                statCard("総EXP", "\(expTotal)", .orange)
            }
        }
    }

    private var achievementCard: some View {
        let badges = achievementBadges()
        return VStack(alignment: .leading, spacing: 8) {
            Text("実績バッジ").font(.headline)
            if badges.isEmpty {
                Text("授業に参加してバッジを集めよう").font(.caption).foregroundColor(.gray)
            } else {
                ForEach(badges, id: \.self) { badge in
                    Text("🏅 \(badge)").font(.subheadline)
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.white)
        .cornerRadius(16)
        .shadow(radius: 1)
    }

    private var logsCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("成長ログ").font(.headline)
            if logs.isEmpty {
                Text(loading ? "読み込み中..." : "まだログがありません")
                    .foregroundColor(.gray)
            } else {
                ForEach(logs, id: \.id) { log in
                    VStack(alignment: .leading, spacing: 4) {
                        HStack {
                            Text(log.summary).font(.subheadline).lineLimit(2)
                            Spacer()
                            Text("+\(log.exp) EXP").font(.caption).bold().foregroundColor(.green)
                        }
                        if !log.hint.isEmpty {
                            Text("次の目標: \(log.hint)").font(.caption).foregroundColor(.blue)
                        }
                        Text(log.id).font(.caption2).foregroundColor(.gray)
                    }
                    .padding(10)
                    .background(Color.white)
                    .cornerRadius(10)
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.gray.opacity(0.08))
        .cornerRadius(16)
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
            var temp: [(id: String, summary: String, hint: String, exp: Int)] = []
            let logsData = snap.value as? [String: Any] ?? [:]
            for (key, value) in logsData {
                let row = value as? [String: Any] ?? [:]
                let summary = row["summary"] as? String ?? "成長記録"
                let hint = row["next_hint"] as? String ?? ""
                let exp = row["exp_gain"] as? Int ?? Int((row["exp_gain"] as? Double) ?? 0)
                temp.append((id: key, summary: summary, hint: hint, exp: exp))
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
        if dims["question", default: 0] >= 10 { result.append("対話の火種") }
        if dims["collab", default: 0] >= 10 { result.append("チームブースター") }
        if dims["stability", default: 0] >= 8 { result.append("静かな支柱") }
        if dims["engagement", default: 0] >= 12 { result.append("行動派") }
        return result
    }

    private func displayNameText() -> String {
        if !viewModel.studentName.isEmpty { return viewModel.studentName }
        if let name = Auth.auth().currentUser?.displayName, !name.isEmpty { return name }
        if let mail = Auth.auth().currentUser?.email, !mail.isEmpty {
            return String(mail.split(separator: "@").first ?? "Student")
        }
        return "Student"
    }
}
