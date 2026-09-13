<?php
namespace App\Repositories;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Database;
use App\Core\Logger;

final class ResourceRepository
{
    /**
     * Employee login lookup: cal_login (`keys` = 2 marks an employee
     * account) joined to cal_resources on the memberid == machid
     * convention; only active resources (status 'a') may log in.
     * LIMIT 2 so duplicate emails are detectable — an ambiguous email
     * is refused and logged rather than logging into an arbitrary account.
     */
    public function findActiveEmployeeByEmail(string $email): ?array
    {
        $sql = "SELECT l.memberid, l.email, l.password, l.fname, l.lname, l.timezone,
                       r.machid, r.name AS resource_name, r.status
                FROM cal_login l
                INNER JOIN cal_resources r ON r.machid = l.memberid
                WHERE l.email = ? AND l.`keys` = 2 AND r.status = 'a'
                LIMIT 2";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll();
        if (count($rows) === 0) {
            return null;
        }
        if (count($rows) > 1) {
            Logger::warn('Ambiguous login email refused: ' . $email);
            return null;
        }
        return $rows[0];
    }

    public function findById(string $machid): ?array
    {
        $sql = "SELECT l.memberid, l.email, l.fname, l.lname, l.timezone,
                       r.name AS resource_name, r.status
                FROM cal_login l
                INNER JOIN cal_resources r ON r.machid = l.memberid
                WHERE l.memberid = ? AND l.`keys` = 2";
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$machid]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
