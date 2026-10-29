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

    /**
     * Raw durations for the monthly summary: bookings whose start_date
     * falls in [startTs, endTsExclusive). Half-open on start_date only —
     * month boundaries never double-count a booking the way an end_date
     * bound could. Reads cal_reservations alone (no JOIN), so every
     * booking is counted exactly once regardless of customer rows.
     */
    public function findDurationsForResourceStartingIn(string $machid, int $startTs, int $endTsExclusive): array
    {
        $sql = "SELECT res.resid, res.starttime, res.endtime, res.is_pending
                FROM cal_reservations res
                WHERE res.machid = ?
                  AND res.start_date >= ? AND res.start_date < ?
                  AND res.is_blackout <> 1";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$machid, $startTs, $endTsExclusive]);
        return $stmt->fetchAll();
    }

    /**
     * First/last booking start_date for a resource (year range for the
     * history month picker), or null when the resource has no bookings.
     */
    public function startDateSpan(string $machid): ?array
    {
        $sql = "SELECT MIN(start_date) AS min_ts, MAX(start_date) AS max_ts
                FROM cal_reservations
                WHERE machid = ? AND is_blackout <> 1";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$machid]);
        $row = $stmt->fetch();
        if ($row === false || $row['min_ts'] === null) {
            return null;
        }
        return ['min' => (int)$row['min_ts'], 'max' => (int)$row['max_ts']];
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

    /**
     * Report submission. Only DRAFT rows (is_pending = 0) may ever be
     * submitted — a submitted (1) or approved (2) row is fully locked.
     * The conditional UPDATE is the authoritative guard: on MyISAM a
     * single statement is atomic (table lock), so a concurrent submit
     * from another tab simply matches 0 rows here. rowCount() equals the
     * number of rows that actually flipped 0 -> 1; the caller compares
     * it against the expected count and treats any mismatch as failure.
     */
    public function markPending(array $resids, string $machid): int
    {
        if ($resids === []) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($resids), '?'));
        $sql = "UPDATE cal_reservations SET is_pending = 1
                WHERE resid IN ($in) AND machid = ? AND is_pending = 0";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([...array_values($resids), $machid]);
        return $stmt->rowCount();
    }

    /**
     * Employee hour adjustment — writes the same columns as legacy ResDB
     * (lib/db/ResDB.class.php:333): times, modified, old_hours. ONLY a
     * draft row (is_pending = 0) may be edited: a submitted or approved
     * row is fully locked, and the condition here is the final guard —
     * a stale form/tab or hand-crafted POST matches 0 rows and nothing
     * (times, modified, old_hours, is_pending) changes.
     * The row stays a draft (is_pending remains 0) until the week is
     * submitted; the adjusted hours travel with the submission.
     */
    public function updateTimes(string $resid, string $machid, int $startMin, int $endMin, float $oldHours): int
    {
        $sql = "UPDATE cal_reservations
                SET starttime = ?, endtime = ?, modified = ?, old_hours = ?
                WHERE resid = ? AND machid = ? AND is_pending = 0";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$startMin, $endMin, time(), $oldHours, $resid, $machid]);
        return $stmt->rowCount();
    }

}
