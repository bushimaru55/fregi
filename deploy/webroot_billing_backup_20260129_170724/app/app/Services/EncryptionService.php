<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    /**
     * 暗号化キーを取得
     */
    private function getKey(): string
    {
        $key = env('FREGI_SECRET_KEY');
        if (empty($key)) {
            throw new Exception('FREGI_SECRET_KEY is not set in environment');
        }
        
        // Base64デコード（32バイトに変換）
        $decodedKey = base64_decode($key, true);
        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            throw new Exception('FREGI_SECRET_KEY must be a valid base64-encoded 32-byte key');
        }
        
        return $decodedKey;
    }

    /**
     * 平文を暗号化（Base64返却）
     *
     * @param string $plaintext 平文
     * @return string Base64エンコードされた暗号文
     * @throws Exception
     */
    public function encryptSecret(string $plaintext): string
    {
        if (empty($plaintext)) {
            throw new Exception('Plaintext cannot be empty');
        }

        $key = $this->getKey();
        $iv = random_bytes(12); // GCM推奨IV長: 12バイト
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new Exception('Encryption failed');
        }

        // IV + Tag + Ciphertext を結合してBase64エンコード
        $encrypted = $iv . $tag . $ciphertext;
        return base64_encode($encrypted);
    }

    /**
     * 暗号文を復号
     *
     * @param string $ciphertextBase64 Base64エンコードされた暗号文
     * @return string 平文
     * @throws Exception
     */
    public function decryptSecret(string $ciphertextBase64): string
    {
        if (empty($ciphertextBase64)) {
            throw new Exception('Ciphertext cannot be empty');
        }

        $encrypted = base64_decode($ciphertextBase64, true);
        if ($encrypted === false) {
            throw new Exception('Invalid base64 ciphertext');
        }

        $key = $this->getKey();
        $ivLength = 12;
        $tagLength = self::TAG_LENGTH;

        if (strlen($encrypted) < $ivLength + $tagLength) {
            throw new Exception('Ciphertext is too short');
        }

        $iv = substr($encrypted, 0, $ivLength);
        $tag = substr($encrypted, $ivLength, $tagLength);
        $ciphertext = substr($encrypted, $ivLength + $tagLength);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new Exception('Decryption failed');
        }

        return $plaintext;
    }
}

