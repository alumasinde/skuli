<?php declare(strict_types=1);
namespace Modules\Terms\Repositories;
use Core\Repository;

final class TermRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert('INSERT INTO terms (academic_year_id,name,start_date,end_date,is_current) VALUES (?,?,?,?,?)',
            [$d['academic_year_id'],$d['name'],$d['start_date'],$d['end_date'],(int)($d['is_current']??0)]);
    }
    public function listByYear(int $yearId): array
    {
        return $this->fetchAll('SELECT * FROM terms WHERE academic_year_id=? AND deleted_at IS NULL ORDER BY start_date', [$yearId]);
    }
    public function listBySchool(int $schoolId): array
    {
        return $this->fetchAll('
            SELECT t.* FROM terms t
            JOIN academic_years ay ON ay.id=t.academic_year_id
            WHERE ay.school_id=? AND t.deleted_at IS NULL
            ORDER BY t.start_date DESC
        ', [$schoolId]);
    }
    public function findById(int $id): ?array { return $this->fetchOne('SELECT * FROM terms WHERE id=?', [$id]); }
    public function findCurrent(int $schoolId): ?array
    {
        return $this->fetchOne('
            SELECT t.* FROM terms t
            JOIN academic_years ay ON ay.id=t.academic_year_id
            WHERE ay.school_id=? AND t.is_current=1 AND t.deleted_at IS NULL LIMIT 1
        ', [$schoolId]);
    }
    public function update(int $id, array $d): void
    {
        $this->execute('UPDATE terms SET name=?,start_date=?,end_date=? WHERE id=?',
            [$d['name'],$d['start_date'],$d['end_date'],$id]);
    }
    public function setCurrent(int $id, int $schoolId): void
    {
        // Unset all current terms for this school first
        $this->execute('
            UPDATE terms t JOIN academic_years ay ON ay.id=t.academic_year_id
            SET t.is_current=0 WHERE ay.school_id=?
        ', [$schoolId]);
        $this->execute('UPDATE terms SET is_current=1 WHERE id=?', [$id]);
    }
    public function delete(int $id): void
    {
        $this->execute('UPDATE terms SET deleted_at=NOW() WHERE id=?', [$id]);
    }
}
