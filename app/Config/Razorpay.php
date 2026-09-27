<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Models\SettingModel;
use App\Services\EncryptionService;

class Razorpay extends BaseConfig
{
    public string $mode = 'test';
    public string $keyId = '';
    public string $keySecret = '';
    public string $webhookSecret = '';

    public function __construct()
    {
        parent::__construct();

        try {
            $settingModel = new SettingModel();
            $modeSetting = $settingModel->where('setting_key', 'payment.razorpay.mode')->first();
            $mode = trim((string) ($modeSetting['setting_value'] ?? 'test'));
            $this->mode = in_array($mode, ['test', 'live'], true) ? $mode : 'test';

            $prefix = ($this->mode === 'live') ? 'payment.razorpay.live.' : 'payment.razorpay.test.';

            $keyIdRow   = $settingModel->where('setting_key', $prefix . 'key_id')->first();
            $secretRow  = $settingModel->where('setting_key', $prefix . 'key_secret_encrypted')->first();
            $webhookRow = $settingModel->where('setting_key', $prefix . 'webhook_secret_encrypted')->first();

            $dbKeyId   = trim((string) ($keyIdRow['setting_value'] ?? ''));
            $dbSecret  = !empty($secretRow['setting_value']) ? EncryptionService::decrypt($secretRow['setting_value']) : '';
            $dbWebhook = !empty($webhookRow['setting_value']) ? EncryptionService::decrypt($webhookRow['setting_value']) : '';

            if (!empty($dbKeyId)) {
                $this->keyId         = $dbKeyId;
                $this->keySecret     = $dbSecret;
                $this->webhookSecret = $dbWebhook;
                return;
            }
        } catch (\Throwable $e) {
            // Fall back to .env if DB is not ready during early bootstrap
        }

        // Fallback to .env configuration
        $this->keyId         = trim((string) env('RAZORPAY_KEY_ID', ''));
        $this->keySecret     = trim((string) env('RAZORPAY_KEY_SECRET', ''));
        $this->webhookSecret = trim((string) env('RAZORPAY_WEBHOOK_SECRET', ''));
    }
}
