<?php

namespace MssMessage\Mapper\Winusers;

use PDO,
    Doctrine\DBAL\Connection,
    MssMessage\Message;

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

    public function getPatientList($dsid, array $patients)
    {
        $sql = <<<SQL
            SELECT p.name                               AS patientName,
                   p.birthDate                          AS patientBirthDate,
                   p.weight                             AS patientWeight,
                   p.gender                             AS patientGender,
                   p.fixed                              AS patientFixed,
                   pc.description                       AS patientColor,
                   pb.description                       AS patientBreed,
                   ps.description                       AS patientSpecies,

                   CONCAT(c.firstName, ' ', c.lastName) AS clientFullName,
                   c.rid                                AS clientRid,
                   c.dsid                               AS dsid,
                   c.firstName                          AS clientFirstName,
                   c.lastName                           AS clientLastName,
		   c.preferredContactTypeId             AS contactTypeId,
                   ca.addressLineOne                    AS clientAddressLineOne,
                   ca.addressLineTwo                    AS clientAddressLineTwo,
                   ca.city                              AS clientCity,
                   ca.state                             AS clientState,
                   ca.country                           AS clientCountry,
                   ca.zip                               AS clientZip

            FROM   patient AS p
                   LEFT JOIN client AS c
                     ON p.dsid = c.dsid AND p.clientRid = c.rid
                   LEFT JOIN address AS ca
                     ON ca.id = c.addressId
                   LEFT JOIN breed pb
                     ON pb.dsid = p.dsid AND pb.rid = p.breedRid
                   LEFT JOIN color pc
                     ON pc.dsid = p.dsid AND pc.rid = p.colorRid
                   LEFT JOIN species ps
                     ON ps.dsid = p.dsid AND ps.rid = p.speciesRid

            WHERE  p.dsid = ?
                   AND p.rid IN (?)

            GROUP  BY c.dsid, c.rid
SQL;

        $stmt = $this->conn->executeQuery(
            $sql,
            array($dsid, $patients),
            array(PDO::PARAM_INT, Connection::PARAM_INT_ARRAY)
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}