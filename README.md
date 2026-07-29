# KOLD

KOL ↔ 品牌雙向媒合平台（Laravel）。

**Production:** https://kold.tedku.cloud

## MVP 功能

- Google／Meta SSO（需設定 OAuth；未設定時可用示範登入）
- 角色：KOL / 品牌
- 檔案：手動編輯、社群連結（手動或 OAuth）、AI 草稿
- 探索搜尋與公開個人頁
- 聯絡請求 + 接受後簡易對話

## 本機啟動

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

開啟 `http://localhost:8000`，可用首頁示範登入走完流程。

## 部署

見 [DEPLOY.md](DEPLOY.md)。

- Push 到 `main` → GitHub Actions 自動部署正式站
- 本機有 SSH 時亦可跑 `./scripts/deploy.sh`

## 環境變數

在 `.env` 填入：

- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`
- `FACEBOOK_CLIENT_ID` / `FACEBOOK_CLIENT_SECRET`
- `OPENAI_API_KEY`（可選；沒有時 AI 會用本地 fallback 草稿）

## 不做範圍（Backlog）

通知、防洗版、法務頁、收藏、評分、金流／合約、TikTok 等。
