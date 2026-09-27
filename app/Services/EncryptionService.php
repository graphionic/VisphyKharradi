<?php

namespace App\Services;

use Config\Encryption;
use Config\Services;

/**
 * EncryptionService — Native CodeIgniter 4 Encryption Service wrapper.
 *
 * Encrypts and decrypts sensitive settings (Razorpay secrets, WhatsApp API tokens)
 * using CodeIgniter 4's \Config\Services::encrypter() backed strictly by the application's
 * configured encryption key (Config\Encryption / env('encryption.key')).
 */
class EncryptionService
{
    /**
     * Get CodeIgniter 4's native Encrypter service instance.
     *
     * @throws \RuntimeException if encryption.key is not configured in .env / Config\Encryption
     */
    private static function getEncrypter()
    {
        $config = new Encryption();
        if (empty($config->key)) {
            $envKey = (string) env('encryption.key', '');
            if (!empty($envKey)) {
                $config->key = $envKey;
            }
        }

        if (empty($config->key)) {
            throw new \RuntimeException('Encryption key is not configured. Please set encryption.key in your .env file (e.g., run "php spark key:generate").');
        }

        return Services::encrypter($config);
    }

    /**
     * Encrypt plaintext using CI4 Encryption Service.
     * Prefixes output with 'ci4:' for format identification.
     */
    public static function encrypt(string $plainText): string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return '';
        }

        $encrypter = self::getEncrypter();
        $binaryCipher = $encrypter->encrypt($plainText);
        return 'ci4:' . base64_encode($binaryCipher);
    }

    /**
     * Decrypt ciphertext using CI4 Encryption Service.
     * Includes temporary backward compatibility for legacy-encrypted values.
     */
    public static function decrypt(string $cipherText): string
    {
        $cipherText = trim($cipherText);
        if ($cipherText === '') {
            return '';
        }

        // 1. Native CI4 encrypted string
        if (str_starts_with($cipherText, 'ci4:')) {
            $rawCipher = base64_decode(substr($cipherText, 4), true);
            if ($rawCipher === false) {
                return '';
            }
            try {
                $encrypter = self::getEncrypter();
                return (string) $encrypter->decrypt($rawCipher);
            } catch (\Throwable $e) {
                log_message('error', 'CI4 Decryption failed: ' . $e->getMessage());
                return '';
            }
        }

        // 2. Temporary backward compatibility fallback for legacy stored values
        return self::legacyDecrypt($cipherText);
    }

    /**
     * Legacy decryption fallback for custom openSSL values stored during initial setup.
     * Uses strictly configured encryption.key without predictable app fallbacks.
     */
    private static function legacyDecrypt(string $cipherText): string
    {
        $raw = base64_decode($cipherText, true);
        if ($raw === false) {
            return '';
        }

        $keyStr = (string) env('encryption.key', '');
        if (empty($keyStr)) {
            return '';
        }

        if (str_starts_with($keyStr, 'hex2bin:')) {
            $keyStr = hex2bin(substr($keyStr, 8));
        }

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($raw) <= $ivLength) {
            return '';
        }

        $iv = substr($raw, 0, $ivLength);
        $encrypted = substr($raw, $ivLength);

        $derivedKey = substr(hash('sha256', $keyStr, true), 0, 32);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $derivedKey, 0, $iv);

        return $decrypted !== false ? $decrypted : '';
    }
}
