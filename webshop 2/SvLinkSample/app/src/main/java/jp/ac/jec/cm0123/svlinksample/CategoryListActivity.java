package jp.ac.jec.cm0123.svlinksample;

import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import java.io.IOException;
import java.util.ArrayList;

import okhttp3.Call;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;

public class CategoryListActivity extends AppCompatActivity {

    private CategoryAdapter adapter;

    class CategoryAdapter extends ArrayAdapter<CategoryItem> {
        CategoryAdapter(Context context) {
            super(context, R.layout.row_category);
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            CategoryItem item = getItem(position);
            if (convertView == null) {
                convertView = LayoutInflater.from(getContext()).inflate(R.layout.row_category, parent, false);
            }
            TextView txtName = convertView.findViewById(R.id.txtCategoryName);
            if (item != null) {
                txtName.setText(item.getName());
                convertView.setOnClickListener(v -> {
                    Intent intent = new Intent(CategoryListActivity.this, GoodsListActivity.class);
                    intent.putExtra("cid", item.getId());
                    intent.putExtra("title", item.getName());
                    startActivity(intent);
                });
            }
            return convertView;
        }
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_category_list);
        findViewById(R.id.btnBackHome).setOnClickListener(v -> finish());
        loadCategories();
    }

    private void loadCategories() {
        Uri.Builder uriBuilder = Uri.parse(ApiConfig.BASE_URL + "categories.php").buildUpon();
        Request request = new Request.Builder().url(uriBuilder.toString()).build();
        OkHttpClient client = new OkHttpClient();
        client.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (!response.isSuccessful()) {
                    return;
                }
                String resString = response.body().string();
                final ArrayList<CategoryItem> list = JsonHelper.parseCategoryList(resString);
                Handler handler = new Handler(Looper.getMainLooper());
                handler.post(() -> {
                    adapter = new CategoryAdapter(CategoryListActivity.this);
                    adapter.addAll(list);
                    ListView listView = findViewById(R.id.listCategory);
                    listView.setAdapter(adapter);
                });
            }

            @Override
            public void onFailure(Call call, IOException e) {
            }
        });
    }
}
