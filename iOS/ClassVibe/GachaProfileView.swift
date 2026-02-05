import SwiftUI
import FirebaseAuth
import FirebaseDatabase

struct GachaProfileView: View {
    @ObservedObject var viewModel: StudentViewModel
    @State private var titleText: String = "はじめの一歩"
    @State private var expTotal: Int = 0
    @State private var dims: [String: Int] = [:]
    @State private var logs: [(id: String, summary: String, exp: Int)] = []
    @State private var loading = true

    private let dbRef = Database.database(url: "https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app/").reference()

    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 16) {
                    VStack(spacing: 8) {
                        Image(systemName: "person.crop.circle.fill")
                            .resizable().frame(width: 78, height: 78).foregroundColor(.blue)
                        Text(viewModel.studentName.isEmpty ? "Student" : viewModel.studentName)
                            .font(.title2).bold()
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

                    HStack {
                        statCard("EXP", "\(expTotal)", .orange)
                        statCard("理解", "\(dims["understand", default: 0])", .green)
                        statCard("質問", "\(dims["question", default: 0])", .blue)
                    }
                    HStack {
                        statCard("協力", "\(dims["collab", default: 0])", .purple)
                        statCard("参加", "\(dims["engagement", default: 0])", .pink)
                        statCard("安定", "\(dims["stability", default: 0])", .teal)
                    }

                    VStack(alignment: .leading, spacing: 10) {
                        Text("成長ログ").font(.headline)
                        if logs.isEmpty {
                            Text(loading ? "読み込み中..." : "まだログがありません")
                                .foregroundColor(.gray)
                        } else {
                            ForEach(logs, id: \.id) { log in
                                HStack {
                                    VStack(alignment: .leading) {
                                        Text(log.summary).font(.subheadline).lineLimit(2)
                                        Text(log.id).font(.caption2).foregroundColor(.gray)
                                    }
                                    Spacer()
                                    Text("+\(log.exp) EXP").font(.caption).bold().foregroundColor(.green)
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
                .padding()
            }
            .navigationTitle("成長プロフィール")
            .onAppear(perform: loadGrowth)
        }
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

        dbRef.child("users").child(uid).child("growth").observeSingleEvent(of: .value) { snap in
            let data = snap.value as? [String: Any] ?? [:]
            titleText = data["title_current"] as? String ?? "はじめの一歩"
            expTotal = data["exp_total"] as? Int ?? Int((data["exp_total"] as? Double) ?? 0)
            var parsedDims: [String: Int] = [:]
            let rawDims = data["dims"] as? [String: Any] ?? [:]
            rawDims.forEach { key, val in
                parsedDims[key] = val as? Int ?? Int((val as? Double) ?? 0)
            }
            dims = parsedDims
        }

        dbRef.child("users").child(uid).child("growth_logs").observeSingleEvent(of: .value) { snap in
            var temp: [(id: String, summary: String, exp: Int)] = []
            let logsData = snap.value as? [String: Any] ?? [:]
            for (key, value) in logsData {
                let row = value as? [String: Any] ?? [:]
                let summary = row["summary"] as? String ?? "成長記録"
                let exp = row["exp_gain"] as? Int ?? Int((row["exp_gain"] as? Double) ?? 0)
                temp.append((id: key, summary: summary, exp: exp))
            }
            logs = temp.sorted(by: { $0.id > $1.id }).prefix(20).map { $0 }
            loading = false
        }
    }
}
