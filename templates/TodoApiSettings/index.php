<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\TodoApiToken|null $token @var string|null $newToken */
$this->assign('title', 'Todo API設定');
?>
<div class="content">
    <h1>Todo API設定</h1>
    <p>外部システムからTodo APIを使うための固定Bearerトークンを管理します。</p>

    <?php if ($newToken) : ?>
        <div class="message success">
            <strong>このトークンは今回だけ表示されます。安全な場所に保存してください。</strong>
            <pre><code><?= h($newToken) ?></code></pre>
        </div>
    <?php endif; ?>

    <section>
        <h2>現在の状態</h2>
        <?php if ($token) : ?>
            <p>有効なトークンが設定されています（末尾: <code><?= h($token->token_hint) ?></code>）。</p>
            <?= $this->Form->postLink('トークンを無効化', ['action' => 'delete'], [
                'class' => 'button button-outline',
                'confirm' => '現在のBearerトークンを無効化しますか？',
            ]) ?>
        <?php else : ?>
            <p>Bearerトークンはまだ設定されていません。</p>
        <?php endif; ?>
    </section>

    <section>
        <h2><?= $token ? 'トークンを再発行' : 'トークンを発行' ?></h2>
        <?= $this->Form->create(null, ['url' => ['action' => 'save']]) ?>
        <?= $this->Form->control('token', [
            'type' => 'password',
            'label' => '任意のトークン（32文字以上）',
            'autocomplete' => 'new-password',
            'minlength' => 32,
            'maxlength' => 255,
            'help' => '空欄のまま発行すると64文字のランダムトークンを自動生成します。',
        ]) ?>
        <?= $this->Form->button($token ? '更新して一度だけ表示' : '発行して一度だけ表示') ?>
        <?= $this->Form->end() ?>
    </section>

    <section>
        <h2>利用例</h2>
        <pre><code>curl <?= h($this->Url->build('/api/todos', ['fullBase' => true])) ?> \
  -H "Authorization: Bearer 発行したトークン"</code></pre>
    </section>
</div>
