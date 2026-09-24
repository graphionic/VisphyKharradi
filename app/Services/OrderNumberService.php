<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * OrderNumberService — Phase 2 isolated sequence generator
 *
 * LOCKED format: FTP-YYYY-000001 (year-scoped, 6-digit padded)
 * Example: FTP-2026-000001, FTP-2026-000002, FTP-2027-000001 (resets per year)
 *
 * Concurrency-safe algorithm:
 *   1. BEGIN TRANSACTION (InnoDB)
 *   2. INSERT INTO order_sequences (year, last_number) VALUES (?, 1)
 *        ON DUPLICATE KEY UPDATE last_number = last_number + 1
 *      — row-level lock on year row; atomic increment
 *   3. SELECT last_number FROM order_sequences WHERE year = ?  (within same tx)
 *   4. Build order_number = sprintf('FTP-%04d-%06d', year, last_number)
 *   5. COMMIT
 *
 * Caller must be inside the same DB transaction that will later INSERT the order
 * to keep sequence and order atomic; this service exposes both a standalone
 * generate() (own transaction) and a generateInTransaction() for use inside
 * an outer OrderService transaction (future Phase 6).
 *
 * This service DOES NOT create orders/payments — only sequence.
 */
class OrderNumberService
{
    private BaseConnection $db;
    private string $table = 'order_sequences';

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Generate next order number for given year (default current year).
     * Own transaction — for isolated use / tests.
     */
    public function generate(?int $year = null): string
    {
        $year ??= (int) date('Y');

        $this->db->transStart();

        $number = $this->nextNumber($year);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Failed to generate order number — transaction failed');
        }

        return $this->format($year, $number);
    }

    /**
     * Generate inside an existing transaction — call when outer transaction
     * is already started (OrderService will use this).
     */
    public function generateInTransaction(int $year): string
    {
        $number = $this->nextNumber($year);

        return $this->format($year, $number);
    }

    private function nextNumber(int $year): int
    {
        // Atomic upsert — increments last_number or inserts 1
        $sql = "INSERT INTO {$this->table} (year, last_number) VALUES (?, 1)
                ON DUPLICATE KEY UPDATE last_number = last_number + 1";
        $this->db->query($sql, [$year]);

        // Read back within same transaction (locked row)
        $row = $this->db->table($this->table)
            ->select('last_number')
            ->where('year', $year)
            ->get()
            ->getRowArray();

        if (! $row || ! isset($row['last_number'])) {
            throw new \RuntimeException('Failed to read order_sequences after upsert');
        }

        return (int) $row['last_number'];
    }

    private function format(int $year, int $number): string
    {
        return sprintf('FTP-%04d-%06d', $year, $number);
    }
}
