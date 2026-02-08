package jp.ac.jec.cm0105.classtest;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.CompoundButton;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SwitchCompat;

public class SettingsActivity extends AppCompatActivity {

    private static final String PREFS = "classvibe_prefs";
    private static final String KEY_HAPTICS = "haptics_enabled";

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
    }
}

