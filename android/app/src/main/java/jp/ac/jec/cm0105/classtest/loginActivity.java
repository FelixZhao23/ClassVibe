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
import android.util.Log;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.gms.auth.api.signin.GoogleSignIn;
import com.google.android.gms.auth.api.signin.GoogleSignInAccount;
import com.google.android.gms.auth.api.signin.GoogleSignInClient;
import com.google.android.gms.auth.api.signin.GoogleSignInOptions;
import com.google.android.gms.common.api.ApiException;
import com.google.android.gms.tasks.Task;
import com.google.android.material.button.MaterialButton;
 

public class loginActivity extends AppCompatActivity {

    private MaterialButton btnGoogleLogin;

    // Google 登录相关
    private GoogleSignInClient mGoogleSignInClient;
    private static final int RC_SIGN_IN = 100;
    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_LAST_NAME = "last_student_name";
    private static final String KEY_LOGIN_LABEL = "login_method_label";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // 1. 初始化 UI
        btnGoogleLogin = findViewById(R.id.btn_google_login);

        // 3. 配置 Google 登录
        GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
                .requestEmail()
                .build();
        mGoogleSignInClient = GoogleSignIn.getClient(this, gso);

        GoogleSignInAccount lastAccount = GoogleSignIn.getLastSignedInAccount(this);
        if (lastAccount != null) {
            String displayName = lastAccount.getDisplayName();
            String email = lastAccount.getEmail();
            getSharedPreferences(PREFS, MODE_PRIVATE)
                    .edit()
                    .putString(KEY_LAST_NAME, displayName == null ? "" : displayName)
                    .putString(KEY_LOGIN_LABEL, email == null ? "Google" : email)
                    .apply();
            Intent intent = new Intent(loginActivity.this, MainActivity.class);
            intent.putExtra("USER_NAME", displayName);
            startActivity(intent);
            finish();
            return;
        }

        // 登录页仅负责认证，课程加入放到 Home(MainActivity)
        btnGoogleLogin.setOnClickListener(v -> signIn());
    }//onCreate end

    private void resetButton() {
        btnGoogleLogin.setEnabled(true);
        btnGoogleLogin.setText("Google でログイン");
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
            String email = account.getEmail();
            Toast.makeText(this, "登录成功: " + personName, Toast.LENGTH_SHORT).show();

            getSharedPreferences(PREFS, MODE_PRIVATE)
                    .edit()
                    .putString(KEY_LAST_NAME, personName == null ? "" : personName)
                    .putString(KEY_LOGIN_LABEL, email == null ? "Google" : email)
                    .apply();

            // 登录后先进入 HOME 页面
            Intent intent = new Intent(loginActivity.this, MainActivity.class);

            // 1. 传用户名 (欢迎用)
            intent.putExtra("USER_NAME", personName);

            startActivity(intent);
            finish();

        } catch (ApiException e) {
            Log.w("GoogleLogin", "登录失败 code=" + e.getStatusCode());
            resetButton();
        }
    }
}
