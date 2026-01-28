//package jp.ac.jec.cm0105.classtest;
//
//import android.content.Intent;
//import android.os.Bundle;
//import android.util.Log;
//import android.widget.EditText;
//import android.widget.Toast;
//import androidx.appcompat.app.AppCompatActivity;
//import com.google.android.gms.auth.api.signin.GoogleSignIn;
//import com.google.android.gms.auth.api.signin.GoogleSignInAccount;
//import com.google.android.gms.auth.api.signin.GoogleSignInClient;
//import com.google.android.gms.auth.api.signin.GoogleSignInOptions;
//import com.google.android.gms.common.api.ApiException;
//import com.google.android.gms.tasks.Task;
//import com.google.android.material.button.MaterialButton;
//
//public class loginActivity extends AppCompatActivity {
//
//    private EditText etClassCode;
//    private MaterialButton btnGoogleLogin;
//    private GoogleSignInClient mGoogleSignInClient;
//    private static final int RC_SIGN_IN = 100; // 请求码
//
//    @Override
//    protected void onCreate(Bundle savedInstanceState) {
//        super.onCreate(savedInstanceState);
//        setContentView(R.layout.activity_login);
//
//        // 1. 初始化 UI
//        etClassCode = findViewById(R.id.et_class_code);
//        btnGoogleLogin = findViewById(R.id.btn_google_login);
//
//        // 2. 配置谷歌登录 (即使不点击也要先配置好)
//        GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
//                .requestEmail()
//                .build();
//        mGoogleSignInClient = GoogleSignIn.getClient(this, gso);
//
//        // 3. 按钮点击逻辑
//        btnGoogleLogin.setOnClickListener(v -> {
//            String inputCode = etClassCode.getText().toString().trim();
//
//            // 假设正确的授業コード是 "1234"
//            if (inputCode.equals("1234")) {
//                signIn(); // 触发谷歌登录
//            } else {
//                Toast.makeText(this, "授業コード不正确，请重新输入", Toast.LENGTH_SHORT).show();
//                etClassCode.setError("代码错误");
//            }
//        });
//    }
//
//    private void signIn() {
//        Intent signInIntent = mGoogleSignInClient.getSignInIntent();
//        startActivityForResult(signInIntent, RC_SIGN_IN);
//    }
//
//    @Override
//    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
//        super.onActivityResult(requestCode, resultCode, data);
//
//        if (requestCode == RC_SIGN_IN) {
//            Task<GoogleSignInAccount> task = GoogleSignIn.getSignedInAccountFromIntent(data);
//            // 在 loginActivity.java 的 handleSignInResult 或 onActivityResult 中
//            try {
//                GoogleSignInAccount account = task.getResult(ApiException.class);
//
//                // 1. 获取用户信息
//                String personName = account.getDisplayName();
//                String personEmail = account.getEmail();
//
//                Toast.makeText(this, "登录成功！欢迎 " + personName, Toast.LENGTH_SHORT).show();
//
//                // 2. 核心：跳转到 MainActivity
//                Intent intent = new Intent(loginActivity.this, MainActivity.class);
//
//                // (可选) 把名字传给下一个页面显示
//                intent.putExtra("USER_NAME", personName);
//
//                startActivity(intent);
//
//                // 3. 关掉当前的登录页面，防止用户按返回键又回到登录页
//                finish();
//
//            } catch (ApiException e) {
//                Log.w("GoogleLogin", "signInResult:failed code=" + e.getStatusCode());
//            }
//        }
//    }
//}

package jp.ac.jec.cm0105.classtest;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.util.Log;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.gms.auth.api.signin.GoogleSignIn;
import com.google.android.gms.auth.api.signin.GoogleSignInAccount;
import com.google.android.gms.auth.api.signin.GoogleSignInClient;
import com.google.android.gms.auth.api.signin.GoogleSignInOptions;
import com.google.android.gms.common.api.ApiException;
import com.google.android.gms.tasks.Task;
import com.google.android.material.button.MaterialButton;
import com.google.firebase.database.DataSnapshot;
import com.google.firebase.database.DatabaseError;
import com.google.firebase.database.DatabaseReference;
import com.google.firebase.database.FirebaseDatabase;
import com.google.firebase.database.ValueEventListener;
import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;

public class loginActivity extends AppCompatActivity {

    private EditText etClassCode;
    private ImageButton btnScanQr;//二维码
    private MaterialButton btnGoogleLogin;

    // Google 登录相关
    private GoogleSignInClient mGoogleSignInClient;
    private static final int RC_SIGN_IN = 100;

    // Firebase 相关
    private DatabaseReference codesRef;

    // ★关键变量：用来暂存查到的课程ID
    private String targetCourseId = null;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // 1. 初始化 UI
        etClassCode = findViewById(R.id.et_class_code);
        btnGoogleLogin = findViewById(R.id.btn_google_login);

        // 1. 绑定控件--二维码
        etClassCode = findViewById(R.id.et_class_code);
        btnScanQr = findViewById(R.id.btn_scan_qr); // 绑定新按钮

        // 2. 初始化 Firebase 引用
        // 指向 "active_codes" 节点
        codesRef = FirebaseDatabase.getInstance("https://classvibe-2025-default-rtdb.asia-southeast1.firebasedatabase.app")
                .getReference("active_codes");

        // 3. 配置 Google 登录
        GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
                .requestEmail()
                .build();
        mGoogleSignInClient = GoogleSignIn.getClient(this, gso);

        // 4. 按钮点击事件
        btnGoogleLogin.setOnClickListener(v -> handleLoginAttempt());

        // 5. 设置扫码按钮点击事件
        btnScanQr.setOnClickListener(v -> {
            // 启动扫码
            ScanOptions options = new ScanOptions();
            options.setPrompt("请扫描老师展示的课程二维码");
            options.setBeepEnabled(true);
            options.setOrientationLocked(true);
            options.setCaptureActivity(CaptureActivityPortrait.class); // 如果你加了竖屏类就用这个
            barcodeLauncher.launch(options);
        });
    }//onCreate end

    // 3. 处理扫码结果
    private final ActivityResultLauncher<ScanOptions> barcodeLauncher = registerForActivityResult(new ScanContract(),
            result -> {
                if(result.getContents() != null) {
                    // 扫码成功！
                    String scannedCode = result.getContents();

                    // 将扫到的码自动填入 EditText
                    etClassCode.setText(scannedCode);

                    // (可选) 可以在这里直接触发登录逻辑，或者只填入让用户自己点 Google Login
                    Toast.makeText(this, "已读取课堂代码", Toast.LENGTH_SHORT).show();
                }
            });

    private void handleLoginAttempt() {
        String inputCode = etClassCode.getText().toString().trim();

        // 简单的非空检查
        if (TextUtils.isEmpty(inputCode)) {
            etClassCode.setError("请输入授業コード");
            return;
        }

        // 禁用按钮防止重复点击
        btnGoogleLogin.setEnabled(false);
        btnGoogleLogin.setText("検証中..."); // 提示正在验证

        // ★核心逻辑：去 Firebase 查询这个代码是否存在
        codesRef.child(inputCode).addListenerForSingleValueEvent(new ValueEventListener() {
            @Override
            public void onDataChange(@NonNull DataSnapshot snapshot) {
                if (snapshot.exists()) {
                    // A. 代码存在！
                    // 获取对应的值 (例如: "-OivOVfeliAkn8zNvD2R")
                    targetCourseId = snapshot.getValue(String.class);

                    Log.d("Login", "验证成功，课程ID: " + targetCourseId);

                    // B. 代码验证通过后，才启动谷歌登录
                    signIn();
                } else {
                    // C. 代码不存在
                    Toast.makeText(loginActivity.this, "無効な授業コードです", Toast.LENGTH_SHORT).show();
                    etClassCode.setError("コードが見つかりません");

                    // 恢复按钮状态
                    resetButton();
                }
            }

            @Override
            public void onCancelled(@NonNull DatabaseError error) {
                Toast.makeText(loginActivity.this, "网络错误，请重试", Toast.LENGTH_SHORT).show();
                resetButton();
            }
        });
    }

    private void resetButton() {
        btnGoogleLogin.setEnabled(true);
        btnGoogleLogin.setText("ログイン / Login"); // 这里的文字最好换回你原本xml里的文字
    }

    private void signIn() {
        Intent signInIntent = mGoogleSignInClient.getSignInIntent();
        startActivityForResult(signInIntent, RC_SIGN_IN);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == RC_SIGN_IN) {
            Task<GoogleSignInAccount> task = GoogleSignIn.getSignedInAccountFromIntent(data);
            handleSignInResult(task);
        }
    }

// ... 之前的代码 ...

    private void handleSignInResult(Task<GoogleSignInAccount> completedTask) {
        try {
            GoogleSignInAccount account = completedTask.getResult(ApiException.class);
            String personName = account.getDisplayName();
            Toast.makeText(this, "登录成功: " + personName, Toast.LENGTH_SHORT).show();

            // ★★★ 修改这里：直接跳转到 ClassroomActivity ★★★
            Intent intent = new Intent(loginActivity.this, ClassroomActivity.class); // 改成 ClassroomActivity

            // 1. 传用户名 (欢迎用)
            intent.putExtra("USER_NAME", personName);

            // 2. 传课程ID (核心！告诉教室页面我们要进哪个房间)
            // targetCourseId 是之前 verifyCode 时查出来的，比如 "-OivOVfeliAkn8zNvD2R"
            if (targetCourseId != null) {
                intent.putExtra("COURSE_ID", targetCourseId);
            } else {
                // 如果没有ID (逻辑漏洞保护)，还是回主页吧
                Toast.makeText(this, "课程ID丢失，返回主页", Toast.LENGTH_SHORT).show();
                startActivity(new Intent(this, MainActivity.class));
                finish();
                return;
            }

            startActivity(intent);
            finish();

        } catch (ApiException e) {
            Log.w("GoogleLogin", "登录失败 code=" + e.getStatusCode());
            resetButton();
        }
    }
}