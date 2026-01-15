package jp.ac.jec.cm0105.classtest;

import com.google.firebase.database.IgnoreExtraProperties;

@IgnoreExtraProperties // 忽略数据库中存在但类中没定义的字段（比如 reactions）
public class Course {
    // 这里的变量名必须和 Firebase 里的 Key 完全一样！
    public String title;      // 对应 "title": "王瑛琦大讲堂"
    public String teacher_id; // 对应 "teacher_id": "teacher_01"
    public String date;       // 对应 "2026-01-07"
    public String time;       // 对应 "16:06"
    public boolean is_active; // 对应 true/false

    // 1. 必须保留的空构造函数
    public Course() {}

    // 2. 方便使用的构造函数
    public Course(String title, String teacher_id, String date, String time, boolean is_active) {
        this.title = title;
        this.teacher_id = teacher_id;
        this.date = date;
        this.time = time;
        this.is_active = is_active;
    }
}