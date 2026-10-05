<?php /** 파트너 입력 칸(추가·고치기 공용). 변수: $form */ ?>
<div class="grid-2">
  <div class="field"><label for="p-role">역할</label>
    <select id="p-role" name="role" required>
<?php foreach (CONTRACT_ROLE_SIDES as $k => $label): ?>
      <option value="<?= $k ?>"<?= ($form['role'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
    </select></div>
  <div class="field"><label for="p-name">이름</label><input id="p-name" name="name" type="text" maxlength="60" required value="<?= e($form['name']) ?>" placeholder="예: 김○○"></div>
  <div class="field"><label for="p-bank">은행</label><input id="p-bank" name="bank_name" type="text" maxlength="40" list="bank-list" value="<?= e($form['bank_name']) ?>" placeholder="예: NH농협은행"></div>
  <div class="field"><label for="p-acc">계좌번호</label><input id="p-acc" name="bank_account" type="text" inputmode="numeric" maxlength="40" value="<?= e($form['bank_account']) ?>" placeholder="예: 302-1234-5678-91"></div>
  <div class="field"><label for="p-holder">예금주</label><input id="p-holder" name="bank_holder" type="text" maxlength="60" value="<?= e($form['bank_holder']) ?>" placeholder="비우면 이름과 같다고 봐요"></div>
  <div class="field"><label for="p-phone">연락처 (선택)</label><input id="p-phone" name="phone" type="tel" maxlength="40" value="<?= e($form['phone']) ?>" placeholder="010-0000-0000"></div>
</div>
<div class="field"><label for="p-memo">메모 (선택)</label><input id="p-memo" name="memo" type="text" maxlength="500" value="<?= e($form['memo']) ?>" placeholder="예: 매달 10일 지급"></div>
<datalist id="bank-list"><?php foreach (PARTNER_BANKS as $b): ?><option value="<?= e($b) ?>"><?php endforeach; ?></datalist>
