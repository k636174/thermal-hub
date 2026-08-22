<?php $this->assign('title', 'ログイン'); ?>
<div class="users form content">
    <?= $this->Form->create() ?>
    <fieldset><legend>ログイン</legend>
        <?= $this->Form->control('email', ['label' => 'メールアドレス', 'type' => 'email', 'required' => true]) ?>
        <?= $this->Form->control('password', ['label' => 'パスワード', 'required' => true]) ?>
    </fieldset>
    <?= $this->Form->button('ログイン') ?>
    <?= $this->Form->end() ?>
</div>
