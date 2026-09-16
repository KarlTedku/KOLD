@extends('layouts.app')

@section('title', ($project->exists ? '編輯' : '新增').'合作項目 — KOLD')

@section('content')
@php
    $options = config('kold.project_options');
    $storedNiches = old('niches', $project->niches ?? []);
    $storedRegions = old('regions', $project->regions ?? []);
    $customNiches = array_values(array_diff((array) $storedNiches, array_keys($options['niches']), ['other']));
    $customRegions = array_values(array_diff((array) $storedRegions, array_keys($options['regions']), ['other']));
    $selectedNiches = array_values(array_intersect((array) $storedNiches, array_keys($options['niches'])));
    $selectedRegions = array_values(array_intersect((array) $storedRegions, array_keys($options['regions'])));
    $nichesOther = old('niches_other', implode('、', $customNiches));
    $regionsOther = old('regions_other', implode('、', $customRegions));
    $hasOtherNiche = in_array('other', (array) $storedNiches, true) || $nichesOther !== '';
    $hasOtherRegion = in_array('other', (array) $storedRegions, true) || $regionsOther !== '';
    $errorFieldsByStep = [
        1 => ['title', 'campaign_objective', 'brief'],
        2 => ['niches', 'niches_other', 'regions', 'regions_other', 'platforms', 'target_audience'],
        3 => ['collaboration_formats', 'compensation_type', 'budget_min', 'budget_max', 'deliverables', 'usage_rights'],
        4 => ['application_deadline', 'campaign_start_date', 'campaign_end_date'],
    ];
    $errorStep = 1;
    foreach ($errorFieldsByStep as $stepNumber => $fields) {
        if (collect($errors->keys())->contains(fn ($key) => collect($fields)->contains(fn ($field) => $key === $field || str_starts_with($key, $field.'.')))) {
            $errorStep = $stepNumber;
            break;
        }
    }
@endphp

<section class="section project-page project-builder-page">
    <a class="back-link" href="{{ route('projects.manage') }}">← 返回管理項目</a>

    <div class="builder-heading project-builder-heading">
        <div>
            <span class="eyebrow">Brand Project</span>
            <h2>{{ $project->exists ? '編輯合作項目' : '新增合作項目' }}</h2>
            <p class="lead">逐步整理合作需要，完成後先儲存為草稿，再決定幾時公開。</p>
        </div>
        <span class="publish-state {{ $project->status === 'published' ? 'is-live' : '' }}">
            {{ $project->status === 'published' ? '已公開' : '草稿' }}
        </span>
    </div>

    <div class="project-wizard" data-project-wizard data-error-step="{{ $errors->any() ? $errorStep : 1 }}">
        <nav class="project-stepper" aria-label="合作項目建立進度">
            @foreach ([1 => '合作概覽', 2 => '配對條件', 3 => '內容與預算', 4 => '時程與確認'] as $number => $label)
                <button class="project-step-button" type="button" data-step-target="{{ $number }}">
                    <span class="project-step-number" aria-hidden="true">{{ $number }}</span>
                    <span class="project-step-label">{{ $label }}</span>
                </button>
            @endforeach
        </nav>
        <p class="sr-only" aria-live="polite" data-step-announcement></p>

        <form class="project-form project-form-panel" data-project-form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}" novalidate>
            @csrf
            @if ($project->exists) @method('PUT') @endif

            <fieldset class="project-form-step" data-step="1">
                <div class="builder-section-heading">
                    <div><span>步驟 1 / 4</span><h3 tabindex="-1">合作概覽</h3></div>
                    <p>先講清楚今次合作想做到咩，等 KOL 一眼掌握方向。</p>
                </div>

                <label for="title">項目名稱 <span class="required-mark">必填</span></label>
                <input id="title" name="title" required maxlength="140" value="{{ old('title', $project->title) }}" placeholder="例如：夏日防曬新品體驗計劃" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title')<p class="field-error" id="title-error">{{ $message }}</p>@enderror

                <fieldset class="choice-group">
                    <legend>今次合作主要目標</legend>
                    <p class="field-help">選擇最接近嘅一項，之後仍可在簡介補充。</p>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['campaign_objectives'] as $value => $label)
                            <label class="choice-card"><input type="radio" name="campaign_objective" value="{{ $value }}" data-label="{{ $label }}" @checked(old('campaign_objective', $project->campaign_objective) === $value)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @error('campaign_objective')<p class="field-error">{{ $message }}</p>@enderror

                <label for="brief">合作簡介 <span class="required-mark">必填</span></label>
                <textarea id="brief" name="brief" required minlength="20" maxlength="5000" rows="6" placeholder="介紹產品／服務、合作背景，同埋希望 KOL 點樣呈現內容。" @error('brief') aria-invalid="true" aria-describedby="brief-error" @enderror>{{ old('brief', $project->brief) }}</textarea>
                <div class="field-meta"><span>建議包括產品賣點、創作方向及希望帶出嘅訊息。</span><span><output data-count-for="brief">0</output> / 5000</span></div>
                @error('brief')<p class="field-error" id="brief-error">{{ $message }}</p>@enderror

                <div class="project-step-actions"><span></span><button class="btn btn-primary js-next" type="button">下一步：配對條件</button></div>
            </fieldset>

            <fieldset class="project-form-step" data-step="2">
                <div class="builder-section-heading">
                    <div><span>步驟 2 / 4</span><h3 tabindex="-1">配對條件</h3></div>
                    <p>用一致選項描述理想 KOL，令搜尋同配對更準確。</p>
                </div>

                <fieldset class="choice-group">
                    <legend>內容類別</legend><p class="field-help">可選多於一項。</p>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['niches'] as $value => $label)
                            <label class="choice-card"><input type="checkbox" name="niches[]" value="{{ $value }}" data-label="{{ $label }}" @checked(in_array($value, $selectedNiches, true))><span>{{ $label }}</span></label>
                        @endforeach
                        <label class="choice-card"><input type="checkbox" name="niches[]" value="other" data-other-toggle="niches_other" data-label="其他" @checked($hasOtherNiche)><span>其他</span></label>
                    </div>
                    <div class="other-choice-field" data-other-field="niches_other" @if(!$hasOtherNiche) hidden @endif><label for="niches_other">其他內容類別</label><input id="niches_other" name="niches_other" maxlength="120" value="{{ $nichesOther }}" placeholder="例如：汽車、藝術文化"></div>
                </fieldset>
                @error('niches')<p class="field-error">{{ $message }}</p>@enderror
                @error('niches.*')<p class="field-error">{{ $message }}</p>@enderror
                @error('niches_other')<p class="field-error">{{ $message }}</p>@enderror

                <fieldset class="choice-group">
                    <legend>目標市場／地區</legend><p class="field-help">選擇希望內容主要接觸嘅市場。</p>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['regions'] as $value => $label)
                            <label class="choice-card"><input type="checkbox" name="regions[]" value="{{ $value }}" data-label="{{ $label }}" @checked(in_array($value, $selectedRegions, true))><span>{{ $label }}</span></label>
                        @endforeach
                        <label class="choice-card"><input type="checkbox" name="regions[]" value="other" data-other-toggle="regions_other" data-label="其他" @checked($hasOtherRegion)><span>其他</span></label>
                    </div>
                    <div class="other-choice-field" data-other-field="regions_other" @if(!$hasOtherRegion) hidden @endif><label for="regions_other">其他市場／地區</label><input id="regions_other" name="regions_other" maxlength="120" value="{{ $regionsOther }}" placeholder="例如：日本、澳洲"></div>
                </fieldset>
                @error('regions')<p class="field-error">{{ $message }}</p>@enderror
                @error('regions.*')<p class="field-error">{{ $message }}</p>@enderror
                @error('regions_other')<p class="field-error">{{ $message }}</p>@enderror

                <fieldset class="choice-group">
                    <legend>目標平台</legend>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['platforms'] as $value => $label)
                            <label class="choice-card"><input type="checkbox" name="platforms[]" value="{{ $value }}" data-label="{{ $label }}" @checked(in_array($value, old('platforms', $project->platforms ?? []), true))><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @error('platforms')<p class="field-error">{{ $message }}</p>@enderror
                @error('platforms.*')<p class="field-error">{{ $message }}</p>@enderror

                <label for="target_audience">目標客群簡述</label>
                <textarea id="target_audience" name="target_audience" maxlength="500" rows="3" placeholder="例如：香港 25–34 歲、關注護膚與健康生活嘅上班族">{{ old('target_audience', $project->target_audience) }}</textarea>
                @error('target_audience')<p class="field-error">{{ $message }}</p>@enderror

                <div class="project-step-actions"><button class="btn btn-ghost js-prev" type="button">上一步</button><button class="btn btn-primary js-next" type="button">下一步：內容與預算</button></div>
            </fieldset>

            <fieldset class="project-form-step" data-step="3">
                <div class="builder-section-heading">
                    <div><span>步驟 3 / 4</span><h3 tabindex="-1">內容與預算</h3></div>
                    <p>先定合作形式同商業條件，詳細執行方式可以稍後再傾。</p>
                </div>

                <fieldset class="choice-group">
                    <legend>合作形式</legend><p class="field-help">可選多於一項。</p>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['collaboration_formats'] as $value => $label)
                            <label class="choice-card"><input type="checkbox" name="collaboration_formats[]" value="{{ $value }}" data-label="{{ $label }}" @checked(in_array($value, old('collaboration_formats', $project->collaboration_formats ?? []), true))><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @error('collaboration_formats')<p class="field-error">{{ $message }}</p>@enderror
                @error('collaboration_formats.*')<p class="field-error">{{ $message }}</p>@enderror

                <fieldset class="choice-group">
                    <legend>報酬方式</legend>
                    <div class="choice-cards choice-cards-3">
                        @foreach ($options['compensation_types'] as $value => $label)
                            <label class="choice-card"><input type="radio" name="compensation_type" value="{{ $value }}" data-label="{{ $label }}" @checked(old('compensation_type', $project->compensation_type) === $value)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @error('compensation_type')<p class="field-error">{{ $message }}</p>@enderror

                <div class="form-grid">
                    <div><label for="budget_min">預算下限（HK$）</label><input id="budget_min" name="budget_min" type="number" min="0" inputmode="numeric" value="{{ old('budget_min', $project->budget_min) }}" placeholder="3000">@error('budget_min')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label for="budget_max">預算上限（HK$）</label><input id="budget_max" name="budget_max" type="number" min="0" inputmode="numeric" value="{{ old('budget_max', $project->budget_max) }}" placeholder="10000">@error('budget_max')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>

                <label for="deliverables">交付要求</label>
                <textarea id="deliverables" name="deliverables" maxlength="3000" rows="4" placeholder="例如：1 條 30–60 秒短片、3 張限時動態；內容發布前需提供一次預覽。">{{ old('deliverables', $project->deliverables) }}</textarea>
                @error('deliverables')<p class="field-error">{{ $message }}</p>@enderror

                <fieldset class="choice-group">
                    <legend>內容使用權</legend><p class="field-help">如未確定，可選擇稍後商議。</p>
                    <div class="choice-cards choice-cards-2">
                        @foreach ($options['usage_rights'] as $value => $label)
                            <label class="choice-card"><input type="radio" name="usage_rights" value="{{ $value }}" data-label="{{ $label }}" @checked(old('usage_rights', $project->usage_rights) === $value)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @error('usage_rights')<p class="field-error">{{ $message }}</p>@enderror

                <div class="project-step-actions"><button class="btn btn-ghost js-prev" type="button">上一步</button><button class="btn btn-primary js-next" type="button">下一步：時程與確認</button></div>
            </fieldset>

            <fieldset class="project-form-step" data-step="4">
                <div class="builder-section-heading">
                    <div><span>步驟 4 / 4</span><h3 tabindex="-1">時程與確認</h3></div>
                    <p>設定重要日期，再快速檢查一次所有合作資料。</p>
                </div>

                <div class="form-grid form-grid-3 project-date-grid">
                    <div><label for="application_deadline">申請截止</label><input id="application_deadline" name="application_deadline" type="date" value="{{ old('application_deadline', $project->application_deadline?->format('Y-m-d')) }}">@error('application_deadline')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign_start_date">開始日期</label><input id="campaign_start_date" name="campaign_start_date" type="date" value="{{ old('campaign_start_date', $project->campaign_start_date?->format('Y-m-d')) }}">@error('campaign_start_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign_end_date">完成日期</label><input id="campaign_end_date" name="campaign_end_date" type="date" value="{{ old('campaign_end_date', $project->campaign_end_date?->format('Y-m-d')) }}">@error('campaign_end_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>

                <section class="project-review" aria-labelledby="project-review-title">
                    <div class="project-review-heading"><div><span class="eyebrow">Review</span><h4 id="project-review-title">草稿資料摘要</h4></div><span>儲存後仍可隨時修改</span></div>
                    <div class="project-review-grid">
                        <article><div><h5>合作概覽</h5><button type="button" data-review-edit="1">修改</button></div><dl><div><dt>項目</dt><dd data-review="title">—</dd></div><div><dt>目標</dt><dd data-review="campaign_objective">—</dd></div></dl></article>
                        <article><div><h5>配對條件</h5><button type="button" data-review-edit="2">修改</button></div><dl><div><dt>類別</dt><dd data-review="niches">—</dd></div><div><dt>市場</dt><dd data-review="regions">—</dd></div><div><dt>平台</dt><dd data-review="platforms">—</dd></div></dl></article>
                        <article><div><h5>內容與預算</h5><button type="button" data-review-edit="3">修改</button></div><dl><div><dt>形式</dt><dd data-review="collaboration_formats">—</dd></div><div><dt>報酬</dt><dd data-review="compensation_type">—</dd></div><div><dt>預算</dt><dd data-review="budget">—</dd></div><div><dt>使用權</dt><dd data-review="usage_rights">—</dd></div></dl></article>
                        <article><div><h5>時程</h5><button type="button" data-review-edit="4">修改</button></div><dl><div><dt>申請截止</dt><dd data-review="application_deadline">—</dd></div><div><dt>合作日期</dt><dd data-review="campaign_dates">—</dd></div></dl></article>
                    </div>
                </section>

                <div class="project-step-actions project-final-actions"><button class="btn btn-ghost js-prev" type="button">上一步</button><div><span>只會儲存為草稿，不會即時公開。</span><button class="btn btn-primary" type="submit">{{ $project->exists ? '儲存更新' : '儲存草稿' }}</button></div></div>
            </fieldset>
        </form>
    </div>

    @if ($project->exists)
        <div class="publish-bar">
            <div><strong>{{ $project->status === 'published' ? '項目現正公開' : '項目未有接受申請' }}</strong><span>公開後，所有訪客可瀏覽，只有登入 KOL 可以申請。</span></div>
            @if ($project->status !== 'published')
                <form method="POST" action="{{ route('projects.publish', $project) }}">@csrf<button class="btn btn-accent" type="submit">公開項目</button></form>
            @else
                <form method="POST" action="{{ route('projects.close', $project) }}">@csrf<button class="btn btn-ghost" type="submit">截止申請</button></form>
            @endif
        </div>
    @endif
</section>
@endsection

@section('scripts')
<script>
(() => {
    const wizard = document.querySelector('[data-project-wizard]');
    if (!wizard) return;

    const form = wizard.querySelector('[data-project-form]');
    const steps = [...form.querySelectorAll('[data-step]')];
    const stepButtons = [...wizard.querySelectorAll('[data-step-target]')];
    const announcement = wizard.querySelector('[data-step-announcement]');
    let currentStep = Number(wizard.dataset.errorStep || 1);
    let highestVisited = currentStep;

    wizard.classList.add('is-enhanced');

    const labels = ['合作概覽', '配對條件', '內容與預算', '時程與確認'];
    const showStep = (number, focusHeading = true) => {
        currentStep = Math.min(4, Math.max(1, Number(number)));
        highestVisited = Math.max(highestVisited, currentStep);
        steps.forEach((step) => {
            const active = Number(step.dataset.step) === currentStep;
            step.hidden = !active;
            step.classList.toggle('is-active', active);
        });
        stepButtons.forEach((button, index) => {
            const numberForButton = index + 1;
            const active = numberForButton === currentStep;
            button.classList.toggle('is-active', active);
            button.classList.toggle('is-complete', numberForButton < currentStep || numberForButton < highestVisited);
            button.setAttribute('aria-current', active ? 'step' : 'false');
            button.disabled = numberForButton > highestVisited + 1;
        });
        if (currentStep === 4) updateReview();
        announcement.textContent = `步驟 ${currentStep} / 4：${labels[currentStep - 1]}`;
        if (focusHeading) {
            steps[currentStep - 1].querySelector('h3')?.focus({ preventScroll: true });
            wizard.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        }
    };

    const validateStep = (number) => {
        const controls = [...steps[number - 1].querySelectorAll('input, textarea, select')].filter((control) => !control.disabled);
        const firstInvalid = controls.find((control) => !control.checkValidity());
        if (!firstInvalid) return true;
        firstInvalid.reportValidity();
        firstInvalid.focus();
        return false;
    };

    const checkedLabels = (name) => [...form.querySelectorAll(`[name="${name}[]"]:checked`)].map((input) => input.value === 'other' ? document.getElementById(`${name}_other`)?.value.trim() : input.dataset.label).filter(Boolean).join('、') || '未指定';
    const radioLabel = (name) => form.querySelector(`[name="${name}"]:checked`)?.dataset.label || '未指定';
    const value = (name) => form.elements[name]?.value?.trim() || '';
    const setReview = (name, content) => { const target = form.querySelector(`[data-review="${name}"]`); if (target) target.textContent = content || '未指定'; };
    const money = (raw) => raw ? `HK$ ${Number(raw).toLocaleString('zh-HK')}` : '';
    const updateReview = () => {
        setReview('title', value('title'));
        setReview('campaign_objective', radioLabel('campaign_objective'));
        setReview('niches', checkedLabels('niches'));
        setReview('regions', checkedLabels('regions'));
        setReview('platforms', checkedLabels('platforms'));
        setReview('collaboration_formats', checkedLabels('collaboration_formats'));
        setReview('compensation_type', radioLabel('compensation_type'));
        setReview('usage_rights', radioLabel('usage_rights'));
        const minimum = money(value('budget_min'));
        const maximum = money(value('budget_max'));
        setReview('budget', minimum && maximum ? `${minimum} – ${maximum}` : minimum || maximum || '面議');
        setReview('application_deadline', value('application_deadline'));
        const start = value('campaign_start_date');
        const end = value('campaign_end_date');
        setReview('campaign_dates', start && end ? `${start} 至 ${end}` : start || end || '待定');
    };

    form.querySelectorAll('[data-other-toggle]').forEach((toggle) => {
        const fieldName = toggle.dataset.otherToggle;
        const wrapper = form.querySelector(`[data-other-field="${fieldName}"]`);
        const input = document.getElementById(fieldName);
        const sync = () => { wrapper.hidden = !toggle.checked; input.required = toggle.checked; if (!toggle.checked) input.setCustomValidity(''); };
        toggle.addEventListener('change', sync);
        sync();
    });
    form.querySelectorAll('[maxlength]').forEach((control) => {
        const output = form.querySelector(`[data-count-for="${control.id}"]`);
        if (!output) return;
        const sync = () => output.textContent = control.value.length;
        control.addEventListener('input', sync);
        sync();
    });
    form.querySelectorAll('.js-next').forEach((button) => button.addEventListener('click', () => { if (validateStep(currentStep)) showStep(currentStep + 1); }));
    form.querySelectorAll('.js-prev').forEach((button) => button.addEventListener('click', () => showStep(currentStep - 1)));
    stepButtons.forEach((button) => button.addEventListener('click', () => {
        const target = Number(button.dataset.stepTarget);
        if (target <= highestVisited) showStep(target);
        else if (target === currentStep + 1 && validateStep(currentStep)) showStep(target);
    }));
    form.querySelectorAll('[data-review-edit]').forEach((button) => button.addEventListener('click', () => showStep(button.dataset.reviewEdit)));
    form.addEventListener('submit', (event) => { if (!validateStep(currentStep)) event.preventDefault(); });
    showStep(currentStep, false);
})();
</script>
@endsection
