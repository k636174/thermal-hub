<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Utility\Security;

class TodoApiTokenService
{
    /** Generate a high-entropy token suitable for an Authorization header. */
    public function generate(): string
    {
        return bin2hex(Security::randomBytes(32));
    }

    /** Derive the non-reversible database lookup value for a token. */
    public function hash(string $token): string
    {
        return hash_hmac('sha256', $token, Security::getSalt());
    }

    /** Return a short identifier that can safely be displayed later. */
    public function hint(string $token): string
    {
        return '…' . substr($token, -8);
    }
}
