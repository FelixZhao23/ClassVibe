package jp.ac.jec.cm0105.classtest;

import android.os.Bundle;
import android.content.Intent;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Handler;
import android.os.Looper;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.GridLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import android.view.View;
import android.widget.ImageButton;
import android.view.LayoutInflater;
import android.animation.Animator;
import android.animation.AnimatorListenerAdapter;
import android.animation.ObjectAnimator;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.app.AlertDialog;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ValueEventListener;

import java.util.ArrayList;
import java.util.List;

public class ProfileActivity extends AppCompatActivity {

    private TextView tvName, tvLevel, tvPoints, tvLogs;
    private TextView tvUnderstand, tvQuestion, tvCollab, tvEngagement, tvStability, tvTotal;
    private TextView tvTitleUpgradeName;
    private ImageView imgAvatar;
    private ImageView imgAvatarEdit;
    private GridLayout gridBadgesBack;
    private View cardGrowth;
    private View growthFront;
    private View growthBack;
    private boolean showingFront = true;
    private ProgressBar progressExp;
    private View titleUpgradeOverlay;
    private LinearLayout titleUpgradeCard;
    private String userId;
    private ImageButton btnSettings;
    private final Handler uiHandler = new Handler(Looper.getMainLooper());
    private ActivityResultLauncher<String> avatarPicker;
    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_LAST_NAME = "last_student_name";
    private static final String KEY_AVATAR_URI = "profile_avatar_uri";

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
        if (userName == null || userName.trim().isEmpty()) {
            userName = getSharedPreferences(PREFS, MODE_PRIVATE).getString(KEY_LAST_NAME, "Student");
        }
        tvName.setText(userName == null ? "Student" : userName);

        BottomNavigationView bottomNav = findViewById(R.id.bottom_navigation);
        final boolean[] navReady = {false};
        bottomNav.setOnItemReselectedListener(item -> { });
        bottomNav.setOnItemSelectedListener(item -> {
            if (!navReady[0]) return true;
            if (item.getItemId() == R.id.nav_classroom) {
                finish();
                overridePendingTransition(0, 0);
                return true;
            }
            return true;
        });
        bottomNav.post(() -> {
            bottomNav.setSelectedItemId(R.id.nav_profile);
            navReady[0] = true;
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
                maybeShowTitleUpgrade(nextTitle == null ? "はじめの一歩" : nextTitle);

                applyDefaultAvatarIfNeeded(role);

                int exp = toInt(snapshot.child("growth").child("exp_total").getValue());
                LevelInfo info = levelFromExp(exp);
                tvLevel.setText("Lv." + info.level);
                tvPoints.setText("EXP " + info.currentInLevel + " / " + info.needForNext);
                progressExp.setMax(info.needForNext);
                progressExp.setProgress(info.currentInLevel);

                DataSnapshot dims = snapshot.child("growth").child("dims");
                tvUnderstand.setText(String.valueOf(toInt(dims.child("understand").getValue())));
                tvQuestion.setText(String.valueOf(toInt(dims.child("question").getValue())));
                tvCollab.setText(String.valueOf(toInt(dims.child("collab").getValue())));
                tvEngagement.setText(String.valueOf(toInt(dims.child("engagement").getValue())));
                tvStability.setText(String.valueOf(toInt(dims.child("stability").getValue())));
                if (tvTotal != null) {
                    tvTotal.setText(String.valueOf(exp));
                }
                populateBadgeGridForTest(
                        toInt(dims.child("understand").getValue()),
                        toInt(dims.child("question").getValue()),
                        toInt(dims.child("collab").getValue()),
                        toInt(dims.child("engagement").getValue()),
                        toInt(dims.child("stability").getValue())
                );

                DataSnapshot logsSnap = snapshot.child("growth_logs");
                List<String> rows = new ArrayList<>();
                for (DataSnapshot child : logsSnap.getChildren()) {
                    String summary = child.child("summary").getValue(String.class);
                    String hint = child.child("next_hint").getValue(String.class);
                    String message = child.child("message").getValue(String.class);
                    int gain = toInt(child.child("exp_gain").getValue());
                    StringBuilder row = new StringBuilder();
                    row.append("・").append(summary == null ? "成長記録" : summary).append("  (+").append(gain).append(" EXP)");
                    if (message != null && !message.isEmpty()) {
                        row.append("\n  💬 ").append(message);
                    }
                    if (hint != null && !hint.isEmpty()) {
                        row.append("\n  次: ").append(hint);
                    }
                    rows.add(row.toString());
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
        imgAvatarEdit = findViewById(R.id.img_avatar_edit);
        tvName = findViewById(R.id.tv_profile_name);
        tvLevel = findViewById(R.id.tv_level);
        tvPoints = findViewById(R.id.tv_points);
        progressExp = findViewById(R.id.progress_exp);
        tvUnderstand = findViewById(R.id.tv_dim_understand);
        tvQuestion = findViewById(R.id.tv_dim_question);
        tvCollab = findViewById(R.id.tv_dim_collab);
        tvEngagement = findViewById(R.id.tv_dim_engagement);
        tvStability = findViewById(R.id.tv_dim_stability);
        tvTotal = findViewById(R.id.tv_dim_total);
        tvLogs = findViewById(R.id.tv_growth_logs);
        titleUpgradeOverlay = findViewById(R.id.title_upgrade_overlay);
        titleUpgradeCard = findViewById(R.id.title_upgrade_card);
        tvTitleUpgradeName = findViewById(R.id.tv_title_upgrade_name);
        btnSettings = findViewById(R.id.btn_settings);
        cardGrowth = findViewById(R.id.card_growth);
        growthFront = findViewById(R.id.growth_front);
        growthBack = findViewById(R.id.growth_back);
        gridBadgesBack = findViewById(R.id.grid_badges_back);
        avatarPicker = registerForActivityResult(new ActivityResultContracts.GetContent(), uri -> {
            if (uri == null) return;
            saveAvatarUri(uri);
            imgAvatar.setImageURI(uri);
        });

        if (btnSettings != null) {
            btnSettings.setOnClickListener(v -> {
                Intent intent = new Intent(ProfileActivity.this, SettingsActivity.class);
                startActivity(intent);
            });
        }
        setupGrowthFlip();
        populateBadgeGridForTest(0, 0, 0, 0, 0);
        if (imgAvatar != null) {
            imgAvatar.setOnClickListener(v -> avatarPicker.launch("image/*"));
            loadAvatarFromPrefs();
        }
        if (imgAvatarEdit != null) {
            imgAvatarEdit.setOnClickListener(v -> avatarPicker.launch("image/*"));
        }
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

    private void showBadgeDialog(String title, String condition, int imageRes) {
        LayoutInflater inflater = LayoutInflater.from(this);
        View dialogView = inflater.inflate(R.layout.dialog_badge_detail, null);
        ImageView image = dialogView.findViewById(R.id.dialog_badge_image);
        TextView tvTitle = dialogView.findViewById(R.id.dialog_badge_title);
        TextView tvCondition = dialogView.findViewById(R.id.dialog_badge_condition);

        image.setImageResource(imageRes);
        tvTitle.setText(title);
        tvCondition.setText("獲得条件: " + condition);

        new AlertDialog.Builder(this)
                .setView(dialogView)
                .setPositiveButton("閉じる", null)
                .show();
    }

    private void populateBadgeGridForTest(int understand, int question, int collab, int engagement, int stability) {
        if (gridBadgesBack == null) return;
        gridBadgesBack.removeAllViews();
        gridBadgesBack.setAlignmentMode(GridLayout.ALIGN_BOUNDS);
        float density = getResources().getDisplayMetrics().density;
        int size = (int) (64 * density);
        int margin = (int) (4 * density);

        for (int i = 1; i <= 28; i++) {
            String name = String.format("badge_%02d", i);
            int resId = getResources().getIdentifier(name, "drawable", getPackageName());
            if (resId == 0) continue;

            BadgeMeta meta = badgeMetaFor(name);
            boolean unlocked = isBadgeUnlocked(name, understand, question, collab, engagement, stability);

            androidx.cardview.widget.CardView card = new androidx.cardview.widget.CardView(this);
            card.setCardBackgroundColor(unlocked ? 0xFFFFFFFF : 0xFFE5E7EB);
            card.setCardElevation(2f);
            card.setRadius(size / 2f);

            GridLayout.LayoutParams params = new GridLayout.LayoutParams();
            params.width = size;
            params.height = size;
            params.setMargins(margin, margin, margin, margin);
            card.setLayoutParams(params);

            if (unlocked) {
                ImageView img = new ImageView(this);
                img.setLayoutParams(new LinearLayout.LayoutParams(
                        LinearLayout.LayoutParams.MATCH_PARENT,
                        LinearLayout.LayoutParams.MATCH_PARENT
                ));
                img.setScaleType(ImageView.ScaleType.CENTER_CROP);
                img.setImageResource(resId);
                card.addView(img);
            }

            card.setOnClickListener(v -> showBadgeDialog(meta.title, meta.condition, resId));

            gridBadgesBack.addView(card);
        }
    }

    private void setupGrowthFlip() {
        if (cardGrowth == null || growthFront == null || growthBack == null) return;
        float scale = getResources().getDisplayMetrics().density;
        cardGrowth.setCameraDistance(8000 * scale);
        growthBack.setVisibility(View.GONE);
        cardGrowth.setOnClickListener(v -> flipGrowthCard());
    }

    private void flipGrowthCard() {
        if (cardGrowth == null || growthFront == null || growthBack == null) return;
        ObjectAnimator firstHalf = ObjectAnimator.ofFloat(cardGrowth, "rotationY", 0f, 90f);
        firstHalf.setDuration(140);
        ObjectAnimator secondHalf = ObjectAnimator.ofFloat(cardGrowth, "rotationY", -90f, 0f);
        secondHalf.setDuration(140);

        firstHalf.addListener(new AnimatorListenerAdapter() {
            @Override
            public void onAnimationEnd(Animator animation) {
                showingFront = !showingFront;
                growthFront.setVisibility(showingFront ? View.VISIBLE : View.GONE);
                growthBack.setVisibility(showingFront ? View.GONE : View.VISIBLE);
                cardGrowth.setRotationY(-90f);
                secondHalf.start();
            }
        });
        firstHalf.start();
    }

    private static class BadgeMeta {
        final String title;
        final String condition;
        BadgeMeta(String title, String condition) {
            this.title = title;
            this.condition = condition;
        }
    }

    private BadgeMeta badgeMetaFor(String id) {
        switch (id) {
            case "badge_01": return new BadgeMeta("協力の見習い", "協力系の成長ポイントが1以上");
            case "badge_02": return new BadgeMeta("インタラクション加速者", "参加系の成長ポイントが3以上");
            case "badge_03": return new BadgeMeta("クラス守護バリア", "安定系の成長ポイントが10以上");
            case "badge_04": return new BadgeMeta("クラス連結コア", "協力系の成長ポイントが15以上");
            case "badge_05": return new BadgeMeta("ソクラテスの眼", "質問系の成長ポイントが15以上");
            case "badge_06": return new BadgeMeta("チームエンジン", "協力系の成長ポイントが6以上");
            case "badge_07": return new BadgeMeta("ヒントハンター", "質問系の成長ポイントが3以上");
            case "badge_08": return new BadgeMeta("ムード点火師", "参加系の成長ポイントが6以上");
            case "badge_09": return new BadgeMeta("リズムウォッチャー", "安定系の成長ポイントが3以上");
            case "badge_10": return new BadgeMeta("不動のガーディアン", "安定系の成長ポイントが15以上");
            case "badge_11": return new BadgeMeta("五角形レジェンド", "全ての成長ポイントが10以上");
            case "badge_12": return new BadgeMeta("全体ビートメーカー", "参加系の成長ポイントが20以上");
            case "badge_13": return new BadgeMeta("共創キャプテン", "協力系の成長ポイントが10以上");
            case "badge_14": return new BadgeMeta("参加の見習い", "参加系の成長ポイントが1以上");
            case "badge_15": return new BadgeMeta("安定の見習い", "安定系の成長ポイントが1以上");
            case "badge_16": return new BadgeMeta("対話イグナイター", "質問系の成長ポイントが6以上");
            case "badge_17": return new BadgeMeta("思考ダブルコア", "理解系と質問系の成長ポイントが10以上");
            case "badge_18": return new BadgeMeta("思考ナビゲーター", "理解系の成長ポイントが10以上");
            case "badge_19": return new BadgeMeta("授業プッシャー", "参加系の成長ポイントが10以上");
            case "badge_20": return new BadgeMeta("洞察チェイサー", "質問系の成長ポイントが10以上");
            case "badge_21": return new BadgeMeta("熱量スター", "参加系の成長ポイントが15以上");
            case "badge_22": return new BadgeMeta("理解の見習い", "理解系の成長ポイントが1以上");
            case "badge_23": return new BadgeMeta("真理トラッカー", "理解系の成長ポイントが15以上");
            case "badge_24": return new BadgeMeta("知識クラフター", "理解系の成長ポイントが6以上");
            case "badge_25": return new BadgeMeta("秩序リペアラー", "安定系の成長ポイントが6以上");
            case "badge_26": return new BadgeMeta("紅青コーディネーター", "協力系の成長ポイントが3以上");
            case "badge_27": return new BadgeMeta("解法トラベラー", "理解系の成長ポイントが3以上");
            case "badge_28": return new BadgeMeta("質問の見習い", "質問系の成長ポイントが1以上");
            default: return new BadgeMeta("バッジ", "条件データ準備中");
        }
    }

    private boolean isBadgeUnlocked(String id, int understand, int question, int collab, int engagement, int stability) {
        switch (id) {
            case "badge_01": return collab >= 1;
            case "badge_02": return engagement >= 3;
            case "badge_03": return stability >= 10;
            case "badge_04": return collab >= 15;
            case "badge_05": return question >= 15;
            case "badge_06": return collab >= 6;
            case "badge_07": return question >= 3;
            case "badge_08": return engagement >= 6;
            case "badge_09": return stability >= 3;
            case "badge_10": return stability >= 15;
            case "badge_11": return understand >= 10 && question >= 10 && collab >= 10 && engagement >= 10 && stability >= 10;
            case "badge_12": return engagement >= 20;
            case "badge_13": return collab >= 10;
            case "badge_14": return engagement >= 1;
            case "badge_15": return stability >= 1;
            case "badge_16": return question >= 6;
            case "badge_17": return understand >= 10 && question >= 10;
            case "badge_18": return understand >= 10;
            case "badge_19": return engagement >= 10;
            case "badge_20": return question >= 10;
            case "badge_21": return engagement >= 15;
            case "badge_22": return understand >= 1;
            case "badge_23": return understand >= 15;
            case "badge_24": return understand >= 6;
            case "badge_25": return stability >= 6;
            case "badge_26": return collab >= 3;
            case "badge_27": return understand >= 3;
            case "badge_28": return question >= 1;
            default: return false;
        }
    }

    private void saveAvatarUri(Uri uri) {
        try {
            getContentResolver().takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION);
        } catch (Exception ignored) { }
        SharedPreferences prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        prefs.edit().putString(KEY_AVATAR_URI, uri.toString()).apply();
    }

    private void loadAvatarFromPrefs() {
        SharedPreferences prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        String uriString = prefs.getString(KEY_AVATAR_URI, null);
        if (uriString == null || uriString.isEmpty()) return;
        try {
            Uri uri = Uri.parse(uriString);
            imgAvatar.setImageURI(uri);
        } catch (Exception ignored) { }
    }

    private void applyDefaultAvatarIfNeeded(String role) {
        SharedPreferences prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        String uriString = prefs.getString(KEY_AVATAR_URI, null);
        if (uriString != null && !uriString.isEmpty()) return;
        if ("teacher".equals(role)) {
            imgAvatar.setImageResource(android.R.drawable.ic_menu_myplaces);
            imgAvatar.setColorFilter(0xFF4F46E5);
        } else {
            imgAvatar.setImageResource(android.R.drawable.ic_menu_info_details);
            imgAvatar.setColorFilter(0xFF2563EB);
        }
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
