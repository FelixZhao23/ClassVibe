package jp.ac.jec.cm0105.classtest;

import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ValueEventListener;

import java.util.ArrayList;
import java.util.List;

public class MainActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private CourseAdapter adapter;
    private List<Course> courseList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main); // 确保你的 activity_main.xml 已经按我之前的建议改成了含有 RecyclerView 的布局

        // 2. 处理欢迎语
        String userName = getIntent().getStringExtra("USER_NAME");
        TextView tvWelcome = findViewById(R.id.tv_welcome);
        if (tvWelcome != null) {
            tvWelcome.setText("你好, " + userName);
        }

        // 3. 【必须添加】初始化 RecyclerView
        recyclerView = findViewById(R.id.rv_courses);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));

        // 4. 【必须添加】初始化适配器并绑定到 RecyclerView
        adapter = new CourseAdapter(courseList);
        recyclerView.setAdapter(adapter);

        // 5. 【核心核心】调用你写好的函数去拿数据！
        getCoursesFromFirebase();

        Toast.makeText(this, "欢迎来到主界面：" + userName, Toast.LENGTH_SHORT).show();

        // 在 MainActivity 的 onCreate 里
        String targetId = getIntent().getStringExtra("TARGET_COURSE_ID");
        if (targetId != null) {
            // 如果有目标ID，直接跳转到教室，或者自动筛选列表
            // 甚至可以直接在这里 start ClassroomActivity
        }

    }//onCreate end


    //获取课程列表item
    private void getCoursesFromFirebase() {
        FirebaseDatabase database = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app");
        DatabaseReference myRef = database.getReference("courses");

        myRef.addValueEventListener(new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot dataSnapshot) {
                // 1. 清空旧数据，防止重复显示
                courseList.clear();

                for (DataSnapshot snapshot : dataSnapshot.getChildren()) {
                    Course course = snapshot.getValue(Course.class);
                    if (course != null) {
                        courseList.add(course);
                    }
                }

                // 2. 数据下载完了，适配器去刷新屏幕上的列表
                if (adapter != null) {
                    adapter.notifyDataSetChanged();
                }

                Log.d("FirebaseData", "获取到的课程数量: " + courseList.size());
            }

            @Override
            public void onCancelled(DatabaseError error) {
                Log.w("FirebaseData", "读取失败", error.toException());
            }
        });
    }

}