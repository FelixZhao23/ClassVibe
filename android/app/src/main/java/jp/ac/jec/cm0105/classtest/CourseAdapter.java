package jp.ac.jec.cm0105.classtest;

import android.content.Intent;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;

public class CourseAdapter extends RecyclerView.Adapter<CourseAdapter.CourseViewHolder> {

    private List<Course> courseList;

    public CourseAdapter(List<Course> courseList) {
        this.courseList = courseList;
    }

    @NonNull
    @Override
    public CourseViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // 关联你刚才创建的 item_course.xml
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_course, parent, false);
        return new CourseViewHolder(view);
    }


    @Override
    public void onBindViewHolder(@NonNull CourseViewHolder holder, int position) {
        // 1. 只获取一次数据对象
        Course course = courseList.get(position);

        // 2. 设置课程标题 (使用 .title)
        holder.tvCourseName.setText(course.title);

        // 3. 设置老师信息和时间
        holder.tvTeacherName.setText("讲师: " + course.teacher_id + " | 时间: " + course.time);

        // 4. 根据活跃状态设置颜色
        if (!course.is_active) {
            holder.tvCourseName.setTextColor(Color.GRAY);
        } else {
            holder.tvCourseName.setTextColor(Color.BLACK);
        }

        // 5. 点击跳转到课堂界面 (ClassroomActivity)
        holder.itemView.setOnClickListener(v -> {
            android.content.Intent intent = new android.content.Intent(v.getContext(), ClassroomActivity.class);
            // 传递课程标题过去
            intent.putExtra("COURSE_TITLE", course.title);
            v.getContext().startActivity(intent);
        });
    }


    @Override
    public int getItemCount() {
        return courseList.size();
    }

    static class CourseViewHolder extends RecyclerView.ViewHolder {
        TextView tvCourseName, tvTeacherName;

        public CourseViewHolder(@NonNull View itemView) {
            super(itemView);
            tvCourseName = itemView.findViewById(R.id.tv_course_name);
            tvTeacherName = itemView.findViewById(R.id.tv_teacher_name);
        }
    }
}