//
//  GachaProfileView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

struct GachaProfileView: View {
    @ObservedObject var viewModel: StudentViewModel
    @State private var showResultModal = false
    @State private var lastReward: RewardItem?
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 20) {
                    // Profile Header
                    VStack {
                        Image(systemName: "person.crop.circle.fill")
                            .resizable().frame(width: 80, height: 80).foregroundColor(.blue)
                        Text(viewModel.studentName).font(.title).bold()
                        HStack {
                            VStack {
                                Text("\(viewModel.vibePoints)").font(.title2).bold().foregroundColor(.yellow)
                                Text("Vibe 积分").font(.caption)
                            }
                            Divider().frame(height: 30)
                            VStack {
                                Text("\(viewModel.inventory.count)").font(.title2).bold()
                                Text("收藏品").font(.caption)
                            }
                        }
                        .padding().background(Color.white).cornerRadius(15).shadow(radius: 2)
                    }
                    .padding()
                    
                    // Gacha Machine
                    VStack {
                        Text("✨ 幸运扭蛋机 ✨").font(.headline).foregroundColor(.purple)
                        Image(systemName: "cube.box.fill")
                            .resizable().scaledToFit().frame(height: 120)
                            .foregroundColor(.pink).padding()
                        
                        Button(action: {
                            if let item = viewModel.spinGacha() {
                                lastReward = item
                                showResultModal = true
                            }
                        }) {
                            VStack {
                                Text("抽奖").bold()
                                Text("50 积分").font(.caption)
                            }
                            .foregroundColor(.white).frame(width: 150, height: 50)
                            .background(viewModel.vibePoints >= 50 ? Color.pink : Color.gray).cornerRadius(25)
                        }
                        .disabled(viewModel.vibePoints < 50)
                    }
                    .padding().background(Color.pink.opacity(0.1)).cornerRadius(20).padding(.horizontal)
                    
                    // Inventory
                    VStack(alignment: .leading) {
                        Text("我的背包").font(.headline).padding(.leading)
                        LazyVGrid(columns: [GridItem(.adaptive(minimum: 100))], spacing: 10) {
                            ForEach(viewModel.inventory) { item in
                                VStack {
                                    Text(item.icon).font(.largeTitle)
                                    Text(item.name).font(.caption).bold()
                                }
                                .padding().background(Color.white).cornerRadius(10).shadow(radius: 2)
                            }
                        }
                        .padding()
                    }
                }
            }
            .navigationTitle("个人中心")
            .sheet(isPresented: $showResultModal) {
                if let item = lastReward {
                    RewardResultView(item: item)
                }
            }
        }
    }
}

struct RewardResultView: View {
    let item: RewardItem
    @Environment(\.presentationMode) var presentationMode
    
    var body: some View {
        ZStack {
            Color.black.opacity(0.8).ignoresSafeArea()
            VStack(spacing: 20) {
                Text("🎉 恭喜获得 🎉").font(.title).foregroundColor(.white).bold()
                VStack {
                    Text(item.icon).font(.system(size: 100))
                    Text(item.name).font(.title2).bold()
                    Text(item.rarity).font(.headline).foregroundColor(.purple)
                }
                .padding(40).background(Color.white).cornerRadius(20).shadow(radius: 20)
                
                Button("收下") { presentationMode.wrappedValue.dismiss() }
                    .padding().background(Color.yellow).foregroundColor(.black).cornerRadius(10)
            }
        }
    }
}
