package jp.ac.jec.cm0123.svlinksample;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.TextUtils;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.text.NumberFormat;

import okhttp3.Call;
import okhttp3.FormBody;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class GoodsDetailActivity extends AppCompatActivity {

    private GoodsDetail detail;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_goods_detail);

        int gid = getIntent().getIntExtra("gid", 0);
        findViewById(R.id.btnBackGoods).setOnClickListener(v -> finish());
        loadDetail(gid);

        findViewById(R.id.btnUpdateStock).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                EditText edtStock = findViewById(R.id.edtStock);
                String stockStr = edtStock.getText().toString().trim();
                if (detail == null) {
                    return;
                }
                if (TextUtils.isEmpty(stockStr)) {
                    Toast.makeText(GoodsDetailActivity.this, "在庫数を入力してください", Toast.LENGTH_SHORT).show();
                    return;
                }
                int stock;
                try {
                    stock = Integer.parseInt(stockStr);
                } catch (NumberFormatException e) {
                    Toast.makeText(GoodsDetailActivity.this, "在庫数は数字で入力してください", Toast.LENGTH_SHORT).show();
                    return;
                }
                if (stock < 0) {
                    Toast.makeText(GoodsDetailActivity.this, "在庫数は0以上で入力してください", Toast.LENGTH_SHORT).show();
                    return;
                }
                updateStock(detail.getId(), stock);
            }
        });
    }

    private void loadDetail(int gid) {
        Uri.Builder uriBuilder = Uri.parse(ApiConfig.BASE_URL + "detail.php").buildUpon();
        uriBuilder.appendQueryParameter("gid", String.valueOf(gid));

        Request request = new Request.Builder().url(uriBuilder.toString()).build();
        OkHttpClient client = new OkHttpClient();
        client.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (!response.isSuccessful()) {
                    return;
                }
                String resString = response.body().string();
                detail = JsonHelper.parseGoodsDetail(resString);
                Handler handler = new Handler(Looper.getMainLooper());
                handler.post(() -> bindDetail());
            }

            @Override
            public void onFailure(Call call, IOException e) {
            }
        });
    }

    private void bindDetail() {
        if (detail == null) {
            return;
        }
        TextView txtName = findViewById(R.id.txtDetailName);
        TextView txtPrice = findViewById(R.id.txtDetailPrice);
        TextView txtCost = findViewById(R.id.txtDetailCost);
        TextView txtStock = findViewById(R.id.txtDetailStock);
        TextView txtCategory = findViewById(R.id.txtDetailCategory);
        TextView txtMaker = findViewById(R.id.txtDetailMaker);
        TextView txtDesc = findViewById(R.id.txtDetailDesc);
        EditText edtStock = findViewById(R.id.edtStock);

        txtName.setText(detail.getName());
        txtPrice.setText("¥" + NumberFormat.getInstance().format(detail.getPrice()));
        txtCost.setText("仕入れ値: ¥" + NumberFormat.getInstance().format(detail.getCostPrice()));
        txtStock.setText("在庫: " + detail.getStock());
        txtCategory.setText("カテゴリ: " + detail.getCategory());
        txtMaker.setText("メーカー: " + detail.getMaker());
        edtStock.setText(String.valueOf(detail.getStock()));
        if (!TextUtils.isEmpty(detail.getDetail())) {
            txtDesc.setText(detail.getDetail());
        } else {
            txtDesc.setText("説明: (なし)");
        }

        if (!TextUtils.isEmpty(detail.getImage())) {
            loadImage(ApiConfig.IMAGE_BASE_URL + detail.getImage());
        }
    }

    private void loadImage(String urlString) {
        new Thread(() -> {
            try {
                URL url = new URL(urlString);
                HttpURLConnection connection = (HttpURLConnection) url.openConnection();
                connection.connect();
                InputStream input = connection.getInputStream();
                Bitmap bitmap = BitmapFactory.decodeStream(input);
                Handler handler = new Handler(Looper.getMainLooper());
                handler.post(() -> {
                    ImageView imageView = findViewById(R.id.imgDetail);
                    imageView.setImageBitmap(bitmap);
                });
            } catch (Exception e) {
            }
        }).start();
    }

    private void updateStock(int gid, int stock) {
        RequestBody requestBody = new FormBody.Builder()
                .add("gid", String.valueOf(gid))
                .add("stock", String.valueOf(stock))
                .build();
        Request request = new Request.Builder()
                .url(ApiConfig.BASE_URL + "updateStock.php")
                .post(requestBody)
                .build();
        OkHttpClient client = new OkHttpClient();
        client.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (!response.isSuccessful()) {
                    return;
                }
                Handler handler = new Handler(Looper.getMainLooper());
                handler.post(() -> {
                    Toast.makeText(GoodsDetailActivity.this, "在庫数を更新しました", Toast.LENGTH_SHORT).show();
                    loadDetail(gid);
                });
            }

            @Override
            public void onFailure(Call call, IOException e) {
            }
        });
    }
}
