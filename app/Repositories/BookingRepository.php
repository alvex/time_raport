<?php
namespace App\Repositories;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Database;
use PDO;

/**
 * Reads and the two allowed writes against cal_reservations.
 *
 * This app only ever writes the columns the legacy resource flow itself
 * writes today: is_pending (report submission, AdminDB::pending_reservation)
 * and starttime/endtime/modified/old_hours (employee hour adjustment,
 * ResDB update). Approved rows (is_pending = 2) are never touched.
 * Every statement carries `machid = ?` with the machid taken from the
 * authenticated session by the caller.
 */
final class BookingRepository
{
    private const SELECT_BOOKING = "
        SELECT res.resid, res.machid, res.scheduleid, res.start_date, res.end_date,
               res.starttime, res.endtime, res.created, res.modified, res.parentid,
               res.is_pending, res.summary, res.recess, res.attention, res.old_hours,
               lg.memberid AS customer_id, lg.fname, lg.lname, lg.address, lg.city,
               lg.phone, lg.mobil, lg.`keys` AS customer_keys
        FROM cal_reservations res
        INNER JOIN cal_reservation_users ru ON ru.resid = res.resid AND ru.owner = 1
        INNER JOIN cal_login lg ON lg.memberid = ru.memberid";

    /**
     * Canonical week/range query — mirror of legacy
     * DBEngine::get_resource_reservations() (lib/DBEngine.class.php:444).
     */
    public function findForResourceBetween(string $machid, int $startTs, int $endTs): array
    {
        $sql = self::SELECT_BOOKING . "
            WHERE res.machid = ?
              AND res.start_date >= ? AND res.end_date <= ?
              AND res.is_blackout <> 1
            ORDER BY res.start_date, res.starttime, lg.fname";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$machid, $startTs, $endTs]);
        return $stmt->fetchAll();
    }

    public function findOneForResource(string $resid, string $machid): ?array
    {
        $sql = self::SELECT_BOOKING . "
            WHERE res.resid = ? AND res.machid = ? AND res.is_blackout <> 1";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$resid, $machid]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Row locks inside the caller's transaction (report submission). */
    public function lockForUpdate(array $resids, string $machid): array
    {
        if ($resids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($resids), '?'));
        $sql = "SELECT resid, is_pending FROM cal_reservations
                WHERE resid IN ($in) AND machid = ? FOR UPDATE";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([...array_values($resids), $machid]);
        return $stmt->fetchAll();
    }

    /**
     * Report submission: legacy parity with AdminDB::pending_reservation()
     * (sets only is_pending, does not touch modified) plus the ownership
     * and state guards legacy lacks.
     */
    public function markPending(array $resids, string $machid): int
    {
        if ($resids === []) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($resids), '?'));
        $sql = "UPDATE cal_reservations SET is_pending = 1
                WHERE resid IN ($in) AND machid = ? AND is_pending < 2";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([...array_values($resids), $machid]);
        // Note: rowCount() counts CHANGED rows — a row already at
        // is_pending = 1 (resubmission) is not included.
        return $stmt->rowCount();
    }

    /**
     * Employee hour adjustment — mirror of the columns legacy ResDB
     * writes for this flow (lib/db/ResDB.class.php:333): times, modified,
     * is_pending = 1 and old_hours (original hours, preserved once set).
     */
    public function updateTimes(string $resid, string $machid, int $startMin, int $endMin, float $oldHours): int
    {
        $sql = "UPDATE cal_reservations
                SET starttime = ?, endtime = ?, modified = ?, is_pending = 1, old_hours = ?
                WHERE resid = ? AND machid = ? AND is_pending < 2";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$startMin, $endMin, time(), $oldHours, $resid, $machid]);
        return $stmt->rowCount();
    }

    public function beginTransaction(): void
    {
        Database::get()->beginTransaction();
    }

    public function commit(): void
    {
        Database::get()->commit();
    }

    public function rollBack(): void
    {
        if (Database::get()->inTransaction()) {
            Database::get()->rollBack();
        }
    }
}
