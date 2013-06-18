<?php
namespace MssMessage\Repository;
use Doctrine\ORM\EntityRepository;

class Message extends EntityRepository
{
    public function findGridArrayByCompany($companyId)
    {
        $query = $this->_em->createQuery("
            SELECT
                m.id,
                m.subject,
                ct.name AS contact_name,
                mt.name AS message_name,
                m.queuedAt,
                m.sentAt
            FROM MssMessage\Entity\Message m
            LEFT JOIN m.sent s
            LEFT JOIN m.contactType ct
            LEFT JOIN m.messageType mt
            WHERE m.company = ?1
        ");
        return $query->execute(array(1 => $companyId));
    }
}
