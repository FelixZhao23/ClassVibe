package jp.ac.jec.cm0123.svlinksample;

import android.util.Log;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;

public class JsonHelper {

    public static ArrayList<CategoryItem> parseCategoryList(String json) {
        ArrayList<CategoryItem> list = new ArrayList<>();
        try {
            JSONArray ary = new JSONArray(json);
            for (int i = 0; i < ary.length(); i++) {
                JSONObject obj = ary.getJSONObject(i);
                CategoryItem item = new CategoryItem();
                item.setId(obj.getInt("category_id"));
                item.setName(obj.getString("category_name"));
                list.add(item);
            }
        } catch (Exception e) {
            Log.e("JsonHelper", e.getMessage(), e);
        }
        return list;
    }

    public static ArrayList<GoodsItem> parseGoodsList(String json) {
        ArrayList<GoodsItem> list = new ArrayList<>();
        try {
            JSONArray ary = new JSONArray(json);
            for (int i = 0; i < ary.length(); i++) {
                JSONObject obj = ary.getJSONObject(i);
                GoodsItem item = new GoodsItem();
                item.setId(obj.getInt("goods_id"));
                item.setName(obj.getString("goods_name"));
                item.setPrice(obj.getInt("price"));
                item.setStock(obj.getInt("stock"));
                item.setImage(obj.optString("image", ""));
                list.add(item);
            }
        } catch (Exception e) {
            Log.e("JsonHelper", e.getMessage(), e);
        }
        return list;
    }

    public static GoodsDetail parseGoodsDetail(String json) {
        try {
            JSONObject obj = new JSONObject(json);
            GoodsDetail item = new GoodsDetail();
            item.setId(obj.getInt("goods_id"));
            item.setName(obj.getString("goods_name"));
            item.setPrice(obj.getInt("price"));
            item.setCostPrice(obj.optInt("cost_price", 0));
            item.setStock(obj.getInt("stock"));
            item.setCategory(obj.optString("category", ""));
            item.setMaker(obj.optString("maker", ""));
            item.setMakerUrl(obj.optString("maker_url", ""));
            item.setImage(obj.optString("image", ""));
            item.setDetail(obj.optString("detail", ""));
            return item;
        } catch (JSONException e) {
            Log.e("JsonHelper", e.getMessage(), e);
            return null;
        }
    }
}
