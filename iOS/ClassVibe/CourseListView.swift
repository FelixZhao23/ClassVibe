//
//  CourseListView.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/01/13.
//

import SwiftUI

struct CourseListView: View {
    @ObservedObject var viewModel: StudentViewModel
    
    var body: some View {
        List(viewModel.courses) { course in
            Button(action: { viewModel.enterCourse(id: course.id) }) {
                HStack {
                    VStack(alignment: .leading) {
                        Text(course.title).font(.headline)
                        Text(course.teacherName).font(.caption).foregroundColor(.gray)
                    }
                    Spacer()
                    if course.isActive {
                        Text("LIVE")
                            .font(.caption).bold()
                            .padding(4)
                            .background(Color.red)
                            .foregroundColor(.white)
                            .cornerRadius(4)
                    }
                }
            }
        }
        .navigationTitle("选择课程")
    }
}
