# KOLD 工程及交付路線圖

最後更新：2026-09-16

本文件係 KOLD 開發流程、CI/CD、release、資料庫及營運準備嘅工程 source of truth。產品功能優先次序見 [產品路線圖](PRODUCT_ROADMAP_ZH_HANT.md)。

## 現行原則

- `main` 永遠代表可部署版本；日常開發必須使用分支及 Pull Request。
- Pull Request 必須通過 SQLite、PostgreSQL、格式、前端 build 及 dependency security checks。
- 正式站只可以部署已提交至 Git 嘅版本；禁止將未提交 working tree 當作長期 production source of truth。
- 所有 production deployment 必須先備份資料庫、環境設定及目前程式。
- Production migration 不可以包含 seed/demo data，亦必須有明確 rollback 或資料恢復方案。
- SQLite 可繼續支援受控 Beta；PostgreSQL 係公開自行註冊前嘅 release gate，而唔係純粹按記錄數量決定。

## 工程階段

| 階段 | 交付成果 | 完成條件 | 狀態 |
| --- | --- | --- | --- |
| E0. Source control baseline | 正式站功能、migrations、測試及文件回到 Git | Production 與 release commit 有可追溯對應；建立 beta tag/release notes | 進行中 |
| E1. CI foundation | PR 自動驗證 PHP、SQLite、PostgreSQL、格式、前端及依賴安全 | 所有 required checks 通過先可以合併 `main` | 本次建立 |
| E2. Safe delivery | Staging、固定 artifact、production approval、atomic release、自動 rollback | 部署失敗可以自動恢復上一版本及解除 maintenance mode | 下一步 |
| E3. Operations | 錯誤、uptime、queue、scheduler、備份及 restore drill | 事故有警報、runbook、復原時間及負責人 | 下一步 |
| E4. Public beta infrastructure | PostgreSQL、Redis、load test、moderation、rate limiting | 通過公開 Beta readiness review | 公開 Beta 前 |

## CI required checks

每個 Pull Request 及 `main` 更新會執行：

1. Composer metadata validation。
2. Laravel Pint formatting check。
3. 全新 SQLite migration 及完整 PHP tests。
4. PostgreSQL 16 migration 及完整 PHP tests。
5. Laravel config、route 及 view cache compilation。
6. Production frontend build。
7. Composer 及 npm dependency security audit。

CI 成功只代表程式可以合併；正式部署仍需要 CD workflow、備份及 production smoke checks。

## Branch 與 release 流程

1. 由最新 `main` 建立短期分支，例如 `codex/feature-name`、`feat/feature-name` 或 `fix/issue-name`。
2. 開 Pull Request，寫清楚功能、風險、migration、測試及 rollback。
3. Required CI checks 全部通過，並解決 review conversation。
4. 合併 `main` 後，由成功完成嘅 `CI` workflow 觸發 production deploy。
5. Smoke check 通過後建立或更新 release notes；重大 Beta baseline 使用 annotated tag。

## Definition of Done

一項工作只有同時符合以下條件先算完成：

- 功能及權限行為有 automated test。
- SQLite 及 PostgreSQL migration/test 均通過，或文件清楚列明暫時不適用原因。
- UI 改動經 desktop/mobile 檢查；重要流程包括 error、empty、loading 或 permission state。
- 冇新增高風險 dependency advisory。
- 文件、環境變數範例及 roadmap 狀態已同步。
- Migration 有 rollback／restore 方案，唔會靠刪除 production data 修復。
- Production release 有備份、smoke check 及可識別嘅 Git commit。

## 下一階段：E2 Safe delivery

E1 合併穩定後，下一個工程 Sprint 應集中：

- 建立 staging environment及獨立 staging database。
- CI 只產生一次固定 artifact，staging／production 使用同一 artifact。
- 改用受限 deploy user，取消 root SSH deployment。
- 使用 release directories加 symlink切換，避免直接覆蓋 live code。
- 所有 maintenance-mode deployment加入失敗 trap，確保網站會自動恢復服務。
- 加入 production environment approval、較完整 authenticated smoke checks及自動 rollback。

## Database release gate

以下任何一項成立，就應啟動 PostgreSQL migration Sprint：

- 準備開放公開自行註冊。
- 壓力測試或 logs 出現持續 `database is locked`／寫入延遲。
- 需要多部 application servers。
- 訊息、申請、session、queue 同時寫入量明顯增加。
- 需要高可用、read replica或更短 backup restore window。

Migration 必須先喺 staging rehearsal，核對 table／row counts、foreign keys、重要 user journeys及 rollback backup，先可以切換 production。
