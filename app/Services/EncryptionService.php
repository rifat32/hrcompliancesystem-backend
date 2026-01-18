<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class EncryptionService
{
    protected string $senderPrivateKey;
    protected string $receiverPublicKey;

    public function __construct()
    {
        $sender_key_path = storage_path('keys/sender_private.pem');
        $receiver_key_path = storage_path('keys/receiver_public.pem');

        if (!file_exists($sender_key_path) || !file_exists($receiver_key_path)) {
            abort(500, 'Keys are missing. Please generate and place keys manually.');
        }

        $this->senderPrivateKey = file_get_contents($sender_key_path);
        $this->receiverPublicKey = file_get_contents($receiver_key_path);
    }

    public function generateEncryptedToken(array $claims): array
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->senderPrivateKey),
            InMemory::plainText('')
        );

        $now = new DateTimeImmutable();
        $builder = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+5 minutes'));

        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());
        $jwtString = $token->toString();

        // --- Hybrid Encryption ---
        $aesKey = random_bytes(32); // AES-256 key
        $iv = random_bytes(16);     // AES block size

        $encryptedJwt = openssl_encrypt($jwtString, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($encryptedJwt === false) {
            Log::error('AES encryption failed');
            abort(500, 'Encryption failed');
        }

        if (!openssl_public_encrypt($aesKey, $encryptedAesKey, $this->receiverPublicKey)) {
            Log::error('RSA encryption of AES key failed');
            abort(500, 'Encryption failed');
        }

        return [
            'encrypted_key' => base64_encode($encryptedAesKey),
            'iv' => base64_encode($iv),
            'encrypted_token' => base64_encode($encryptedJwt),
        ];
    }

}
