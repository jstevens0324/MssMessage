<?php

namespace MssMessage\Mergeword;

use PDO,
    Doctrine\DBAL\Connection,
    MssMessage\Model\MessageRecipientClient,
    MssMessage\Service\Mergeword;

class Client implements MergewordInterface
{
    protected $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    
    public function getReplacementsFor(MessageRecipientClient $mrc)
    {
        if (null === $mrc->getLastName()) {
            $this->mapClient($mrc);
        }
        
        return array(
            Mergeword::CLIENT_FIRST_NAME => $mrc->getFirstName(),
            Mergeword::CLIENT_LAST_NAME  => $mrc->getLastName(),
        );
    }
    
    protected function mapClient($mrc)
    {
        $sql  = "SELECT c.* FROM client AS c WHERE dsid = ? AND rid = ?";
        $stmt = $this->conn->executeQuery($sql, array($mrc->getDsid(), $mrc->getClientRid()));
        
        $mrc->fromArray($stmt->fetch(PDO::FETCH_ASSOC));
    }
}
