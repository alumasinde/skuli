<?php declare(strict_types=1);
namespace Modules\AcademicYears\Repositories;
use Core\Repository;

final class AcademicYearRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert('INSERT INTO academic_years (school_id,name,start_date,end_date,is_current) VALUES (?,?,?,?,?)',
            [$d['school_id'],$d['name'],$d['start_date'],$d['end_date'],(int)($d['is_current']??0)]);
    }
    public function listBySchool(int $sid): array
    {
        return $this->fetchAll('SELECT * FROM academic_years WHERE school_id=? AND deleted_at IS NULL ORDER BY start_date DESC', [$sid]);
    }
    public function findById(int $id): ?array { return $this->fetchOne('SELECT * FROM academic_years WHERE id=?', [$id]); }
    public function update(int $id, array $d): void
    {
        $this->execute('UPDATE academic_years SET name=?,start_date=?,end_date=? WHERE id=?',
            [$d['name'],$d['start_date'],$d['end_date'],$id]);
    }
    public function setCurrent(int $id, int $schoolId): void
    {
        $this->execute('UPDATE academic_years SET is_current=0 WHERE school_id=?', [$schoolId]);
        $this->execute('UPDATE academic_years SET is_current=1 WHERE id=?', [$id]);
    }
    public function delete(int $id): void { $this->execute('UPDATE academic_years SET deleted_at=NOW() WHERE id=?', [$id]); }
}
