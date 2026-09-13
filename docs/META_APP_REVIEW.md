# KOLD Meta App Review Notes

## Requested permissions

- `pages_show_list`
- `instagram_basic`

KOLD uses Facebook Login to let a KOL connect Instagram Business / Creator accounts that are linked to Facebook Pages they manage. The first release only reads the Page list and basic Instagram account profile/follower data. It does not request insights, publishing, comments, messages, ads, or Page engagement permissions.

## Reviewer test flow

1. Open `https://kold.tedku.cloud`.
2. Click **用 Meta 開始**.
3. Log in with the provided reviewer/test Facebook account.
4. Choose **KOL** during onboarding if the account has no role yet.
5. Open **我的檔案** and publish the draft profile if needed.
6. Open **社群連結**.
7. Click **OAuth 連 Meta／IG**.
8. Approve the Facebook Login dialog.
9. KOLD shows the Facebook Pages that have linked Instagram Business / Creator accounts.
10. Select one or more Instagram accounts and click **連結已選帳號**.
11. KOLD returns to **社群連結** and displays the connected Instagram account, its linked Facebook Page, follower count, and sync time.

## User-facing value

Brands use KOLD to discover KOL profiles. Connecting Instagram accounts lets a KOL show verified social identities and follower counts in their KOLD profile, reducing manual entry and improving trust for brand discovery.

## Not in this review round

- Instagram insights
- Publishing content
- Comment or message management
- Ads or business management workflows
