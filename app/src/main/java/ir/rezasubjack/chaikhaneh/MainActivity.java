package ir.rezasubjack.chaikhaneh;

import android.app.Activity;
import android.os.Bundle;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.content.Intent;
import android.net.Uri;
import android.view.View;
import android.widget.FrameLayout;
import android.widget.ProgressBar;

public class MainActivity extends Activity {
    private static final String HOME = "https://chaikhane.emammahdi.rezasubjack.ir/";
    private WebView webView;
    private ProgressBar progress;
    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        getWindow().setStatusBarColor(0xFF4E342E);
        getWindow().setNavigationBarColor(0xFF2B1B17);
        FrameLayout root = new FrameLayout(this);
        webView = new WebView(this);
        progress = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        root.addView(webView, new FrameLayout.LayoutParams(-1, -1));
        root.addView(progress, new FrameLayout.LayoutParams(-1, 6));
        setContentView(root);
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true); s.setDomStorageEnabled(true); s.setDatabaseEnabled(true);
        s.setLoadWithOverviewMode(true); s.setUseWideViewPort(true); s.setSupportZoom(false);
        s.setAllowFileAccess(false); s.setAllowContentAccess(true);
        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, false);
        webView.setWebChromeClient(new WebChromeClient() {
            @Override public void onProgressChanged(WebView v, int n) { progress.setProgress(n); progress.setVisibility(n >= 100 ? View.GONE : View.VISIBLE); }
        });
        webView.setWebViewClient(new WebViewClient() {
            @Override public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest req) {
                Uri u = req.getUrl(); String h = u.getHost() == null ? "" : u.getHost();
                if (h.equals("chaikhane.emammahdi.rezasubjack.ir")) return false;
                if ("https".equals(u.getScheme()) || "http".equals(u.getScheme())) { startActivity(new Intent(Intent.ACTION_VIEW, u)); return true; }
                return true;
            }
        });
        if (state != null) webView.restoreState(state); else webView.loadUrl(HOME);
    }
    @Override protected void onSaveInstanceState(Bundle out) { super.onSaveInstanceState(out); webView.saveState(out); }
    @Override public void onBackPressed() { if (webView != null && webView.canGoBack()) webView.goBack(); else super.onBackPressed(); }
    @Override protected void onDestroy() { if (webView != null) { webView.stopLoading(); webView.destroy(); } super.onDestroy(); }
}
