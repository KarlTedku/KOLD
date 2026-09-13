# KOLD — 下一步功能清單

> Current product source of truth: [docs/PRODUCT_REQUIREMENTS.md](docs/PRODUCT_REQUIREMENTS.md)
>
> Visual roadmap and prioritized backlog: [docs/PRODUCT_ROADMAP.md](docs/PRODUCT_ROADMAP.md)
>
> This file contains older sprint notes. Use the PRD for current product direction and roadmap.

---

目標：登入接好之後，**品牌可以篩選瀏覽 KOL**；先用豐富假資料把體驗填滿，唔使等真社群 API。

---

## A. 明日先做（假資料 + 篩選體驗）

### A1. 豐富 Seed 假資料（最高優先）

而家只有約 2 個 KOL + 1 個品牌，太少。建議一次 seed：

| 欄位 | 說明 |
|------|------|
| KOL 檔案 | 顯示名、bio、niche、地區、語言、報價區間 |
| 頭像／相片 | `users.avatar` + 作品集圖（可先用 placeholder 圖 URL，例如 picsum／unsplash） |
| 粉絲數 | 各平台 `social_accounts.follower_count`（IG／YT／FB 可多平台） |
| 年齡層 | 新增 `age_range`（例如 `18-24`、`25-34`、`35-44`）— 觀眾現有 schema 未有 |
| 品牌假資料 | 3–5 個品牌，方便 KOL 端探索 |

建議數量：**12–20 個已發佈 KOL**、**4–6 個品牌**，覆蓋美妝／旅遊／美食／健身／親子等 niche，粉絲由 5k–500k 唔等。

### A2. 探索篩選（登入後依角色）

已有：關鍵字、niche／產業、地區。  
明日要加（配合假資料）：

| 篩選 | 誰用得到 | 備註 |
|------|----------|------|
| 粉絲數區間 | 品牌睇 KOL | 例如 `<10k` / `10–50k` / `50–200k` / `200k+` |
| 年齡層 | 品牌睇 KOL | 用 `age_range` |
| 平台 | 品牌睇 KOL | IG／YouTube／Facebook 有連結先出 |
| （可選）報價區間 | 品牌睇 KOL | 用現有 `rate_min` / `rate_max` |

角色行為（雙登入＝KOL 或品牌）：

- **品牌登入** → 預設進「探索 KOL」+ 上述 filter  
- **KOL 登入** → 預設進「探索品牌」（現有產業／地區 filter 即可）  
- **未選角色** → 強制 onboarding 選角色（已有）

### A3. KOL 公開頁加強（假資料可撐起）

- 大頭照 + 作品相片牆（3–6 張）  
- 平台／粉絲數清楚列出  
- 年齡層、niche、地區 chips  
- 品牌可一鍵「送出聯絡請求」（已有）

---

## B. 明日同步處理：真登入（Google／Meta）

唔阻擋 A，但你話明日有時間可一齊做：

1. Google OAuth Client ID／Secret → `.env`  
2. Meta App ID／Secret → `.env`（Dev 模式先加自己做 Tester）  
3. Redirect：  
   - `https://kold.tedku.cloud/auth/google/callback`  
   - `https://kold.tedku.cloud/auth/facebook/callback`  
4. 驗收：Google／Meta 登入 → 選角色 → 去探索睇到假 KOL／filter

示範登入可保留作後備。

---

## C. 之後再加（唔阻明日）

- 登入後通知（有人聯絡你）  
- 收藏／短名單  
- 真 IG／YouTube 同步粉絲（要 App Review）  
- 上傳真相片（而家用外部 URL placeholder 即可）  
- AI 定檔接真 API key  

（詳見先前 Backlog。）

---

## D. 建議實作順序（明日 checklist）

1. Migration：`kol_profiles.age_range`、`kol_profiles.photos`（JSON URL 陣列）  
2. Seeder：大批假 KOL／品牌（頭像、相片、粉絲、年齡層）  
3. Discover：粉絲／年齡／平台 filter + 列表卡顯示頭像與粉絲  
4. 公開頁：相片牆  
5. （並行）Google／Meta `.env` + 真登入試一次  
6. Push `main` → Actions 自動上 https://kold.tedku.cloud  

---

## E. 登入後「應該見到咩」（驗收標準）

**品牌帳號**

- 打開探索 → 見到一排假 KOL（有相、粉絲、年齡層）  
- 用 filter 收窄（例如「美妝 + 香港 + 10–50k」）有結果  
- 點入檔案 → 睇到相片牆 → 可送聯絡請求  

**KOL 帳號**

- 打開探索 → 見到假品牌  
- 可完善自己檔案（可稍後再 seed 自己）  

## 進度

- [x] A1–A3：假資料、篩選、公開頁相片牆（2026-07-30）
- [ ] B：Google／Meta 真登入 `.env`
