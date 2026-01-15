package jp.ac.jec.cm0105.classtest;

import android.animation.Animator;
import android.animation.AnimatorListenerAdapter;
import android.animation.ObjectAnimator;
import android.animation.PropertyValuesHolder;
import android.animation.ValueAnimator;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.TextView; // 加个TextView显示课程名或欢迎语
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ServerValue;
import com.google.firebase.database.ValueEventListener;

public class ClassroomActivity extends AppCompatActivity {

    // === UI 控件 ===
    private FrameLayout mochiContainer;
    private View mochiBody, mochiFace, snotBubble;
    private ImageView eyeLeft, eyeRight, mouth;
    private TextView tvClassTitle; // 用来显示当前在哪个课

    // === 核心变量 ===
    private DatabaseReference roomRef; // 指向 rooms/-Oiv...
    private String currentCourseId;    // 存Intent传来的ID
    private String myUserId;
    private String myUserName;

    // === 状态 & 动画变量 (保留你原来的逻辑) ===
    private float moodValue = 0;
    private boolean isSleeping = false;
    private boolean isPerformingAction = false;
    private long lastInteractionTime = System.currentTimeMillis();
    private ObjectAnimator idleAnimation;
    private Handler gameLoopHandler = new Handler();
    private Handler blinkHandler = new Handler();
    private TextView emoteQuestion;//馒头精头顶小问号

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_classroom);

        emoteQuestion = findViewById(R.id.emote_question);

        // 1. 获取 Intent 数据
        currentCourseId = getIntent().getStringExtra("COURSE_ID");
        myUserName = getIntent().getStringExtra("USER_NAME");

        if (currentCourseId == null) {
            Toast.makeText(this, "课程信息错误", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // 2. 初始化 Firebase 路径
        // 目标路径：rooms / {courseId}
        roomRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                .getReference("rooms")
                .child(currentCourseId);

        // 生成用户ID (为了统计人数)
        myUserId = java.util.UUID.randomUUID().toString();

        // 3. 绑定 UI
        mochiContainer = findViewById(R.id.mochi_container);
        mochiBody = findViewById(R.id.mochi_body);
        mochiFace = findViewById(R.id.mochi_face);
        snotBubble = findViewById(R.id.snot_bubble);
        eyeLeft = findViewById(R.id.eye_left);
        eyeRight = findViewById(R.id.eye_right);
        mouth = findViewById(R.id.mouth);
        tvClassTitle = findViewById(R.id.tv_class_title); // 确保XML里有这个ID

        if (tvClassTitle != null && myUserName != null) {
            tvClassTitle.setText("欢迎, " + myUserName);
        }

        // 4. ★ 核心功能：上线登记 & 按钮统计 ★
        setupOnlinePresence(); // 告诉老师我来了
        setupButtons();        // 设置按钮点击

        // 5. 启动动画循环
        gameLoopHandler.post(gameLoopRunnable);
        blinkHandler.post(blinkRunnable);
        startBreathAnimation();

        // 5. 启动动画循环
        gameLoopHandler.post(gameLoopRunnable);
        blinkHandler.post(blinkRunnable);
        startBreathAnimation();

        // ★★★ 新增：去拿课程标题和老师名字 ★★★
        fetchCourseInfo();
    }

    // ==========================================
    //           功能 A: 告诉老师有几个人
    // ==========================================
    private void setupOnlinePresence() {
        // 路径：rooms / {courseId} / members / {userId}
        DatabaseReference myMemberRef = roomRef.child("members").child(myUserId);

        // 写入数据：可以是简单的 true，也可以存名字
        myMemberRef.setValue(true);
        // 进阶：如果你想让老师看到谁来了，可以用 setValue(myUserName);

        // ★ 关键：断开连接(杀后台)时自动删除
        myMemberRef.onDisconnect().removeValue();
    }


    //           功能 B: 按钮点击 & 统计

    private void setupButtons() {
        // 1. Happy
        findViewById(R.id.btn_easy).setOnClickListener(v -> {
            handleFeedback(10);
            animateHappy();
            // 路径：rooms / {courseId} / reactions / happy -> +1
            roomRef.child("reactions").child("happy").setValue(ServerValue.increment(1));
        });

        // 2. Confused / Hard
        findViewById(R.id.btn_hard).setOnClickListener(v -> {
            handleFeedback(-15);
            animateSad();
            // 路径：rooms / {courseId} / reactions / confused -> +1
            roomRef.child("reactions").child("confused").setValue(ServerValue.increment(1));
        });

        // 3. Question / Fast
        findViewById(R.id.btn_fast).setOnClickListener(v -> {
            handleFeedback(0);
            animateConfused();
            // 路径：rooms / {courseId} / reactions / question -> +1
            roomRef.child("reactions").child("question").setValue(ServerValue.increment(1));
        });

        // 4. Lost / Slow
        findViewById(R.id.btn_slow).setOnClickListener(v -> {
            handleFeedback(-5);
            animateLost();
            // 路径：rooms / {courseId} / reactions / lost -> +1
            roomRef.child("reactions").child("lost").setValue(ServerValue.increment(1));
        });
    }

    //        以下全是原本的动画代码 (保持不变)

    private void handleFeedback(int impact) {
        lastInteractionTime = System.currentTimeMillis();
        if (isSleeping) setSleepingState(false);
        moodValue += impact;
        moodValue = Math.max(-100, Math.min(100, moodValue));
    }

    // ... (请保留之前发给你的 animateHappy, animateSad, animateConfused, animateLost 等所有动画方法) ...
    // ... (为了节省篇幅，这里假设你保留了之前写的动画代码，如果需要我再次完整贴出请告诉我) ...

    // ↓↓↓↓↓ 下面这些是必须要保留的辅助动画方法 ↓↓↓↓↓

    private void animateHappy() {
        startAction();
        eyeLeft.setImageResource(R.drawable.shape_eye_happy);
        eyeRight.setImageResource(R.drawable.shape_eye_happy);
        mouth.setVisibility(View.VISIBLE);
        snotBubble.setVisibility(View.INVISIBLE);
        ObjectAnimator jump = ObjectAnimator.ofFloat(mochiContainer, "translationY", 0f, -60f, 0f, -40f, 0f, -20f, 0f);
        jump.setDuration(1200);
        jump.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); }});
        jump.start();
    }

    private void animateSad() {
        startAction();
        eyeLeft.setImageResource(R.drawable.shape_eye_sad);
        eyeRight.setImageResource(R.drawable.shape_eye_sad);
        mouth.setVisibility(View.VISIBLE);
        snotBubble.setVisibility(View.INVISIBLE);
        ObjectAnimator shake = ObjectAnimator.ofFloat(mochiContainer, "translationX", 0f, 15f, -15f, 15f, -15f, 10f, -10f, 5f, -5f, 0f);
        shake.setDuration(1500);
        shake.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); }});
        shake.start();
    }

    private void animateConfused() {
        startAction();
        eyeLeft.setImageResource(R.drawable.shape_eye_normal);
        eyeRight.setImageResource(R.drawable.shape_eye_normal);
        mouth.setVisibility(View.VISIBLE);
        snotBubble.setVisibility(View.INVISIBLE);
        ObjectAnimator tilt = ObjectAnimator.ofFloat(mochiFace, "rotation", 0f, 25f, 25f, 25f, 25f, 0f);
        tilt.setDuration(2500);
        tilt.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); }});
        tilt.start();
    }

    // 4. 呆萌疑惑 (问号 + 吸鼻涕)
    private void animateLost() {
        startAction();

        // A. 表情设置：
        // 用"圆点眼"显得更呆 (或者你可以换回 sleeping 线条眼)
        eyeLeft.setImageResource(R.drawable.shape_eye_normal);
        eyeRight.setImageResource(R.drawable.shape_eye_normal);

        // 稍微把眼睛变小一点，更有"豆豆眼"的呆滞感
        updateViewSize(eyeLeft, 10, 10);
        updateViewSize(eyeRight, 10, 10);

        mouth.setVisibility(View.INVISIBLE); // 嘴巴藏起来
        snotBubble.setVisibility(View.VISIBLE); // ★ 显示鼻涕泡
        emoteQuestion.setVisibility(View.VISIBLE); // ★ 显示小问号

        // B. 动作：身体像呼吸一样微微起伏 (模拟吸鼻涕的节奏)
        ObjectAnimator sniffBody = ObjectAnimator.ofFloat(mochiContainer, "translationY", 0f, 5f, 0f, 5f, 0f);
        sniffBody.setDuration(4000); // 持续4秒

        // C. 鼻涕泡动画：忽大忽小 (模拟呼吸/吸鼻涕)
        ObjectAnimator sniffSnotX = ObjectAnimator.ofFloat(snotBubble, "scaleX", 0.3f, 1.2f, 0.3f, 1.2f, 0.3f);
        ObjectAnimator sniffSnotY = ObjectAnimator.ofFloat(snotBubble, "scaleY", 0.3f, 1.2f, 0.3f, 1.2f, 0.3f);
        sniffSnotX.setDuration(4000);
        sniffSnotY.setDuration(4000);

        // D. 问号动画：轻轻飘动/晃动
        ObjectAnimator questionAnim = ObjectAnimator.ofFloat(emoteQuestion, "rotation", 20f, 35f, 20f, 35f, 20f);
        questionAnim.setDuration(4000);

        // 组合播放
        sniffBody.start();
        sniffSnotX.start();
        sniffSnotY.start();
        questionAnim.start();

        // E. 结束监听
        sniffBody.addListener(new AnimatorListenerAdapter() {
            @Override
            public void onAnimationEnd(Animator animation) {
                // 恢复原样
                updateViewSize(eyeLeft, 14, 18); // 恢复眼睛大小
                updateViewSize(eyeRight, 14, 18);
                snotBubble.setVisibility(View.INVISIBLE);
                emoteQuestion.setVisibility(View.INVISIBLE); // ★ 隐藏问号
                endAction();
            }
        });
    }

    private void startAction() {
        isPerformingAction = true;
        if (idleAnimation != null) idleAnimation.cancel();
        mochiContainer.setTranslationY(0); mochiContainer.setTranslationX(0); mochiContainer.setRotation(0); mochiFace.setRotation(0);
    }

    private void endAction() {
        isPerformingAction = false;
        if (isSleeping) setSleepingState(true); else { startBreathAnimation(); updateVisualsByMood(); }
    }

    private void startBreathAnimation() {
        if (idleAnimation != null) idleAnimation.cancel();
        idleAnimation = ObjectAnimator.ofFloat(mochiBody, "scaleY", 1f, 1.05f, 1f);
        idleAnimation.setDuration(3000);
        idleAnimation.setRepeatCount(ValueAnimator.INFINITE);
        idleAnimation.start();
    }

    private void startFloatAnimation() {
        if (idleAnimation != null) idleAnimation.cancel();
        idleAnimation = ObjectAnimator.ofFloat(mochiContainer, "translationY", 0f, -10f, 0f);
        idleAnimation.setDuration(3000);
        idleAnimation.setRepeatCount(ValueAnimator.INFINITE);
        idleAnimation.start();
    }

    private void startSnotAnimation() {
        ObjectAnimator snotAnim = ObjectAnimator.ofFloat(snotBubble, "scaleX", 0.5f, 1.2f, 0.5f);
        snotAnim.setRepeatCount(ValueAnimator.INFINITE);
        snotAnim.setDuration(2000);
        snotAnim.start();
    }

    private void updateViewSize(View view, int widthDp, int heightDp) {
        float density = getResources().getDisplayMetrics().density;
        ViewGroup.LayoutParams params = view.getLayoutParams();
        params.width = Math.round(widthDp * density);
        params.height = Math.round(heightDp * density);
        view.setLayoutParams(params);
    }

    private void setSleepingState(boolean sleep) {
        isSleeping = sleep;
        if (idleAnimation != null) idleAnimation.cancel();
        if (isSleeping) {
            snotBubble.setVisibility(View.VISIBLE);
            eyeLeft.setImageResource(R.drawable.shape_eye_sleeping); eyeRight.setImageResource(R.drawable.shape_eye_sleeping);
            updateViewSize(eyeLeft, 28, 8); updateViewSize(eyeRight, 28, 8);
            mouth.setVisibility(View.INVISIBLE);
            startFloatAnimation(); startSnotAnimation();
        } else {
            snotBubble.setVisibility(View.INVISIBLE); mouth.setVisibility(View.VISIBLE);
            updateViewSize(eyeLeft, 14, 18); updateViewSize(eyeRight, 14, 18);
            startBreathAnimation(); updateVisualsByMood();
        }
    }

    private void updateVisualsByMood() {
        if (isSleeping || isPerformingAction) return;
        if (moodValue >= 50) { eyeLeft.setImageResource(R.drawable.shape_eye_happy); eyeRight.setImageResource(R.drawable.shape_eye_happy); }
        else if (moodValue <= -50) { eyeLeft.setImageResource(R.drawable.shape_eye_sad); eyeRight.setImageResource(R.drawable.shape_eye_sad); }
        else { eyeLeft.setImageResource(R.drawable.shape_eye_normal); eyeRight.setImageResource(R.drawable.shape_eye_normal); }
    }

    private Runnable gameLoopRunnable = new Runnable() {
        @Override public void run() {
            long now = System.currentTimeMillis();
            if (now - lastInteractionTime > 10000 && !isSleeping && !isPerformingAction) setSleepingState(true);
            if (Math.abs(moodValue) >= 1) {
                moodValue *= 0.98f;
                if (!isSleeping && !isPerformingAction) updateVisualsByMood();
            } else moodValue = 0;
            gameLoopHandler.postDelayed(this, 100);
        }
    };

    private Runnable blinkRunnable = new Runnable() {
        @Override public void run() {
            if (!isSleeping && !isPerformingAction) {
                eyeLeft.setImageResource(R.drawable.shape_eye_closed); eyeRight.setImageResource(R.drawable.shape_eye_closed);
                new Handler().postDelayed(() -> { if(!isSleeping && !isPerformingAction) updateVisualsByMood(); }, 150);
            }
            blinkHandler.postDelayed(this, 3000 + (long)(Math.random() * 4000));
        }
    };

    @Override
    protected void onDestroy() {
        super.onDestroy();
        gameLoopHandler.removeCallbacks(gameLoopRunnable);
        blinkHandler.removeCallbacks(blinkRunnable);
    }



    // === 获取课程详细信息 (标题 & 老师) ===
    private void fetchCourseInfo() {
        // 1. 指向 courses 节点下的当前课程ID
        DatabaseReference courseRef = FirebaseDatabase.getInstance()
                .getReference("courses")
                .child(currentCourseId);

        // 2. 读取一次数据 (SingleValue 即可，因为标题上课时一般不改)
        courseRef.addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot snapshot) {
                if (snapshot.exists()) {
                    // 获取标题
                    String title = snapshot.child("title").getValue(String.class);

                    // 获取老师名字
                    // (注意：如果你的数据库里存的是 teacher_name 就写 teacher_name)
                    // (如果是 teacher_id，就暂时显示 id，或者你去 users 节点再查一次)
                    String teacher = snapshot.child("teacher_name").getValue(String.class);

                    // 如果数据库里只有 teacher_id，没有 teacher_name，我们可以做个防空处理
                    if (teacher == null) {
                        teacher = snapshot.child("teacher_id").getValue(String.class);
                    }
                    if (title == null) title = "未命名课程";
                    if (teacher == null) teacher = "未知讲师";

                    // 3. 更新 UI
                    // 格式例如： "王瑛琦小讲堂 (讲师: teacher_01)"
                    if (tvClassTitle != null) {
                        tvClassTitle.setText(title + "\n讲师: " + teacher);
                    }
                }
            }

            @Override
            public void onCancelled(DatabaseError error) {
                // 读取失败
            }
        });
    }
}