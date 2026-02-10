package jp.ac.jec.cm0105.classtest;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.TextUtils;
import android.text.Editable;
import android.text.TextWatcher;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AlertDialog;
import com.google.android.material.bottomnavigation.BottomNavigationView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;

import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ValueEventListener;
import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class MainActivity extends AppCompatActivity {

    private EditText etCode;
    private TextView tvCoursePreview;
    private String userName;
    private String userId;
    private DatabaseReference codesRef;
    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_LAST_NAME = "last_student_name";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        userName = getIntent().getStringExtra("USER_NAME");
        if (userName == null || userName.trim().isEmpty()) {
            userName = getSharedPreferences(PREFS, MODE_PRIVATE).getString(KEY_LAST_NAME, null);
        }

        SharedPreferences prefs = getSharedPreferences("AppPrefs", MODE_PRIVATE);
        userId = prefs.getString("SAVED_USER_ID", null);
        if (userId == null) {
            userId = java.util.UUID.randomUUID().toString();
            prefs.edit().putString("SAVED_USER_ID", userId).apply();
        }

        codesRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                .getReference("active_codes");

        etCode = findViewById(R.id.et_class_code_home);
        tvCoursePreview = findViewById(R.id.tv_course_preview);
        ImageButton btnScan = findViewById(R.id.btn_scan_qr_home);
        Button btnJoin = findViewById(R.id.btn_join_home);
        BottomNavigationView bottomNav = findViewById(R.id.bottom_navigation);

        etCode.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override public void afterTextChanged(Editable s) {
                String code = s.toString().trim();
                if (code.length() == 4) {
                    previewCourseByCode(code);
                } else {
                    tvCoursePreview.setText("");
                }
            }
        });

        btnScan.setOnClickListener(v -> {
            ScanOptions options = new ScanOptions();
            options.setDesiredBarcodeFormats(ScanOptions.QR_CODE);
            options.setPrompt("教室のQRを読み取ってください");
            options.setBeepEnabled(true);
            options.setOrientationLocked(true);
            options.setCaptureActivity(CaptureActivityPortrait.class);
            barcodeLauncher.launch(options);
        });

        btnJoin.setOnClickListener(v -> joinClassByCode(etCode.getText().toString().trim()));

        if (bottomNav != null) {
            bottomNav.setSelectedItemId(R.id.nav_classroom);
            bottomNav.setOnItemSelectedListener(item -> {
                if (item.getItemId() == R.id.nav_profile) {
                    Intent intent = new Intent(MainActivity.this, ProfileActivity.class);
                    intent.putExtra("USER_ID", userId);
                    intent.putExtra("USER_NAME", userName);
                    startActivity(intent);
                    return true;
                }
                return true;
            });
        }
    }

    private final ActivityResultLauncher<ScanOptions> barcodeLauncher =
            registerForActivityResult(new ScanContract(), result -> {
                if (result.getContents() != null) {
                    String raw = result.getContents().trim();
                    String code = extractFourDigits(raw);
                    if (code != null) {
                        etCode.setText(code);
                    } else {
                        Toast.makeText(MainActivity.this, "4桁コードが見つかりません", Toast.LENGTH_SHORT).show();
                    }
                }
            });

    private String extractFourDigits(String raw) {
        if (raw == null) return null;
        String trimmed = raw.trim();
        if (trimmed.matches("^\\d{4}$")) {
            return trimmed;
        }
        Pattern pattern = Pattern.compile("\\d{4}");
        Matcher matcher = pattern.matcher(raw);
        if (matcher.find()) {
            return matcher.group();
        }
        return null;
    }

    private void joinClassByCode(String code) {
        if (TextUtils.isEmpty(code)) {
            etCode.setError("コードを入力してください");
            return;
        }
        codesRef.child(code).addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                if (!snapshot.exists()) {
                    Toast.makeText(MainActivity.this, "無効なコードです", Toast.LENGTH_SHORT).show();
                    return;
                }
                String courseId = snapshot.getValue(String.class);
                FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                        .getReference("courses")
                        .child(courseId)
                        .addListenerForSingleValueEvent(new ValueEventListener() {
                            @Override
                            public void onDataChange(@NonNull DataSnapshot courseSnap) {
                                boolean isActive = Boolean.TRUE.equals(courseSnap.child("is_active").getValue(Boolean.class));
                                String title = courseSnap.child("title").getValue(String.class);
                                String courseTitle = title == null ? "未設定授業" : title;
                                if (!isActive) {
                                    Toast.makeText(MainActivity.this, "「" + courseTitle + "」はまだ開始していません", Toast.LENGTH_SHORT).show();
                                    return;
                                }
                                Intent intent = new Intent(MainActivity.this, ClassroomActivity.class);
                                intent.putExtra("COURSE_ID", courseId);
                                intent.putExtra("USER_NAME", userName == null ? "student" : userName);
                                startActivity(intent);
                            }

                            @Override
                            public void onCancelled(@NonNull DatabaseError error) {
                                Toast.makeText(MainActivity.this, "接続エラー", Toast.LENGTH_SHORT).show();
                            }
                        });
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) {
                Toast.makeText(MainActivity.this, "接続エラー", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void previewCourseByCode(String code) {
        codesRef.child(code).addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                if (!snapshot.exists()) {
                    tvCoursePreview.setText("授業が見つかりません");
                    return;
                }
                String courseId = snapshot.getValue(String.class);
                FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                        .getReference("courses")
                        .child(courseId)
                        .child("title")
                        .addListenerForSingleValueEvent(new ValueEventListener() {
                            @Override
                            public void onDataChange(@NonNull DataSnapshot titleSnap) {
                                String title = titleSnap.getValue(String.class);
                                tvCoursePreview.setText(title == null ? "" : "この授業: " + title);
                            }

                            @Override
                            public void onCancelled(@NonNull DatabaseError error) {
                                tvCoursePreview.setText("");
                            }
                        });
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) {
                tvCoursePreview.setText("");
            }
        });
    }
}
