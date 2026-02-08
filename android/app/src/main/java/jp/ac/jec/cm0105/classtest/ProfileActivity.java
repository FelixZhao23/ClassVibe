package jp.ac.jec.cm0105.classtest;

import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import android.view.View;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ValueEventListener;

import java.util.ArrayList;
import java.util.List;

public class ProfileActivity extends AppCompatActivity {

    private TextView tvName, tvTitle, tvLevel, tvPoints, tvLogs;
    private TextView tvUnderstand, tvQuestion, tvCollab, tvEngagement, tvStability;
    private TextView tvTitleUpgradeName;
    private ImageView imgAvatar;
    private ProgressBar progressExp;
    private View titleUpgradeOverlay;
    private LinearLayout titleUpgradeCard;
    private String userId;
    private final Handler uiHandler = new Handler(Looper.getMainLooper());

    private DatabaseReference userRef;
    private ValueEventListener userListener;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        userId = getIntent().getStringExtra("USER_ID");
        String userName = getIntent().getStringExtra("USER_NAME");
        if (userId == null) {
            Toast.makeText(this, "ユーザー情報が見つかりません", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        bindViews();
        tvName.setText(userName == null ? "Student" : userName);

        BottomNavigationView bottomNav = findViewById(R.id.bottom_navigation);
        bottomNav.setSelectedItemId(R.id.nav_profile);
        bottomNav.setOnItemSelectedListener(item -> {
            if (item.getItemId() == R.id.nav_classroom) {
                finish();
                overridePendingTransition(0, 0);
                return true;
            }
            return true;
        });

        userRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                .getReference("users")
                .child(userId);

        userListener = new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot snapshot) {
                String role = snapshot.child("role").getValue(String.class);
                String displayName = snapshot.child("name").getValue(String.class);
                if (displayName != null && !displayName.isEmpty()) tvName.setText(displayName);
                String nextTitle = snapshot.child("growth").child("title_current").getValue(String.class);
                tvTitle.setText(nextTitle == null
                        ? "はじめの一歩"
                        : nextTitle);
                maybeShowTitleUpgrade(nextTitle == null ? "はじめの一歩" : nextTitle);

                if ("teacher".equals(role)) {
                    imgAvatar.setImageResource(android.R.drawable.ic_menu_myplaces);
                    imgAvatar.setColorFilter(0xFF4F46E5);
                } else {
                    imgAvatar.setImageResource(android.R.drawable.ic_menu_info_details);
                    imgAvatar.setColorFilter(0xFF2563EB);
                }

                int exp = toInt(snapshot.child("growth").child("exp_total").getValue());
                LevelInfo info = levelFromExp(exp);
                tvLevel.setText("Lv." + info.level);
                tvPoints.setText("EXP " + info.currentInLevel + " / " + info.needForNext);
                progressExp.setMax(info.needForNext);
                progressExp.setProgress(info.currentInLevel);

                DataSnapshot dims = snapshot.child("growth").child("dims");
                tvUnderstand.setText("理解: " + toInt(dims.child("understand").getValue()));
                tvQuestion.setText("質問: " + toInt(dims.child("question").getValue()));
                tvCollab.setText("協力: " + toInt(dims.child("collab").getValue()));
                tvEngagement.setText("参加: " + toInt(dims.child("engagement").getValue()));
                tvStability.setText("安定: " + toInt(dims.child("stability").getValue()));

                DataSnapshot logsSnap = snapshot.child("growth_logs");
                List<String> rows = new ArrayList<>();
                for (DataSnapshot child : logsSnap.getChildren()) {
                    String summary = child.child("summary").getValue(String.class);
                    String hint = child.child("next_hint").getValue(String.class);
                    int gain = toInt(child.child("exp_gain").getValue());
                    rows.add("・" + (summary == null ? "成長記録" : summary) + "  (+" + gain + " EXP)"
                            + (hint == null || hint.isEmpty() ? "" : "\n  次: " + hint));
                    if (rows.size() >= 10) break;
                }
                if (rows.isEmpty()) {
                    tvLogs.setText("まだ成長ログがありません。");
                } else {
                    tvLogs.setText(android.text.TextUtils.join("\n\n", rows));
                }
            }

            @Override
            public void onCancelled(DatabaseError error) { }
        };
        userRef.addValueEventListener(userListener);
    }

    private void bindViews() {
        imgAvatar = findViewById(R.id.img_role_avatar);
        tvName = findViewById(R.id.tv_profile_name);
        tvTitle = findViewById(R.id.tv_profile_title);
        tvLevel = findViewById(R.id.tv_level);
        tvPoints = findViewById(R.id.tv_points);
        progressExp = findViewById(R.id.progress_exp);
        tvUnderstand = findViewById(R.id.tv_dim_understand);
        tvQuestion = findViewById(R.id.tv_dim_question);
        tvCollab = findViewById(R.id.tv_dim_collab);
        tvEngagement = findViewById(R.id.tv_dim_engagement);
        tvStability = findViewById(R.id.tv_dim_stability);
        tvLogs = findViewById(R.id.tv_growth_logs);
        titleUpgradeOverlay = findViewById(R.id.title_upgrade_overlay);
        titleUpgradeCard = findViewById(R.id.title_upgrade_card);
        tvTitleUpgradeName = findViewById(R.id.tv_title_upgrade_name);
    }

    private void maybeShowTitleUpgrade(String nextTitle) {
        if (nextTitle == null || nextTitle.isEmpty() || userId == null) return;

        String prefKey = "last_title_" + userId;
        String oldTitle = getSharedPreferences("classvibe_growth", MODE_PRIVATE).getString(prefKey, null);
        getSharedPreferences("classvibe_growth", MODE_PRIVATE).edit().putString(prefKey, nextTitle).apply();

        if (oldTitle == null || oldTitle.equals(nextTitle)) return;

        tvTitleUpgradeName.setText(nextTitle);
        titleUpgradeOverlay.setVisibility(View.VISIBLE);
        titleUpgradeOverlay.setAlpha(0f);
        titleUpgradeCard.setScaleX(0.75f);
        titleUpgradeCard.setScaleY(0.75f);
        titleUpgradeCard.setAlpha(0f);

        titleUpgradeOverlay.animate().alpha(1f).setDuration(180).start();
        titleUpgradeCard.animate()
                .alpha(1f)
                .scaleX(1f)
                .scaleY(1f)
                .setDuration(320)
                .start();

        uiHandler.removeCallbacksAndMessages(null);
        uiHandler.postDelayed(() -> {
            titleUpgradeCard.animate()
                    .alpha(0f)
                    .scaleX(0.92f)
                    .scaleY(0.92f)
                    .setDuration(180)
                    .start();
            titleUpgradeOverlay.animate()
                    .alpha(0f)
                    .setDuration(220)
                    .withEndAction(() -> titleUpgradeOverlay.setVisibility(View.GONE))
                    .start();
        }, 2200);
    }

    private int toInt(Object value) {
        if (value instanceof Long) return ((Long) value).intValue();
        if (value instanceof Integer) return (Integer) value;
        if (value instanceof Double) return (int) Math.round((Double) value);
        return 0;
    }

    private LevelInfo levelFromExp(int exp) {
        int level = 1;
        int remaining = Math.max(0, exp);
        int need = 120;
        while (remaining >= need) {
            remaining -= need;
            level += 1;
            need = 120 + ((level - 1) * 20);
        }
        return new LevelInfo(level, remaining, need);
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        uiHandler.removeCallbacksAndMessages(null);
        if (userRef != null && userListener != null) {
            userRef.removeEventListener(userListener);
        }
    }

    private static class LevelInfo {
        final int level;
        final int currentInLevel;
        final int needForNext;

        LevelInfo(int level, int currentInLevel, int needForNext) {
            this.level = level;
            this.currentInLevel = currentInLevel;
            this.needForNext = needForNext;
        }
    }
}
