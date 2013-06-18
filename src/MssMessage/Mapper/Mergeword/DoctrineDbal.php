<?php

namespace MssMessage\Mapper\Mergeword;

use PDO,
    Doctrine\DBAL\Connection,
    MssMessage\MergewordSet;

class DoctrineDbal implements MapperInterface
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findByCompanyId($companyId)
    {
        $sql = <<<SQL
            SELECT ms.*,
                   ma.name AS alias,
                   m.name AS mergeword

            FROM   company AS c
                   LEFT JOIN mergeword_set AS ms
                     ON c.mergewordSetId = ms.id
                   LEFT JOIN mergeword_alias AS ma
                     ON ma.mergewordSetId = ms.id
                   LEFT JOIN mergeword m
                     ON ma.mergewordId = m.id

            WHERE  c.id = ?
SQL;

        $stmt = $this->conn->executeQuery($sql, array($companyId));
        $set  = new MergewordSet;
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (!$set->getId()) {
                $set->setId($row['id'])
                    ->setName($row['name'])
                    ->setPrefix($row['prefix'])
                    ->setSuffix($row['suffix']);
            }

            $set->addAlias($row['mergeword'], $row['alias']);
        }

        return $set;
    }
}