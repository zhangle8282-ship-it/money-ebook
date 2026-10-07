<?php /** 정기청소 정산 · 일회성 정산 공통: 대표 · 운영 비율, 파트너 3명(청소 담당은 인력 배치에서도), 원천징수. 변수: $form, $fixedGap(정해진 대표 비율 — 일회성은 50), $skipByeong(청소 담당 칸을 다른 곳에 그릴 때) */
$fixedGap = $fixedGap ?? null;
$skipByeong = !empty($skipByeong);
?>
    <section class="card stack-lg" aria-labelledby="cf-split">
      <h2 id="cf-split"><?= $skipByeong ? '대표 · 운영 파트너' : '파트너 3명' ?></h2>
      <div class="grid-2">
<?php if ($fixedGap !== null): ?>
        <div class="field">
          <span class="field-label-strong">대표 · 운영 나누기</span>
          <input type="hidden" name="gap_rate" value="<?= (int) $fixedGap ?>" data-calc-input="gap">
          <p class="fixed-split"><span>대표파트너 <b><?= (int) $fixedGap ?>%</b></span><span>운영파트너 <b><?= 100 - (int) $fixedGap ?>%</b></span></p>
          <p class="field-help">회사 몫(수수료)을 대표 · 운영이 반씩 나눠요. 일회성 정산은 이 비율로 정해져 있어요.</p>
        </div>
<?php else: ?>
        <div class="field">
          <label for="cf-gap">대표파트너 비율 (도급 몫 안에서)</label>
          <div class="input-suffix"><input id="cf-gap" name="gap_rate" type="number" min="0" max="100" step="1" value="<?= e((string) ($form['gap_rate'] >= 0 ? $form['gap_rate'] : CONTRACT_GAP_DEFAULT)) ?>" data-calc-input="gap"><span>%</span></div>
          <p class="field-help">운영파트너는 나머지 <strong data-calc="eul_rate"><?= 100 - max(0, (int) $form['gap_rate']) ?></strong>%를 받아요. (기본 대표 60 : 운영 40)</p>
        </div>
        <div class="field"><span class="field-label-strong">빠르게 고르기</span>
          <div class="quick-rates"><button type="button" class="btn btn-outline btn-sm" data-gap="60">60 : 40</button><button type="button" class="btn btn-outline btn-sm" data-gap="50">50 : 50</button><button type="button" class="btn btn-outline btn-sm" data-gap="70">70 : 30</button></div>
        </div>
<?php endif; ?>
<?php $groups = partners_by_role(); foreach (CONTRACT_ROLE_SIDES as $role => $label): if ($skipByeong && $role === 'byeong') { continue; } $sel = (int) ($form[$role . '_partner_id'] ?? 0); $selP = $sel ? (partners_all()[$sel] ?? null) : null; ?>
        <div class="field partner-pick"<?= $role === 'byeong' ? ' data-byeong-field' : '' ?>>
          <label for="cf-<?= $role ?>-partner"><?= e($label) ?></label>
          <select id="cf-<?= $role ?>-partner" name="<?= $role ?>_partner_id">
            <option value=""><?= $role === 'byeong' ? '파트너 · 인력 배치에서 고르기' : '등록한 파트너에서 고르기' ?></option>
<?php if ($role === 'byeong' && $groups[$role]): ?>            <optgroup label="청소 담당 파트너">
<?php endif; ?>
<?php foreach ($groups[$role] as $p): ?>
            <option value="<?= (int) $p['id'] ?>"<?= $sel === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?><?= $p['bank_account'] !== '' ? ' · ' . e($p['bank_name']) : ' · 계좌 없음' ?><?= !empty($p['worker_id']) ? ' · 인력 배치' : '' ?></option>
<?php endforeach; ?>
<?php if ($role === 'byeong'): $pickWorkers = workers_for_pick(); $selWorker = (int) ($form['byeong_worker_id'] ?? 0); ?>
<?php if ($groups[$role]): ?>            </optgroup>
<?php endif; ?>
<?php if ($pickWorkers): ?>            <optgroup label="인력 배치에서 고르기">
<?php foreach ($pickWorkers as $w): ?>
              <option value="w:<?= (int) $w['id'] ?>"<?= $selWorker === (int) $w['id'] ? ' selected' : '' ?>><?= e($w['name']) ?><?= worker_team_label($w) !== '' ? ' (' . e(worker_team_label($w)) . ')' : '' ?> · <?= e(str_cut($w['regions'], 24)) ?> · <?= e(implode('/', array_map(function ($k) { return WORKER_METHODS[$k][0]; }, worker_methods($w['method'])))) ?></option>
<?php endforeach; ?>
            </optgroup>
<?php endif; ?>
<?php endif; ?>
          </select>
          <input name="<?= $role ?>_name" type="text" maxlength="60" value="<?= e($form[$role . '_name']) ?>" placeholder="또는 이름만 적기" aria-label="<?= e($label) ?> 이름 직접 적기">
<?php if ($selP && $selP['bank_account'] !== ''): ?>          <p class="field-help">지급 계좌: <?= e(partner_account($selP)) ?></p>
<?php elseif ($role === 'byeong'): ?>          <p class="field-help">인력 배치에 등록한 사람을 고르면 청소 담당 파트너로 함께 등록돼요. <a href="/admin/workers/new">인력 배치에 사람 추가 ›</a></p>
<?php elseif (!$groups[$role]): ?>          <p class="field-help"><a href="/admin/contracts/partners?role=<?= $role ?>#partner-add"><?= e(CONTRACT_ROLE_SHORT[$role]) ?> 파트너와 계좌 등록하기 ›</a></p>
<?php endif; ?>
        </div>
<?php endforeach; ?>
        <div class="field" data-byeong-field>
          <span class="field-label-strong">청소 담당 파트너 원천징수</span>
          <fieldset class="segmented">
            <legend class="sr-only">청소 담당 파트너 원천징수</legend>
            <input type="radio" id="cf-wh-1" name="withholding" value="1" class="sr-only" data-calc-input="withholding"<?= (int) $form['withholding'] ? ' checked' : '' ?>><label for="cf-wh-1"><?= CONTRACT_WITHHOLDING ?>% 떼고 주기</label>
            <input type="radio" id="cf-wh-0" name="withholding" value="0" class="sr-only" data-calc-input="withholding"<?= (int) $form['withholding'] ? '' : ' checked' ?>><label for="cf-wh-0">떼지 않음</label>
          </fieldset>
        </div>
      </div>
    </section>
