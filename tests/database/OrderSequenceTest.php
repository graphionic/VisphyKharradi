<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\OrderNumberService;
use Config\Database;

/**
 * OrderSequenceTest — Phase 2
 *
 * Verifies FTP-YYYY-000001 year-scoped sequence.
 *
 * @internal
 */
final class OrderSequenceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testFirstNumberForYear2026(): void
    {
        $svc = new OrderNumberService(Database::connect('tests'));
        $num = $svc->generate(2026);
        $this->assertEquals('FTP-2026-000001', $num);
    }

    public function testNextNumberIncrements(): void
    {
        $db  = Database::connect('tests');
        $svc = new OrderNumberService($db);

        // Ensure clean: truncate order_sequences for 2026
        $db->table('order_sequences')->where('year', 2026)->delete();

        $first  = $svc->generate(2026);
        $second = $svc->generate(2026);

        $this->assertEquals('FTP-2026-000001', $first);
        $this->assertEquals('FTP-2026-000002', $second);
    }

    public function testDifferentYearResets(): void
    {
        $svc = new OrderNumberService(Database::connect('tests'));
        $db  = Database::connect('tests');

        // Clean both years — refresh already does, but be explicit
        $db->table('order_sequences')->where('year', 2027)->delete();
        $db->table('order_sequences')->where('year', 2026)->delete();

        $num2027 = $svc->generate(2027);
        $this->assertEquals('FTP-2027-000001', $num2027);

        // 2026 should start at 000001 (different year resets)
        $num2026First = $svc->generate(2026);
        $this->assertEquals('FTP-2026-000001', $num2026First);

        // Next 2026 should increment
        $num2026Next = $svc->generate(2026);
        $this->assertEquals('FTP-2026-000002', $num2026Next);
    }

    public function testFormatAlwaysSixDigits(): void
    {
        $svc = new OrderNumberService(Database::connect('tests'));
        // Clean year 2099
        Database::connect('tests')->table('order_sequences')->where('year', 2099)->delete();
        $num = $svc->generate(2099);
        $this->assertMatchesRegularExpression('/^FTP-2099-000001$/', $num);
    }

    public function testUniqueConstraintOnOrders(): void
    {
        // Directly test DB unique on orders.order_number — use 20-char safe number
        $packageModel = new \App\Models\PackageModel();
        $packageId = $packageModel->insert([
            'name'          => 'Seq Test Pkg',
            'slug'          => 'seq-test-' . substr(uniqid(), -6),
            'selling_price' => '100.00',
            'regular_price' => '200.00',
            'cta_label'     => 'Get Started',
            'display_order' => 100,
            'is_active'     => 1,
        ], true);
        $this->assertNotFalse($packageId, 'Package insert failed: ' . json_encode($packageModel->errors()));

        $orderModel = new \App\Models\OrderModel();
        $orderNumber = sprintf('FTP-2026-%06d', random_int(100000, 999999));
        $data = [
            'order_number'            => $orderNumber,
            'package_id'              => $packageId,
            'package_name_snapshot'   => 'Snap',
            'package_slug_snapshot'   => 'snap',
            'package_price_snapshot'  => '100.00',
            'customer_name'           => 'Seq',
            'customer_email'          => 'seq@example.com',
            'customer_phone'          => '9876543210',
            'currency'                => 'INR',
            'subtotal'                => '100.00',
            'discount_amount'         => '0.00',
            'total_amount'            => '100.00',
            'status'                  => 'pending',
        ];
        $id1 = $orderModel->insert($data, true);
        $this->assertNotFalse($id1);
        $id2 = $orderModel->insert($data, true);
        $this->assertFalse($id2, 'Duplicate order_number should be rejected');
    }
}
