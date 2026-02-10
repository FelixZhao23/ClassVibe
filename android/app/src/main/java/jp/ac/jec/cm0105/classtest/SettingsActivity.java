package jp.ac.jec.cm0105.classtest;

import android.content.SharedPreferences;
import android.content.Intent;
import androidx.appcompat.app.AlertDialog;
import com.google.android.gms.auth.api.signin.GoogleSignIn;
import com.google.android.gms.auth.api.signin.GoogleSignInClient;
import com.google.android.gms.auth.api.signin.GoogleSignInOptions;
import com.google.android.material.button.MaterialButton;
import android.os.Bundle;
import android.widget.CompoundButton;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SwitchCompat;

public class SettingsActivity extends AppCompatActivity {

    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_HAPTICS = "haptics_enabled";
    private GoogleSignInClient signInClient;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_settings);

        SwitchCompat switchHaptics = findViewById(R.id.switch_haptics);
        SharedPreferences prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        boolean enabled = prefs.getBoolean(KEY_HAPTICS, true);
        switchHaptics.setChecked(enabled);

        switchHaptics.setOnCheckedChangeListener((CompoundButton buttonView, boolean isChecked) -> {
            prefs.edit().putBoolean(KEY_HAPTICS, isChecked).apply();
        });

        GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
                .requestEmail()
                .build();
        signInClient = GoogleSignIn.getClient(this, gso);

        MaterialButton btnSignOut = findViewById(R.id.btn_sign_out);
        btnSignOut.setOnClickListener(v -> {
            new AlertDialog.Builder(SettingsActivity.this)
                    .setTitle("ログアウトしますか？")
                    .setMessage("ログアウトすると再度ログインが必要になります。")
                    .setNegativeButton("キャンセル", null)
                    .setPositiveButton("ログアウト", (d, w) -> doSignOut())
                    .show();
        });
    }

    private void doSignOut() {
        signInClient.signOut().addOnCompleteListener(task -> {
            Intent intent = new Intent(SettingsActivity.this, loginActivity.class);
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
            startActivity(intent);
            finish();
        });
    }
}
