# KOLD Product Requirements

> Visual delivery plan and current backlog: [PRODUCT_ROADMAP.md](PRODUCT_ROADMAP.md)

## 1. Product Positioning

KOLD is a two-sided KOL marketplace and public profile platform.

The product must support both directions:

- Brands can discover KOLs, review public profiles, invite them to campaigns, and start conversations.
- KOLs can build a public profile page, connect social accounts, show credibility, browse brand projects, and apply for opportunities.

KOLD should not become an agency-only internal CRM in the first version. It can support internal admin workflows later, but the primary product experience is self-serve for brands and KOLs.

## 2. Current System Snapshot

Already implemented:

- Google / Meta SSO entrypoints, with demo login fallback.
- Role onboarding: KOL or brand.
- KOL and brand profile editing.
- Public profile pages at `/u/{user}`.
- Discover flow:
  - Brand users browse KOL profiles.
  - KOL users browse brand profiles.
- Contact request flow:
  - User sends request.
  - Recipient accepts or declines.
  - Accepted request creates a conversation.
- Basic messaging.
- KOL fake data, filters, and photo wall.
- Meta SSO production setup.
- Local implementation for multi Page / Instagram Business connect.
- Linktree-style KOL card with guided setup, draft preview, and publishing.
- AI-assisted KOL tagging with KOL approval and fallback mode.
- Structured brand brief matching with primary and related recommendations.
- Production demo login disabled by environment.
- Public privacy policy, terms, and data deletion instructions.
- Authenticated account deletion and Meta signed data deletion callback.

Important current limits:

- Production database is SQLite. This is acceptable for MVP/demo, but PostgreSQL is recommended before public SaaS onboarding.
- Project marketplace does not exist yet.
- KOL applications to projects do not exist yet.
- Admin moderation does not exist yet.
- No payment, contract, subscription, or commission flow.
- Meta Page / IG connect is deployed; a real tester authorization smoke test is still pending.

## 3. Target Users and Journeys

### KOL Journey

1. KOL signs up with Meta or Google.
2. KOL chooses the KOL role.
3. KOL creates a public profile:
   - Display name
   - Bio
   - Niches
   - Regions
   - Languages
   - Age range
   - Rate range
   - Photos / portfolio
4. KOL connects social accounts:
   - Instagram Business / Creator through Meta.
   - Facebook Page through Meta.
   - YouTube later if Google OAuth is configured.
5. KOL publishes the profile.
6. KOL can:
   - Share public profile URL.
   - Receive brand invitations.
   - Browse public brand projects.
   - Apply to projects.
   - Continue accepted opportunities in conversation.

### Brand Journey

1. Brand signs up with Meta or Google.
2. Brand chooses the brand role.
3. Brand creates a brand profile:
   - Company name
   - Bio
   - Industries
   - Regions
   - Budget range
4. Brand can:
   - Browse and filter KOLs.
   - Review KOL public pages.
   - Send direct invitations.
   - Post projects.
   - Review KOL applications.
   - Continue accepted opportunities in conversation.

### Admin Journey

Admin is not required for the first public MVP, but the system should be designed so admin tools can be added.

Later admin responsibilities:

- Review reported profiles or projects.
- Hide abusive content.
- View user/project status.
- Support Meta App Review testing.
- Manage featured KOLs or projects.

## 4. MVP Scope

The MVP should prove the marketplace loop without building commercial infrastructure too early.

### Must Have

| Area | Requirement | Status |
| --- | --- | --- |
| Authentication | Meta login works in production | Done |
| Authentication | Google login configured and smoke-tested | Pending |
| Role onboarding | User chooses KOL or brand | Done |
| KOL public profile | KOL can edit and publish public profile | Done |
| Social credibility | KOL can connect Meta Page / IG Business | Deployed, real authorization test pending |
| Brand discovery | Brand can search/filter KOLs | Done |
| Brand direct invite | Brand can contact KOL from profile | Done |
| Conversation | Accepted request creates messaging thread | Done |
| KOL card | KOL can share short public card URL with links | Done |
| AI tagging | KOL can generate and approve broad matching tags | Done |
| Brand AI matching | Brand can search KOLs from a campaign brief | Done |
| Project posting | Brand can create project opportunities | Pending |
| Project browsing | KOL can browse open projects | Pending |
| Project application | KOL can apply to project | Pending |
| Application review | Brand can accept/decline applications | Pending |
| App Review docs | Meta permission explanation and reviewer flow | Started |

### Should Have

- Shortlist / saved KOLs for brands.
- Profile completion checklist.
- Better profile URL slug instead of numeric user route.
- Basic notification badges for pending requests/applications.
- Admin-only seed/demo data reset in non-production.
- Admin moderation and abuse reporting.

### Not MVP

- Payments.
- Commission handling.
- Contract signing.
- Escrow.
- Full CRM pipeline.
- AI matching score.
- Instagram insights dashboard.
- TikTok integration.
- Advanced analytics.

## 5. Project Marketplace Requirements

### Brand Project Fields

Minimum fields:

- Title
- Brand / company owner
- Campaign brief
- Target niches
- Target regions
- Target platforms
- Budget range
- Deliverables
- Application deadline
- Campaign timing
- Status: draft, published, closed

Optional later fields:

- Required follower range
- Required audience age / gender
- Content usage rights
- Product gifting details
- Compensation type: paid, gifted, affiliate, mixed

### KOL Application Fields

Minimum fields:

- Project
- KOL user
- Message / pitch
- Proposed rate
- Status: pending, accepted, declined, withdrawn

When a brand accepts an application, the system should create or reuse a conversation between the two users.

### Direct Invite vs Project Application

Both should exist, but they should share the same collaboration concept later.

MVP distinction:

- Direct invite: brand starts from a KOL profile.
- Application: KOL starts from a project.

Do not overbuild a complex deal pipeline yet.

## 6. Data and Infrastructure Direction

### Database

Keep SQLite for immediate local/demo work.

Move to PostgreSQL before:

- Public self-serve KOL onboarding.
- Multiple active brand users.
- Paid plans or commercial commitments.
- High-volume messaging or project applications.

PostgreSQL migration should be a separate sprint:

- Provision PostgreSQL.
- Backup SQLite.
- Export/import data.
- Verify users, profiles, social accounts, contact requests, conversations.
- Run smoke tests.
- Keep rollback backup.

### Meta Integration

Current permission target:

- `pages_show_list`
- `instagram_basic`

Do not request these in the first review round unless needed:

- `instagram_manage_insights`
- `pages_read_engagement`
- Publishing or messaging permissions.

Meta integration should be used first for credibility:

- Confirm ownership of social identity.
- Display handle and follower count.
- Support App Review with a narrow, explainable flow.

## 7. Development Roadmap

The maintained visual roadmap now lives in [PRODUCT_ROADMAP.md](PRODUCT_ROADMAP.md). The sprint notes below remain as historical planning context.

### Sprint 1: KOL Card + AI Matching

Status: completed and deployed on 2026-09-11.

Goal: make KOLD useful to KOLs immediately through a shareable card, and make KOL discovery easier for brands through approved AI tags.

Tasks:

- Add short KOL card URL at `/k/{slug}`.
- Let KOLs manage headline, external contact URL, and card links.
- Generate AI suggested tags from profile, links, and social accounts.
- Require KOL approval before tags are used for matching.
- Add brand brief search that translates a brief into tag filters and match reasons.

Acceptance:

- KOL can publish and share a card.
- KOL can generate, approve, or remove AI tags.
- Brand can search with a brief and see matched KOLs with reasons.

### Sprint 2: Stabilize Social Identity

Goal: deploy and verify Meta Page / IG connect.

Tasks:

- Deploy existing multi Page / IG connect implementation.
- Run production migration safely with backup.
- Smoke-test Meta connect using a real tester account.
- Confirm no regression in Meta SSO login.
- Update Meta App Review notes if real screens differ.

Acceptance:

- KOL can connect one or more IG Business / Creator accounts.
- Social page shows IG handle, follower count, linked Page, sync time.
- Public KOL page and Discover still display follower counts.

### Sprint 3: Product Requirements Cleanup

Goal: make the product direction clear inside the repo.

Tasks:

- Keep this PRD updated as source of truth.
- Replace outdated `NEXT.md` items with current sprint backlog.
- Add a lightweight feature matrix for current / next / later.

Acceptance:

- A developer can open the repo and understand what KOLD is building next.

### Sprint 4: Project Marketplace MVP

Goal: let brands post opportunities and KOLs apply.

Tasks:

- Add project model, migration, controller, and views.
- Add project list for KOL users.
- Add project create/edit/publish for brand users.
- Add application model and status flow.
- Link accepted applications to conversation.
- Add feature tests for permissions and status transitions.

Acceptance:

- Brand can publish a project.
- KOL can browse and apply.
- Brand can accept or decline.
- Accepted application opens a conversation.

### Controlled Beta: Professional Public Profile

Status: baseline card deployed; professional profile upgrade moved into Controlled Beta.

Goal: make KOL public pages useful enough to lead KOL invitations and share externally.

Tasks:

- Add public slug for KOL profiles.
- Add profile completion checklist.
- Improve portfolio layout.
- Add clearer CTA for brand contact.
- Add optional media kit fields.
- Keep `/k/{slug}` public and require login only when a Brand starts an invitation.

Acceptance:

- KOL can share a clean public URL.
- Brand can understand the KOL's niche, region, stats, work samples, and rate range quickly.

### Sprint 6: Launch Readiness

Goal: prepare for external users.

Tasks:

- Move production database to PostgreSQL.
- Review legal copy with qualified counsel before commercial launch.
- Add basic admin moderation.
- Add notification badges or email notifications.
- Finish Meta App Review submission material.
- Add backup and restore procedure.

Acceptance:

- KOLD is ready for controlled public onboarding.

## 8. Product Decisions

Current decisions:

- KOLD is a marketplace, not only a portfolio builder.
- KOLD supports both brand-to-KOL discovery and KOL-to-project discovery.
- KOL public profile is a core feature, not a side feature.
- Meta social connect is for credibility first; insights come later.
- PostgreSQL should happen before real SaaS launch, but not before product flow is validated.

Open decisions:

- Will KOLD charge brands, KOLs, or both?
- Will projects be visible publicly or only to logged-in KOLs?
- Should all KOL profiles be public by default after publish, or require approval?
- Does KOLD need agency/admin curated onboarding before self-serve?
- Should direct invites and project applications merge into a single collaboration object later?
