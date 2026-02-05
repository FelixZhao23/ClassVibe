package jp.ac.jec.cm0105.classtest;

import android.app.AlertDialog;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ServerValue; // 导入这个用来做减法
import com.google.firebase.database.ValueEventListener;

import java.util.Random;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class ProfileActivity extends AppCompatActivity {

    private int currentPoints = 0; // 默认0分，会从数据库更新
    private TextView tvPoints;
    private String currentTitle = "はじめの一歩";

    // Firebase 相关
    private DatabaseReference myPointsRef;
    private ValueEventListener pointsListener;
    private DatabaseReference growthRef;
    private ValueEventListener growthListener;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        // 1. 获取传递过来的数据
        String courseId = getIntent().getStringExtra("COURSE_ID");
        String userId = getIntent().getStringExtra("USER_ID");
        String userName = getIntent().getStringExtra("USER_NAME");

        tvPoints = findViewById(R.id.tv_points);

        if (courseId == null || userId == null) {
            Toast.makeText(this, "用户信息加载失败", Toast.LENGTH_SHORT).show();
            // 如果为空，可能无法进行后续操作
        } else {
            // 2. 连接到 Firebase：获取这个人的 points 节点
            // 路径：courses/{courseId}/members/{userId}/points
            myPointsRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                    .getReference("courses")
                    .child(courseId)
                    .child("members")
                    .child(userId)
                    .child("points");

            // 3. 实时监听分数变化
            pointsListener = new ValueEventListener() {
                @Override
                public void onDataChange(DataSnapshot snapshot) {
                    if (snapshot.exists()) {
                        // 如果数据库里有值，读出来 (Firebase 数字通常是 Long)
                        Long val = snapshot.getValue(Long.class);
                        currentPoints = (val != null) ? val.intValue() : 0;
                    } else {
                        // 如果数据库没有 points 字段（比如刚进来还没点过按钮），默认 0
                        currentPoints = 0;
                    }
                    updatePointDisplay();
                }

                @Override
                public void onCancelled(DatabaseError error) { }
            };
            // 开启监听
            myPointsRef.addValueEventListener(pointsListener);

            growthRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                    .getReference("users")
                    .child(userId);
            growthListener = new ValueEventListener() {
                @Override
                public void onDataChange(DataSnapshot snapshot) {
                    DataSnapshot growth = snapshot.child("growth");
                    Long expVal = growth.child("exp_total").getValue(Long.class);
                    currentPoints = expVal != null ? expVal.intValue() : currentPoints;
                    String title = growth.child("title_current").getValue(String.class);
                    currentTitle = title != null ? title : "はじめの一歩";
                    updatePointDisplay();
                }

                @Override
                public void onCancelled(DatabaseError error) { }
            };
            growthRef.addValueEventListener(growthListener);
        }

        // === 底部导航栏逻辑 ===
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

        // 关闭旧扭蛋/商店模块，切换为成长页
        hideLegacyGameUi();

        // 点击积分区域可快速查看成长日志
        tvPoints.setOnClickListener(v -> showGrowthLogDialog(userId));
    }

    private void hideLegacyGameUi() {
        int[] ids = new int[] {
                R.id.item_ticket, R.id.item_score, R.id.item_snack, R.id.item_monster, R.id.btn_gacha
        };
        for (int id : ids) {
            View view = findViewById(id);
            if (view != null) view.setVisibility(View.GONE);
        }
    }

    private void showGrowthLogDialog(String userId) {
        DatabaseReference logsRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                .getReference("users")
                .child(userId)
                .child("growth_logs");
        logsRef.addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(DataSnapshot snapshot) {
                List<String> rows = new ArrayList<>();
                for (DataSnapshot child : snapshot.getChildren()) {
                    String summary = child.child("summary").getValue(String.class);
                    Long exp = child.child("exp_gain").getValue(Long.class);
                    rows.add((summary == null ? "成長記録" : summary) + "  (+" + (exp == null ? 0 : exp) + " EXP)");
                    if (rows.size() >= 10) break;
                }
                String msg = rows.isEmpty() ? "まだ成長ログがありません。" : android.text.TextUtils.join("\n\n", rows);
                new AlertDialog.Builder(ProfileActivity.this)
                        .setTitle("称号: " + currentTitle)
                        .setMessage(msg)
                        .setPositiveButton("OK", null)
                        .show();
            }

            @Override
            public void onCancelled(DatabaseError error) { }
        });
    }

    private void setupShopItem(int includeId, String name, int price, int iconResId) {
        View itemView = findViewById(includeId);
        if (itemView == null) return;

        TextView tvName = itemView.findViewById(R.id.tv_item_name);
        TextView tvPrice = itemView.findViewById(R.id.tv_item_price);
        ImageView imgIcon = itemView.findViewById(R.id.img_item);
        Button btnBuy = itemView.findViewById(R.id.btn_buy);

        if (tvName != null) tvName.setText(name);
        if (tvPrice != null) tvPrice.setText(price + " pt");
        if (imgIcon != null) imgIcon.setImageResource(iconResId);

        if (btnBuy != null) {
            btnBuy.setOnClickListener(v -> {
                if (currentPoints >= price) {
                    // ★ 扣分逻辑改了：告诉 Firebase 减去价格
                    if (myPointsRef != null) {
                        // ServerValue.increment(-price) 意思是在现有值基础上减去 price
                        myPointsRef.setValue(ServerValue.increment(-price));
                    }
                    showDialog("購入成功！", "「" + name + "」を手に入れました！\n先生に見せて使ってください。");
                } else {
                    Toast.makeText(this, "ポイントが足りません！", Toast.LENGTH_SHORT).show();
                }
            });
        }
    }

    private void playGacha() {
        int gachaCost = 100;
        if (currentPoints < gachaCost) {
            Toast.makeText(this, "ポイントが足りません！", Toast.LENGTH_SHORT).show();
            return;
        }

        // ★ 扣分：告诉 Firebase 减去 100
        if (myPointsRef != null) {
            myPointsRef.setValue(ServerValue.increment(-gachaCost));
        }

        int random = new Random().nextInt(100);
        String resultTitle;
        String resultMsg;

        if (random < 5) {
            resultTitle = "★ 大当たり！ ★";
            resultMsg = "すごい！「レアモンスター」をゲットしました！";
        } else if (random < 20) {
            resultTitle = "当たり！";
            resultMsg = "やったね！「茶菓子」をゲットしました！";
        } else if (random < 60) {
            resultTitle = "参加賞";
            resultMsg = "「ポケットティッシュ」をもらいました。";
        } else {
            resultTitle = "ハズレ...";
            resultMsg = "残念！何も出ませんでした。\nまた挑戦してね！";
        }
        showDialog(resultTitle, resultMsg);
    }

    private void updatePointDisplay() {
        if (tvPoints != null) {
            tvPoints.setText(currentPoints + " EXP");
        }
    }

    private void showDialog(String title, String message) {
        new AlertDialog.Builder(this)
                .setTitle(title)
                .setMessage(message)
                .setPositiveButton("OK", null)
                .show();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        // 退出页面时移除监听器，这是一个好习惯
        if (myPointsRef != null && pointsListener != null) {
            myPointsRef.removeEventListener(pointsListener);
        }
        if (growthRef != null && growthListener != null) {
            growthRef.removeEventListener(growthListener);
        }
    }
}
