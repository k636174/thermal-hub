<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use RuntimeException;

class UserCommand extends Command
{
    /** Configure command arguments. */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription('アカウントを管理します。')
            ->addArgument('action', ['required' => true, 'help' => 'create | reset-password'])
            ->addArgument('email', ['required' => true])
            ->addArgument('name', ['required' => false, 'help' => 'createの場合に必須']);
    }

    /** Execute account creation or password reset. */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $action = (string)$args->getArgument('action');
        if (!in_array($action, ['create', 'reset-password'], true)) {
            $io->err('対応する操作は create と reset-password です。');

            return self::CODE_ERROR;
        }

        if ($action === 'create' && trim((string)$args->getArgument('name')) === '') {
            $io->err('createでは表示名を指定してください。');

            return self::CODE_ERROR;
        }

        $password = $this->askHidden($io, 'パスワード（8文字以上）');
        $confirmation = $this->askHidden($io, 'パスワード（確認）');
        if ($password !== $confirmation) {
            $io->err('パスワードが一致しません。');

            return self::CODE_ERROR;
        }
        if (mb_strlen($password) < 8) {
            $io->err('パスワードは8文字以上で入力してください。');

            return self::CODE_ERROR;
        }

        $users = $this->fetchTable('Users');
        $email = mb_strtolower(trim((string)$args->getArgument('email')));
        if ($action === 'reset-password') {
            $user = $users->find()->where(['email' => $email])->first();
            if ($user === null) {
                $io->err('指定したメールアドレスのアカウントが見つかりません。');

                return self::CODE_ERROR;
            }
            $user = $users->patchEntity($user, ['password' => $password]);
            if (!$users->save($user)) {
                $this->outputErrors($io, $user->getErrors());

                return self::CODE_ERROR;
            }
            $io->success('パスワードをリセットしました。');

            return self::CODE_SUCCESS;
        }

        $user = $users->newEntity([
            'email' => $email,
            'name' => $args->getArgument('name'),
            'password' => $password,
        ]);
        if (!$users->save($user)) {
            $this->outputErrors($io, $user->getErrors());

            return self::CODE_ERROR;
        }
        $io->success('アカウントを作成しました。');

        return self::CODE_SUCCESS;
    }

    /**
     * Output entity validation errors.
     *
     * @param array<string, array<string, string>> $errors Validation errors.
     */
    private function outputErrors(ConsoleIo $io, array $errors): void
    {
        foreach ($errors as $field => $messages) {
            $io->err($field . ': ' . implode(', ', $messages));
        }
    }

    /**
     * Read a password without echoing it to the terminal.
     */
    private function askHidden(ConsoleIo $io, string $prompt): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $io->out($prompt . ': ', 0);
            $script = ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'read_password.ps1';
            $command = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($script);
            $password = shell_exec($command);
            $io->out();
            if ($password === null || $password === false) {
                throw new RuntimeException('パスワードを読み取れませんでした。');
            }

            return rtrim($password, "\r\n");
        }

        shell_exec('stty -echo');
        try {
            return $io->ask($prompt);
        } finally {
            shell_exec('stty echo');
            $io->out();
        }
    }
}
