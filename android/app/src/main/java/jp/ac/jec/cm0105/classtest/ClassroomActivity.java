package jp.ac.jec.cm0105.classtest;

import android.animation.Animator;
import android.animation.AnimatorListenerAdapter;
import android.animation.ObjectAnimator;
import android.animation.ValueAnimator;
import android.content.Intent;
import android.content.SharedPreferences; // 必须导入这个
import android.content.res.ColorStateList;
import android.graphics.drawable.GradientDrawable;
import android.graphics.drawable.LayerDrawable;
import android.graphics.ImageDecoder;
import android.graphics.drawable.AnimatedImageDrawable;
import android.os.Bundle;
import android.os.Handler;
import android.graphics.Color;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import android.os.VibrationEffect;
import android.os.Vibrator;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ServerValue; // 必须导入这个
import com.google.firebase.database.ValueEventListener;

import java.util.HashMap;
import java.util.Map;
import java.util.UUID;

public class ClassroomActivity extends AppCompatActivity {

    // === UI 控件 ===
    private FrameLayout mochiContainer;
    private View mochiBody, mochiFace, snotBubble;
    private ImageView eyeLeft, eyeRight, mouth;
    private ImageView dizzyGif;
    private AnimatedImageDrawable dizzyDrawable;
    private TextView tvClassTitle;
    private TextView emoteQuestion;
    private TextView tvTeamLabel;
    private View btnLeaveRoom;
    private View btnEasy, btnHard, btnFast, btnSlow, btnSlacking, btnBoring;
    private View classroomRoot;
    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_HAPTICS = "haptics_enabled";

    // === 核心变量 ===
    private DatabaseReference courseRef;
    private DatabaseReference myMemberRef; // ★ 新增：指向我自己的数据节点
    private String currentCourseId;
    private String myUserId; // ★ 现在这个ID会保存在本地
    private String myUserName;
    private String myTeam = "red";
    private long lastMetricAt = 0L;
    private String lastMetricKey = "";
    private int sameMetricChain = 0;
    private int teamCountRed = 1;
    private int teamCountBlue = 1;
    private long joinTimeMs = System.currentTimeMillis();

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
        myTeam = (Math.abs(myUserId.hashCode()) % 2 == 0) ? "red" : "blue";
        joinTimeMs = System.currentTimeMillis();

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
        dizzyGif = findViewById(R.id.dizzy_gif);
        tvClassTitle = findViewById(R.id.tv_class_title);
        emoteQuestion = findViewById(R.id.emote_question);
        tvTeamLabel = findViewById(R.id.tv_team_label);
        btnLeaveRoom = findViewById(R.id.btn_leave_room);
        btnEasy = findViewById(R.id.btn_easy);
        btnHard = findViewById(R.id.btn_hard);
        btnFast = findViewById(R.id.btn_fast);
        btnSlow = findViewById(R.id.btn_slow);
        btnSlacking = findViewById(R.id.btn_slacking);
        btnBoring = findViewById(R.id.btn_boring);
        classroomRoot = findViewById(R.id.classroom_root);

        if (tvClassTitle != null && myUserName != null) {
            tvClassTitle.setText("教室");
        }
        if (btnLeaveRoom != null) {
            btnLeaveRoom.setOnClickListener(v -> showLeaveConfirm());
        }

        chooseBalancedTeamAndStart();
    }

    @Override
    public void onBackPressed() {
        // Prevent swipe-back from exiting; use explicit leave button instead.
        showLeaveConfirm();
    }

    // === 上线打卡 ===
    private void setupOnlinePresence() {
        myMemberRef.child("online").setValue(true);
        myMemberRef.child("name").setValue(myUserName); // 把名字也存进去
        myMemberRef.child("online").onDisconnect().removeValue();

        DatabaseReference activeRef = courseRef.child("active_students").child(myUserId);
        Map<String, Object> studentInfo = new HashMap<>();
        studentInfo.put("name", myUserName == null ? "student" : myUserName);
        studentInfo.put("team", myTeam);
        studentInfo.put("joined_at", ServerValue.TIMESTAMP);
        activeRef.updateChildren(studentInfo);
        activeRef.onDisconnect().removeValue();
    }

    // === ★★★ 核心修改：按钮点击加积分 ★★★ ===
    private void setupButtons() {
        attachPressEffects(btnEasy);
        attachPressEffects(btnHard);
        attachPressEffects(btnFast);
        attachPressEffects(btnSlow);
        attachPressEffects(btnSlacking);
        attachPressEffects(btnBoring);

        // 定义一个通用的加分方法
        View.OnClickListener scoreIncrement = v -> {
            // 在 Firebase 里，把 points 字段加 1 (原子操作，不会冲突)
            // 路径：courses/{courseId}/members/{userId}/points
            myMemberRef.child("points").setValue(ServerValue.increment(1));
        };

        // 1. Happy
        btnEasy.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnEasy);
            handleFeedback(10);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateHappy();
            submitReaction("happy", "happy", () -> scoreIncrement.onClick(v));
        });

        // 2. Hard
        btnHard.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnHard);
            handleFeedback(-15);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateSad();
            submitReaction("confused", "confused", () -> scoreIncrement.onClick(v));
        });

        // 3. ぜんぜんわからない
        btnFast.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnFast);
            handleFeedback(0);

            emoteQuestion.setVisibility(View.VISIBLE);
            FrameLayout.LayoutParams params = (FrameLayout.LayoutParams) emoteQuestion.getLayoutParams();
            params.gravity = Gravity.TOP | Gravity.END;
            params.setMargins(0, -dpToPx(20), -dpToPx(10), 0);
            emoteQuestion.setLayoutParams(params);
            emoteQuestion.setRotation(20);

            animateLost(); // dizzy gif for ぜんぜんわからない
            submitReaction("question", "question", () -> scoreIncrement.onClick(v));
        });

        // 4. ちょっとわからない
        btnSlow.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnSlow);
            handleFeedback(-5);

            emoteQuestion.setVisibility(View.VISIBLE);
            FrameLayout.LayoutParams params = (FrameLayout.LayoutParams) emoteQuestion.getLayoutParams();
            params.gravity = Gravity.TOP | Gravity.START;
            params.setMargins(-dpToPx(10), -dpToPx(20), 0, 0);
            emoteQuestion.setLayoutParams(params);
            emoteQuestion.setRotation(-20);

            animateConfused();
            submitReaction("amazing", "amazing", () -> scoreIncrement.onClick(v));
        });

        // 5. サボり中
        btnSlacking.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnSlacking);
            handleFeedback(-5);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateSad();
            submitReaction("sleepy", "sleepy", () -> scoreIncrement.onClick(v));
        });

        // 6. 面倒
        btnBoring.setOnClickListener(v -> {
            triggerHaptic();
            pulseHighlight(btnBoring);
            handleFeedback(-5);
            emoteQuestion.setVisibility(View.INVISIBLE);
            animateSad();
            submitReaction("bored", "bored", () -> scoreIncrement.onClick(v));
        });
    }

    private void triggerHaptic() {
        SharedPreferences prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        boolean enabled = prefs.getBoolean(KEY_HAPTICS, true);
        if (!enabled) return;

        Vibrator vibrator = (Vibrator) getSystemService(VIBRATOR_SERVICE);
        if (vibrator == null) return;
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.O) {
            vibrator.vibrate(VibrationEffect.createOneShot(20, VibrationEffect.DEFAULT_AMPLITUDE));
        } else {
            vibrator.vibrate(20);
        }
    }

    private void attachPressEffects(View view) {
        if (view == null) return;
        view.setOnTouchListener((v, event) -> {
            switch (event.getAction()) {
                case android.view.MotionEvent.ACTION_DOWN:
                    v.animate().scaleX(0.97f).scaleY(0.97f).setDuration(70).start();
                    break;
                case android.view.MotionEvent.ACTION_UP:
                case android.view.MotionEvent.ACTION_CANCEL:
                    v.animate().scaleX(1f).scaleY(1f).setDuration(120).start();
                    break;
                default:
                    break;
            }
            return false;
        });
    }

    private void pulseHighlight(View view) {
        if (view == null) return;
        if (view instanceof com.google.android.material.button.MaterialButton) {
            com.google.android.material.button.MaterialButton button =
                    (com.google.android.material.button.MaterialButton) view;
            ColorStateList original = button.getBackgroundTintList();
            int base = original != null ? original.getDefaultColor() : Color.WHITE;
            int highlight = blendColor(base, Color.WHITE, 0.18f);
            button.setBackgroundTintList(ColorStateList.valueOf(highlight));
            button.postDelayed(() -> {
                if (original != null) {
                    button.setBackgroundTintList(original);
                }
            }, 160);
        } else {
            view.setAlpha(0.88f);
            view.animate().alpha(1f).setDuration(160).start();
        }
    }

    private int blendColor(int color, int overlay, float ratio) {
        int r = (int) ((1 - ratio) * Color.red(color) + ratio * Color.red(overlay));
        int g = (int) ((1 - ratio) * Color.green(color) + ratio * Color.green(overlay));
        int b = (int) ((1 - ratio) * Color.blue(color) + ratio * Color.blue(overlay));
        return Color.rgb(r, g, b);
    }

    // RealReaction 开启时，写入 courses/{id}/real_reaction；否则写入普通 reactions
    private void submitReaction(String normalKey, String rrKey, Runnable onSuccess) {
        double weight = computeMetricWeight(normalKey);
        if (weight <= 0) {
            Toast.makeText(this, "連打しすぎです。少し待ってください。", Toast.LENGTH_SHORT).show();
            return;
        }

        DatabaseReference rrRef = courseRef.child("real_reaction");
        rrRef.child("active").addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot activeSnap) {
                boolean rrActive = Boolean.TRUE.equals(activeSnap.getValue(Boolean.class));

                if (!rrActive) {
                    courseRef.child("reactions").child(normalKey)
                            .setValue(ServerValue.increment(1), (error, ref) -> {
                                if (error == null) {
                                    updateStudentMetrics(normalKey, weight);
                                    if (onSuccess != null) onSuccess.run();
                                }
                            });
                    return;
                }

                rrRef.child("voted_students").child(myUserId)
                        .addListenerForSingleValueEvent(new ValueEventListener() {
                            @Override
                            public void onDataChange(@NonNull DataSnapshot voteSnap) {
                                if (voteSnap.exists()) {
                                    Toast.makeText(ClassroomActivity.this, "リアルリアクションは1人1回までです", Toast.LENGTH_SHORT).show();
                                    return;
                                }

                                Map<String, Object> updates = new HashMap<>();
                                updates.put("reactions/" + rrKey, ServerValue.increment(1));

                                Map<String, Object> voteInfo = new HashMap<>();
                                voteInfo.put("name", myUserName == null ? "student" : myUserName);
                                voteInfo.put("at", ServerValue.TIMESTAMP);
                                updates.put("voted_students/" + myUserId, voteInfo);

                                rrRef.updateChildren(updates, (error, ref) -> {
                                    if (error == null) {
                                        updateStudentMetrics(rrKey, weight);
                                        if (onSuccess != null) onSuccess.run();
                                    }
                                });
                            }

                            @Override
                            public void onCancelled(@NonNull DatabaseError error) { }
                        });
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) { }
        });
    }

    private double computeMetricWeight(String key) {
        long now = System.currentTimeMillis();
        if (now - lastMetricAt < 2000) return 0;

        if (key.equals(lastMetricKey)) {
            sameMetricChain += 1;
        } else {
            sameMetricChain = 1;
            lastMetricKey = key;
        }
        lastMetricAt = now;

        if (sameMetricChain == 1) return 1.0;
        if (sameMetricChain == 2) return 0.6;
        return 0.3;
    }

    private void updateStudentMetrics(String metricKey, double weight) {
        DatabaseReference metricsRef = courseRef.child("student_metrics").child(myUserId);

        int understood = ("happy".equals(metricKey) || "amazing".equals(metricKey)) ? 1 : 0;
        int question = ("confused".equals(metricKey) || "question".equals(metricKey) || "amazing".equals(metricKey)) ? 1 : 0;
        int confused = ("confused".equals(metricKey) || "sleepy".equals(metricKey) || "bored".equals(metricKey)) ? 1 : 0;
        double teamContribution = computeTeamContribution(weight);

        Map<String, Object> updates = new HashMap<>();
        updates.put("display_name", myUserName == null ? "student" : myUserName);
        updates.put("team", myTeam);
        updates.put("effective_interactions", ServerValue.increment(weight));
        updates.put("understood_count", ServerValue.increment(understood));
        updates.put("question_count", ServerValue.increment(question));
        updates.put("confused_count", ServerValue.increment(confused));
        updates.put("team_contribution", ServerValue.increment(teamContribution));
        updates.put("last_reaction_at", ServerValue.TIMESTAMP);

        metricsRef.updateChildren(updates);
    }

    private double computeTeamContribution(double base) {
        int red = Math.max(1, teamCountRed);
        int blue = Math.max(1, teamCountBlue);
        int total = Math.max(1, red + blue);
        int teamCount = "red".equals(myTeam) ? red : blue;

        double ratio = Math.sqrt((double) total / (double) teamCount);
        double sizeFactor = Math.min(5.0, Math.max(1.0, ratio));
        double elapsed = (System.currentTimeMillis() - joinTimeMs) / 1000.0;
        double ramp = Math.min(1.0, Math.max(0.4, elapsed / 30.0));
        return base * sizeFactor * ramp;
    }

    private void observeTeamCounts() {
        courseRef.child("active_students").addValueEventListener(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                int red = 0;
                int blue = 0;
                for (DataSnapshot child : snapshot.getChildren()) {
                    String team = child.child("team").getValue(String.class);
                    if ("red".equals(team)) red += 1;
                    if ("blue".equals(team)) blue += 1;
                }
                teamCountRed = Math.max(1, red);
                teamCountBlue = Math.max(1, blue);
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) { }
        });
    }

    private void chooseBalancedTeamAndStart() {
        courseRef.child("active_students").addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                int red = 0;
                int blue = 0;
                for (DataSnapshot child : snapshot.getChildren()) {
                    String team = child.child("team").getValue(String.class);
                    if ("red".equals(team)) red += 1;
                    if ("blue".equals(team)) blue += 1;
                }
                if (red > blue) {
                    myTeam = "blue";
                } else if (blue > red) {
                    myTeam = "red";
                } else {
                    myTeam = (Math.abs(myUserId.hashCode()) % 2 == 0) ? "red" : "blue";
                }

                updateTeamLabel();
                applyTeamBackground();

                // 初始化功能
                setupOnlinePresence();
                setupButtons();
                setupBottomNavigation();
                fetchCourseInfo();
                observeClassActiveState();
                observeTeamCounts();

                // 启动动画
                gameLoopHandler.post(gameLoopRunnable);
                blinkHandler.post(blinkRunnable);
                startBreathAnimation();
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) {
                // fallback: hash-based
                myTeam = (Math.abs(myUserId.hashCode()) % 2 == 0) ? "red" : "blue";
                updateTeamLabel();
                applyTeamBackground();
                setupOnlinePresence();
                setupButtons();
                setupBottomNavigation();
                fetchCourseInfo();
                observeClassActiveState();
                observeTeamCounts();
                gameLoopHandler.post(gameLoopRunnable);
                blinkHandler.post(blinkRunnable);
                startBreathAnimation();
            }
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

    private void observeClassActiveState() {
        courseRef.child("is_active").addValueEventListener(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                Boolean active = snapshot.getValue(Boolean.class);
                if (Boolean.FALSE.equals(active)) {
                    Toast.makeText(ClassroomActivity.this, "授業が終了しました", Toast.LENGTH_SHORT).show();
                    finish();
                }
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) { }
        });
    }

    private void fetchCourseInfo() {
        courseRef.addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot snapshot) {
                if (snapshot.exists()) {
                    String title = snapshot.child("title").getValue(String.class);
                    if (title == null) title = "未命名课程";
                    if (tvClassTitle != null) {
                        tvClassTitle.setText(title);
                    }
                }
            }
            @Override
            public void onCancelled(DatabaseError error) {}
        });
    }

    private void updateTeamLabel() {
        if (tvTeamLabel == null) return;
        if ("red".equals(myTeam)) {
            tvTeamLabel.setText("🟥 RED TEAM");
            tvTeamLabel.setTextColor(Color.parseColor("#DC2626"));
        } else {
            tvTeamLabel.setText("🟦 BLUE TEAM");
            tvTeamLabel.setTextColor(Color.parseColor("#2563EB"));
        }
    }

    private void applyTeamBackground() {
        if (classroomRoot == null) return;
        int[] colors;
        if ("red".equals(myTeam)) {
            colors = new int[] {0xFFFFF1F2, 0xFFFECACA};
        } else {
            colors = new int[] {0xFFEFF6FF, 0xFFBFDBFE};
        }
        GradientDrawable gradient = new GradientDrawable(GradientDrawable.Orientation.TL_BR, colors);
        gradient.setCornerRadius(0f);
        GradientDrawable glow = makeSoftGlow();
        LayerDrawable layers = new LayerDrawable(new android.graphics.drawable.Drawable[]{gradient, glow});
        classroomRoot.setBackground(layers);
    }

    private GradientDrawable makeSoftGlow() {
        int start = 0x66FFFFFF;
        int end = 0x00FFFFFF;
        GradientDrawable glow = new GradientDrawable(GradientDrawable.Orientation.TL_BR, new int[]{start, end});
        glow.setGradientType(GradientDrawable.RADIAL_GRADIENT);
        glow.setGradientRadius(600f);
        glow.setGradientCenter(0.2f, 0.1f);
        return glow;
    }

    private void showLeaveConfirm() {
        new AlertDialog.Builder(this)
                .setTitle("教室を退出しますか？")
                .setMessage("退出すると参加状態が解除されます。")
                .setNegativeButton("キャンセル", null)
                .setPositiveButton("退出", (d, w) -> leaveCourse())
                .show();
    }

    private void leaveCourse() {
        if (courseRef != null && myUserId != null) {
            courseRef.child("active_students").child(myUserId).removeValue();
            myMemberRef.child("online").removeValue();
        }
        finish();
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
        showDizzyGif();
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
        hideDizzyGif();
        if (isSleeping) setSleepingState(true);
        else {
            startBreathAnimation();
            updateVisualsByMood();
        }
    }

    private void showDizzyGif() {
        if (dizzyGif == null) return;
        try {
            ImageDecoder.Source source = ImageDecoder.createSource(getResources(), R.drawable.dizzy);
            AnimatedImageDrawable drawable = (AnimatedImageDrawable) ImageDecoder.decodeDrawable(source);
            dizzyDrawable = drawable;
            dizzyGif.setImageDrawable(drawable);
            dizzyGif.setVisibility(View.VISIBLE);
            drawable.start();
        } catch (Exception e) {
            // fallback: hide if decode fails
            dizzyGif.setVisibility(View.GONE);
        }
    }

    private void hideDizzyGif() {
        if (dizzyGif == null) return;
        if (dizzyDrawable != null) {
            dizzyDrawable.stop();
        }
        dizzyGif.setVisibility(View.GONE);
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
