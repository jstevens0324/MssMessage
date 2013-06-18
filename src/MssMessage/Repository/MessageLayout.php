<?php
namespace MssMessage\Repository;
use Doctrine\ORM\EntityRepository;

class MessageLayout extends EntityRepository
{
    public function getByTypeForCompanyQb($messageTypeId, $companyId)
    {
        $qb = $this->createQueryBuilder('q');
        return $qb->where($qb->expr()->eq('q.type', $messageTypeId))
                  ->andWhere($qb->expr()->eq('q.company', $companyId))
                  ->orderBy('q.name', 'ASC');
    }
    
    public function findGridArrayByCompany($companyId)
    {
        $query = $this->_em->createQuery("
            SELECT
                m.id,
                m.name,
                m.description,
                t.name AS type_name
            FROM MssMessage\Entity\MessageLayout m
            LEFT JOIN m.type t
            WHERE m.company = ?1
        ");
        return $query->execute(array(1 => $companyId));
    }
}
