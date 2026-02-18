package jp.ac.jec.cm0123.svlinksample;

public class RankItem {
    private int rank; // 順位
    private String name; // ユーザ名
    private String score; // 得点
    public int getRank() {
        return rank;
    }
    public void setRank(int rank) {
        this.rank = rank;
    }
    public String getName() {
        return name;
    }
    public void setName(String name) {
        this.name = name;
    }
    public String getScore() {
        return score;
    }
    public void setScore(String score) {
        this.score = score;
    }
}
