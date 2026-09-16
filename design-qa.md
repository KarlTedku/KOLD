# KOLD Project Step Form — Design QA

- Source visual truth: `/var/folders/70/ms4qtszs4c189stbf5_sdlpw0000gn/T/codex-clipboard-0703d2b1-dc30-4017-bf9e-9887fe779816.png`
- Desktop implementation: `/tmp/kold-project-step1-desktop.png`
- Desktop review state: `/tmp/kold-project-step4-desktop-top.png`
- Mobile implementation: `/tmp/kold-project-step1-mobile-v2.png`
- Mobile review state: `/tmp/kold-project-step4-mobile.png`
- Combined comparison: `/tmp/kold-project-form-comparison.png`
- Desktop viewport: 1492 × 807 CSS px; browser full-page capture normalized to 1477 px content width.
- Mobile viewport: 390 × 844 CSS px; browser full-page capture is 375 px wide after scrollbar normalization.
- Source pixels: 1959 × 1276. For the combined comparison it was proportionally resized to 1477 × 962 and top-aligned beside the 1477 × 1340 desktop implementation.
- State: authenticated brand; create form step 1, completed review step 4, saved edit form, and owner-visible project detail.

## Full-view comparison evidence

The implementation retains the source screen's KOLD header, serif display hierarchy, left-aligned 900 px form column, pale green-to-sand page treatment, dark pill CTA, and restrained coral/green accents. The longer canvas is intentional: the previous single long form is replaced by one focused step plus progress navigation. Step 4 remains compact enough to show dates, four review cards, and the save action without creating a second workflow.

## Focused region evidence

- Form controls: selected cards use the existing forest token, clear inset selection treatment, and visible keyboard focus without introducing unrelated icon assets.
- Step navigation: active, completed, disabled, and reduced-motion states are distinct; the mobile step labels remain legible at 375 px content width.
- Review cards: copy, spacing, labels, budget formatting, and edit affordances were checked at desktop and mobile sizes.
- Public detail: new objective, audience, collaboration format, compensation, and usage-right labels render only when data exists.

## Required fidelity surfaces

- Fonts and typography: existing Fraunces and Manrope stack preserved; headings, labels, helper copy, and compact review metadata maintain the source hierarchy.
- Spacing and layout rhythm: source column alignment and page margins preserved; form panel, selection grid, and review cards use consistent 12–18 px radii and compact vertical rhythm.
- Colors and visual tokens: existing ink, forest, moss, coral, paper, and line tokens reused. Contrast and semantic selected/error states remain clear.
- Image quality and assets: the target contains no form imagery or custom illustration assets. The KOLD text logo and native date controls remain unchanged; no placeholder or synthetic imagery was introduced.
- Copy and content: all new guidance is Traditional Chinese/Cantonese, concise, and specific to building a KOL collaboration brief.

## Findings and comparison history

1. P2 — Initial mobile capture showed the existing navigation wrapping into multiple rows and the draft badge stretching full width.
   - Fix: changed the small-screen header to a one-line horizontally scrollable navigation beneath the logo and constrained the draft badge to content width.
   - Post-fix evidence: `/tmp/kold-project-step1-mobile-v2.png`; no horizontal page overflow at the 390 px viewport.
2. P2 — The initial public budget summary repeated the currency only once and represented one-sided budgets ambiguously.
   - Fix: added explicit two-sided, minimum-only, maximum-only, and negotiable formatting.
   - Post-fix evidence: owner-visible public detail rendered the complete structured brief and `HK$ 5,000 – HK$ 12,000` range.

No actionable P0, P1, or P2 findings remain. No focused crop was required because all important controls and review text are readable in the full-resolution desktop and mobile captures.

## Interaction and accessibility checks

- Tested required-field validation, step navigation, custom "Other" reveal, multiple selections, review summary generation, final save, edit reload, and public detail rendering.
- Step headings receive focus after navigation; the live region announces the current step; inaccessible future steps are disabled.
- Browser runtime reported no JavaScript errors or unhandled promise rejections; web fonts loaded; no horizontal overflow was detected.
- Reduced-motion CSS disables step and card animation.

final result: passed

---

# KOLD Project Marketplace — Design QA

- Source visual truth: `/var/folders/70/ms4qtszs4c189stbf5_sdlpw0000gn/T/codex-clipboard-c4d49285-fce3-4dcd-98d8-00071d428922.png`
- Rendered implementation: `http://127.0.0.1:8765/projects` in Codex in-app browser tab 1.
- Desktop implementation screenshot: full-page in-app browser capture from this QA run; the browser surface did not expose a local export path.
- Mobile implementation screenshot: full-page in-app browser capture from this QA run; the browser surface did not expose a local export path.
- Desktop viewport: 1280 × 720 CSS px; full-page document content measured 1265 × 2102 px after scrollbar normalization.
- Mobile viewport: 390 × 844 CSS px; document width measured 390 px with no horizontal overflow.
- Source pixels: 1846 × 1262.
- State: public project marketplace with six isolated preview records; default listing and a filtered `健身健康 + Instagram + 可申請` state with three results.

## Full-view comparison evidence

The supplied screenshot and the browser-rendered desktop and mobile captures were opened and reviewed together in the current QA run. The previous narrow, low-contrast long list is intentionally replaced by a stronger forest hero, a visible result count, a sticky filter surface, and responsive project cards. Existing KOLD serif/body typography, forest/coral/paper palette, public browsing behavior, and sample-brand privacy are preserved.

## Focused region evidence

- Filter panel: search, niche, platform, region, minimum budget, sort, open-only toggle, reset, and submit controls were checked for labels, selected-state persistence, spacing, and keyboard-readable native semantics.
- Result cards: brand privacy label, application state, title, brief, tags, budget, deadline, compensation, and CTA were checked at desktop and 390 px mobile widths.
- Empty and filtered states: automated feature coverage confirms the result count, included/excluded records, deadline filtering, and deadline sort order.

## Required fidelity surfaces

- Fonts and typography: existing Fraunces and Manrope stacks are preserved. The new hero, result headings, card titles, labels, and facts create a clearer hierarchy without introducing a new type system.
- Spacing and layout rhythm: the desktop uses a 280 px filter rail and a two-column card grid; 880 px and 640 px breakpoints reflow the layout, with a verified single-column 390 px mobile result.
- Colors and visual tokens: existing ink, forest, moss, coral, paper, mist, and line tokens are reused. Status, objective, platform, and active-filter treatments remain visually distinct.
- Image quality and assets: neither the source list nor the redesign requires imagery. No placeholder graphics, custom SVGs, emoji, or synthetic image assets were introduced.
- Copy and content: public-facing guidance remains Traditional Chinese/Cantonese. One-sided and two-sided budgets, application state, compensation, result count, and empty-state recovery are explicit.

## Findings and comparison history

No actionable P0, P1, or P2 findings were found in the first browser-rendered pass. The intentional structural changes directly address the supplied screen's missing filters, weak information hierarchy, excessive list length, and low visual emphasis. Full-page desktop and mobile captures confirmed readable cards and consistent spacing; no post-comparison visual fix was required.

## Interaction and accessibility checks

- Browser-tested niche, platform, and open-only filtering; the URL state and three matching results updated correctly.
- Verified native labels for every filter, visible status text, semantic definition lists, live result-count updates, and 44 px or larger primary targets.
- Verified no horizontal overflow at 390 px and no browser console errors.
- Automated tests cover keyword, niche, region, platform, minimum-budget, open-only filtering, deadline sort, privacy behavior, and public visibility.

final result: passed
