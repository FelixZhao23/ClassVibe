package jp.ac.jec.cm0105.classtest;

import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

public class registerActivity extends AppCompatActivity {

    private EditText etUsername, etPassword, etConfirmPassword;
    private Button btnRegister;
    private TextView tvBackLogin;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_register);

        // 处理沉浸式边距
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        // 1. 初始化控件
        etUsername = findViewById(R.id.et_username);
        etPassword = findViewById(R.id.et_password);
        etConfirmPassword = findViewById(R.id.et_confirm_password);
        btnRegister = findViewById(R.id.btn_register);
        tvBackLogin = findViewById(R.id.tv_back_to_login);

        // 2. 注册按钮点击事件
        btnRegister.setOnClickListener(v -> {
            String name = etUsername.getText().toString().trim();
            String pwd = etPassword.getText().toString().trim();
            String cpwd = etConfirmPassword.getText().toString().trim();

            if (name.isEmpty() || pwd.isEmpty()) {
                Toast.makeText(this, "信息请填写完整", Toast.LENGTH_SHORT).show();
            } else if (!pwd.equals(cpwd)) {
                Toast.makeText(this, "两次密码输入不一致", Toast.LENGTH_SHORT).show();
            } else {
                // 这里执行注册成功的逻辑
                Toast.makeText(this, "注册成功！", Toast.LENGTH_SHORT).show();
                finish(); // 注册成功后返回登录界面
            }
        });

        // 3. 点击“去登录”返回
        tvBackLogin.setOnClickListener(v -> finish());
    }
}