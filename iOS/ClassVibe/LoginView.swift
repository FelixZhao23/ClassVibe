//
//  LoginView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

struct LoginView: View {
    @Binding var studentName: String
    var onLogin: () -> Void
    
    var body: some View {
        ZStack {
            Color.blue.opacity(0.1).ignoresSafeArea()
            VStack(spacing: 30) {
                Image(systemName: "gamecontroller.fill")
                    .resizable().scaledToFit().frame(width: 80)
                    .foregroundColor(.blue)
                
                Text("ClassVibe 2.0").font(.largeTitle).bold()
                
                VStack(alignment: .leading) {
                    Text("请输入名字").font(.caption).foregroundColor(.gray)
                    TextField("例如：王瑛琦", text: $studentName)
                        .padding()
                        .background(Color.white)
                        .cornerRadius(10)
                        .shadow(radius: 1)
                }
                .padding(.horizontal)
                
                Button(action: onLogin) {
                    Text("开始上课")
                        .bold()
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.blue)
                        .foregroundColor(.white)
                        .cornerRadius(10)
                        .shadow(radius: 5)
                }
                .padding(.horizontal)
            }
        }
    }
}
