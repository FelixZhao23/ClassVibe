//
//  GifImage.swift
//  ClassVibe
//
//  Created by cmStudent on 2026/02/03.
//
import SwiftUI
import WebKit

struct GifImage: UIViewRepresentable {
    private let name: String

    init(_ name: String) {
        self.name = name
    }

    func makeUIView(context: Context) -> WKWebView {
        let webView = WKWebView()
        
        // 设置背景透明，这样你的 GIF 就能融入背景色了
        webView.isOpaque = false
        webView.backgroundColor = .clear
        webView.scrollView.backgroundColor = .clear
        
        // 禁止滚动，只展示图片
        webView.scrollView.isScrollEnabled = false
        
        // 读取 GIF 数据
        if let url = Bundle.main.url(forResource: name, withExtension: "gif"),
           let data = try? Data(contentsOf: url) {
            
            webView.load(
                data,
                mimeType: "image/gif",
                characterEncodingName: "UTF-8",
                baseURL: url.deletingLastPathComponent()
            )
        }
        
        return webView
    }

    func updateUIView(_ uiView: WKWebView, context: Context) {
        // 不需要更新逻辑，只负责播放
    }
}
