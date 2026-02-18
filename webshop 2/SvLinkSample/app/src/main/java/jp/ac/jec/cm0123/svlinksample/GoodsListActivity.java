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
import java.text.NumberFormat;
import java.util.ArrayList;

import okhttp3.Call;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;

public class GoodsListActivity extends AppCompatActivity {

    private GoodsAdapter adapter;

    class GoodsAdapter extends ArrayAdapter<GoodsItem> {
        GoodsAdapter(Context context) {
            super(context, R.layout.row_goods);
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            GoodsItem item = getItem(position);
            if (convertView == null) {
                convertView = LayoutInflater.from(getContext()).inflate(R.layout.row_goods, parent, false);
            }

            TextView txtName = convertView.findViewById(R.id.txtGoodsName);
            TextView txtPrice = convertView.findViewById(R.id.txtGoodsPrice);
            TextView txtStock = convertView.findViewById(R.id.txtGoodsStock);
            TextView btnDetail = convertView.findViewById(R.id.btnDetail);

            if (item != null) {
                txtName.setText(item.getName());
                txtPrice.setText("¥" + NumberFormat.getInstance().format(item.getPrice()));
                txtStock.setText("在庫: " + item.getStock());
                btnDetail.setOnClickListener(v -> {
                    Intent intent = new Intent(GoodsListActivity.this, GoodsDetailActivity.class);
                    intent.putExtra("gid", item.getId());
                    startActivity(intent);
                });
            }
            return convertView;
        }
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_goods_list);

        int cid = getIntent().getIntExtra("cid", 0);
        String title = getIntent().getStringExtra("title");
        TextView txtTitle = findViewById(R.id.txtGoodsTitle);
        if (title != null) {
            txtTitle.setText(title);
        }
        findViewById(R.id.btnBackCategory).setOnClickListener(v -> finish());

        loadGoods(cid);
    }

    private void loadGoods(int cid) {
        Uri.Builder uriBuilder = Uri.parse(ApiConfig.BASE_URL + "goods.php").buildUpon();
        if (cid > 0) {
            uriBuilder.appendQueryParameter("cid", String.valueOf(cid));
        }

        Request request = new Request.Builder().url(uriBuilder.toString()).build();
        OkHttpClient client = new OkHttpClient();
        client.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (!response.isSuccessful()) {
                    return;
                }
                String resString = response.body().string();
                final ArrayList<GoodsItem> list = JsonHelper.parseGoodsList(resString);
                Handler handler = new Handler(Looper.getMainLooper());
                handler.post(() -> {
                    adapter = new GoodsAdapter(GoodsListActivity.this);
                    adapter.addAll(list);
                    ListView listView = findViewById(R.id.listGoods);
                    listView.setAdapter(adapter);
                });
            }

            @Override
            public void onFailure(Call call, IOException e) {
            }
        });
    }
}
