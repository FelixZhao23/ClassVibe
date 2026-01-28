package jp.ac.jec.cm0105.classtest;

import android.animation.Animator;
import android.animation.AnimatorListenerAdapter;
import android.animation.ObjectAnimator;
import android.animation.ValueAnimator;
import android.content.Intent;
import android.content.SharedPreferences; // 必须导入这个
import android.os.Bundle;
import android.os.Handler;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ServerValue; // 必须导入这个
import com.google.firebase.database.ValueEventListener;

import java.util.UUID;

public class ClassroomActivity extends AppCompatActivity {

    // === UI 控件 ===
    private FrameLayout mochiContainer;
    private View mochiBody, mochiFace, snotBubble;
    private ImageView eyeLeft, eyeRight, mouth;
    private TextView tvClassTitle;
    private TextView emoteQuestion;

    // === 核心变量 ===
    private DatabaseReference courseRef;
    private DatabaseReference myMemberRef; // ★ 新增：指向我自己的数据节点
    private String currentCourseId;
    private String myUserId; // ★ 现在这个ID会保存在本地
    private String myUserName;

    // === 状态 & 动画变量 ===
    private float moodValue = 0;
    private boolean isSleeping = false;
    private boolean isPerformingAction = false;
    private long lastInteractionTime = System.currentTimeMillis();
    private ObjectAnimator idleAnimation;
    private Handler gameLoopHandler = new Handler();
    private Handler blinkHandler = new Handler();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_classroom);

        // 1. 获取 Intent 数据
        currentCourseId = getIntent().getStringExtra("COURSE_ID");
        myUserName = getIntent().getStringExtra("USER_NAME");

        if (currentCourseId == null) {
            Toast.makeText(this, "课程信息错误", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // 2. ★★★ 核心修改：获取并保存固定的 UserID ★★★
        // 这样下次打开 App，积分还在！
        SharedPreferences prefs = getSharedPreferences("AppPrefs", MODE_PRIVATE);
        // 尝试从手机硬盘里读 ID
        myUserId = prefs.getString("SAVED_USER_ID", null);

        // 如果读不到（说明是第一次安装），就生成一个并存起来
        if (myUserId == null) {
            myUserId = UUID.randomUUID().toString();
            prefs.edit().putString("SAVED_USER_ID", myUserId).apply();
        }

        // 3. Firebase 设置
        FirebaseDatabase database = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app");
        courseRef = database.getReference("courses").child(currentCourseId);

        // 指向：courses/{courseId}/members/{userId}
        myMemberRef = courseRef.child("members").child(myUserId);

        // 4. 绑定 UI
        mochiContainer = findViewById(R.id.mochi_container);
        mochiBody = findViewById(R.id.mochi_body);
        mochiFace = findViewById(R.id.mochi_face);
        snotBubble = findViewById(R.id.snot_bubble);
        eyeLeft = findViewById(R.id.eye_left);
        eyeRight = findViewById(R.id.eye_right);
        mouth = findViewById(R.id.mouth);
        tvClassTitle = findViewById(R.id.tv_class_title);
        emoteQuestion = findViewById(R.id.emote_question);

        if (tvClassTitle != null && myUserName != null) {
            tvClassTitle.setText("欢迎, " + myUserName);
        }

        // 5. 初始化功能
        setupOnlinePresence();
        setupButtons();
        setupBottomNavigation();
        fetchCourseInfo();

        // 6. 启动动画
        gameLoopHandler.post(gameLoopRunnable);
        blinkHandler.post(blinkRunnable);
        startBreathAnimation();
    }

    // === 上线打卡 ===
    private void setupOnlinePresence() {
        myMemberRef.child("online").setValue(true);
        myMemberRef.child("name").setValue(myUserName); // 把名字也存进去
        myMemberRef.child("online").onDisconnect().removeValue();
    }

    // === ★★★ 核心修改：按钮点击加积分 ★★★ ===
    private void setupButtons() {
        // 定义一个通用的加分方法
        View.OnClickListener scoreIncrement = v -> {
            // 在 Firebase 里，把 points 字段加 1 (原子操作，不会冲突)
            // 路径：courses/{courseId}/members/{userId}/points
            myMemberRef.child("points").setValue(ServerValue.increment(1));
        };

        // 1. Happy
        findViewById(R.id.btn_easy).setOnClickListener(v -> {
            handleFeedback(10);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateHappy();
            courseRef.child("reactions").child("happy").setValue(ServerValue.increment(1));
            scoreIncrement.onClick(v); // ★ 调用加分
        });

        // 2. Hard
        findViewById(R.id.btn_hard).setOnClickListener(v -> {
            handleFeedback(-15);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateSad();
            courseRef.child("reactions").child("confused").setValue(ServerValue.increment(1));
            scoreIncrement.onClick(v); // ★ 调用加分
        });

        // 3. Focus (问号右上)
        findViewById(R.id.btn_fast).setOnClickListener(v -> {
            handleFeedback(0);

            emoteQuestion.setVisibility(View.VISIBLE);
            FrameLayout.LayoutParams params = (FrameLayout.LayoutParams) emoteQuestion.getLayoutParams();
            params.gravity = Gravity.TOP | Gravity.END;
            params.setMargins(0, -dpToPx(20), -dpToPx(10), 0);
            emoteQuestion.setLayoutParams(params);
            emoteQuestion.setRotation(20);

            animateConfused();
            courseRef.child("reactions").child("question").setValue(ServerValue.increment(1));
            scoreIncrement.onClick(v); // ★ 调用加分
        });

        // 4. Slow (问号左上)
        findViewById(R.id.btn_slow).setOnClickListener(v -> {
            handleFeedback(-5);

            emoteQuestion.setVisibility(View.VISIBLE);
            FrameLayout.LayoutParams params = (FrameLayout.LayoutParams) emoteQuestion.getLayoutParams();
            params.gravity = Gravity.TOP | Gravity.START;
            params.setMargins(-dpToPx(10), -dpToPx(20), 0, 0);
            emoteQuestion.setLayoutParams(params);
            emoteQuestion.setRotation(-20);

            animateLost();
            courseRef.child("reactions").child("lost").setValue(ServerValue.increment(1));
            scoreIncrement.onClick(v); // ★ 调用加分
        });
    }

    // === 底部导航栏 ===
    private void setupBottomNavigation() {
        BottomNavigationView bottomNav = findViewById(R.id.bottom_navigation);
        bottomNav.setSelectedItemId(R.id.nav_classroom);
        bottomNav.setOnItemSelectedListener(item -> {
            if (item.getItemId() == R.id.nav_profile) {
                Intent intent = new Intent(ClassroomActivity.this, ProfileActivity.class);
                // ★★★ 必须把 ID 传给 Profile 页面，它才知道读谁的分数 ★★★
                intent.putExtra("COURSE_ID", currentCourseId);
                intent.putExtra("USER_ID", myUserId);
                intent.putExtra("USER_NAME", myUserName);

                startActivity(intent);
                overridePendingTransition(0, 0);
                return true;
            }
            return true;
        });
    }

    private int dpToPx(int dp) {
        float density = getResources().getDisplayMetrics().density;
        return Math.round((float) dp * density);
    }

    // === 下面是原本的动画和获取信息代码 (保持不变) ===

    private void fetchCourseInfo() {
        courseRef.addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot snapshot) {
                if (snapshot.exists()) {
                    String title = snapshot.child("title").getValue(String.class);
                    String teacher = snapshot.child("teacher_name").getValue(String.class);
                    if (teacher == null) teacher = snapshot.child("teacher_id").getValue(String.class);
                    if (title == null) title = "未命名课程";
                    if (teacher == null) teacher = "未知讲师";
                    if (tvClassTitle != null) tvClassTitle.setText(title + "\n讲师: " + teacher);
                }
            }
            @Override
            public void onCancelled(DatabaseError error) {}
        });
    }

    private void handleFeedback(int impact) {
        lastInteractionTime = System.currentTimeMillis();
        if (isSleeping) setSleepingState(false);
        moodValue += impact;
        moodValue = Math.max(-100, Math.min(100, moodValue));
    }

    private void animateHappy() {
        startAction();
        eyeLeft.setImageResource(R.drawable.shape_eye_happy);
        eyeRight.setImageResource(R.drawable.shape_eye_happy);
        mouth.setVisibility(View.VISIBLE);
        snotBubble.setVisibility(View.INVISIBLE);
        ObjectAnimator jump = ObjectAnimator.ofFloat(mochiContainer, "translationY", 0f, -60f, 0f, -40f, 0f, -20f, 0f);
        jump.setDuration(1200);
        jump.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); } });
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
        shake.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); } });
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
        tilt.addListener(new AnimatorListenerAdapter() { @Override public void onAnimationEnd(Animator animation) { endAction(); } });
        tilt.start();
    }

    private void animateLost() {
        startAction();
        eyeLeft.setImageResource(R.drawable.shape_eye_normal);
        eyeRight.setImageResource(R.drawable.shape_eye_normal);
        updateViewSize(eyeLeft, 10, 10);
        updateViewSize(eyeRight, 10, 10);
        mouth.setVisibility(View.INVISIBLE);
        snotBubble.setVisibility(View.VISIBLE);
        ObjectAnimator sniffBody = ObjectAnimator.ofFloat(mochiContainer, "translationY", 0f, 5f, 0f, 5f, 0f);
        sniffBody.setDuration(4000);
        ObjectAnimator sniffSnotX = ObjectAnimator.ofFloat(snotBubble, "scaleX", 0.3f, 1.2f, 0.3f, 1.2f, 0.3f);
        ObjectAnimator sniffSnotY = ObjectAnimator.ofFloat(snotBubble, "scaleY", 0.3f, 1.2f, 0.3f, 1.2f, 0.3f);
        sniffSnotX.setDuration(4000);
        sniffSnotY.setDuration(4000);
        ObjectAnimator questionAnim = ObjectAnimator.ofFloat(emoteQuestion, "rotation", 20f, 35f, 20f, 35f, 20f);
        questionAnim.setDuration(4000);
        sniffBody.start();
        sniffSnotX.start();
        sniffSnotY.start();
        questionAnim.start();
        sniffBody.addListener(new AnimatorListenerAdapter() {
            @Override public void onAnimationEnd(Animator animation) {
                updateViewSize(eyeLeft, 14, 18);
                updateViewSize(eyeRight, 14, 18);
                snotBubble.setVisibility(View.INVISIBLE);
                emoteQuestion.setVisibility(View.INVISIBLE);
                endAction();
            }
        });
    }

    private void startAction() {
        isPerformingAction = true;
        if (idleAnimation != null) idleAnimation.cancel();
        mochiContainer.setTranslationY(0);
        mochiContainer.setTranslationX(0);
        mochiContainer.setRotation(0);
        mochiFace.setRotation(0);
    }

    private void endAction() {
        isPerformingAction = false;
        if (isSleeping) setSleepingState(true);
        else {
            startBreathAnimation();
            updateVisualsByMood();
        }
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
            eyeLeft.setImageResource(R.drawable.shape_eye_sleeping);
            eyeRight.setImageResource(R.drawable.shape_eye_sleeping);
            updateViewSize(eyeLeft, 28, 8);
            updateViewSize(eyeRight, 28, 8);
            mouth.setVisibility(View.INVISIBLE);
            startFloatAnimation();
            startSnotAnimation();
        } else {
            snotBubble.setVisibility(View.INVISIBLE);
            mouth.setVisibility(View.VISIBLE);
            updateViewSize(eyeLeft, 14, 18);
            updateViewSize(eyeRight, 14, 18);
            startBreathAnimation();
            updateVisualsByMood();
        }
    }

    private void updateVisualsByMood() {
        if (isSleeping || isPerformingAction) return;
        if (moodValue >= 50) {
            eyeLeft.setImageResource(R.drawable.shape_eye_happy);
            eyeRight.setImageResource(R.drawable.shape_eye_happy);
        } else if (moodValue <= -50) {
            eyeLeft.setImageResource(R.drawable.shape_eye_sad);
            eyeRight.setImageResource(R.drawable.shape_eye_sad);
        } else {
            eyeLeft.setImageResource(R.drawable.shape_eye_normal);
            eyeRight.setImageResource(R.drawable.shape_eye_normal);
        }
    }

    private Runnable gameLoopRunnable = new Runnable() {
        @Override public void run() {
            long now = System.currentTimeMillis();
            if (now - lastInteractionTime > 10000 && !isSleeping && !isPerformingAction)
                setSleepingState(true);
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
                eyeLeft.setImageResource(R.drawable.shape_eye_closed);
                eyeRight.setImageResource(R.drawable.shape_eye_closed);
                new Handler().postDelayed(() -> {
                    if (!isSleeping && !isPerformingAction) updateVisualsByMood();
                }, 150);
            }
            blinkHandler.postDelayed(this, 3000 + (long) (Math.random() * 4000));
        }
    };

    @Override
    protected void onDestroy() {
        super.onDestroy();
        gameLoopHandler.removeCallbacks(gameLoopRunnable);
        blinkHandler.removeCallbacks(blinkRunnable);
    }
}